<?php
/**
 * Master Car - Integração com Gateway de Pagamento (Asaas)
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

class GatewayPagamento {
    private $apiKey;
    private $apiUrl;
    private $gateway;
    
    public function __construct() {
        $config = DB()->fetch("SELECT * FROM config_pagamento WHERE ativo = 1 LIMIT 1");
        
        if (!$config) {
            throw new Exception("Nenhum gateway de pagamento configurado.");
        }
        
        $this->gateway = $config['gateway'];
        $this->apiKey = $config['api_key'];
        
        if ($config['ambiente'] == 'producao') {
            $this->apiUrl = $this->getProdUrl();
        } else {
            $this->apiUrl = $this->getSandboxUrl();
        }
    }
    
    private function getSandboxUrl() {
        switch ($this->gateway) {
            case 'asaas':
                return 'https://sandbox.asaas.com/api/v3';
            case 'mercadopago':
                return 'https://api.mercadopago.com';
            default:
                return '';
        }
    }
    
    private function getProdUrl() {
        switch ($this->gateway) {
            case 'asaas':
                return 'https://api.asaas.com/v3';
            case 'mercadopago':
                return 'https://api.mercadopago.com';
            default:
                return '';
        }
    }
    
    /**
     * Faz requisição para a API
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $result['errors'][0]['description'] ?? $result['message'] ?? 'Erro desconhecido';
            throw new Exception("Erro API ({$httpCode}): " . $errorMsg);
        }
        
        return $result;
    }
    
    /**
     * Cria ou atualiza cliente no gateway
     */
    public function criarCliente($cliente) {
        // Verifica se cliente já existe
        $clienteExistente = $this->buscarClientePorCpfCnpj($cliente['cpf_cnpj']);
        
        if ($clienteExistente) {
            return $clienteExistente;
        }
        
        $data = [
            'name' => $cliente['nome'],
            'cpfCnpj' => limparCpfCnpj($cliente['cpf_cnpj']),
            'email' => $cliente['email'],
            'phone' => limparCpfCnpj($cliente['telefone']),
            'mobilePhone' => limparCpfCnpj($cliente['celular']),
            'address' => $cliente['endereco'],
            'addressNumber' => $cliente['numero'],
            'complement' => $cliente['complemento'],
            'province' => $cliente['bairro'],
            'postalCode' => limparCpfCnpj($cliente['cep']),
            'notificationDisabled' => false
        ];
        
        return $this->request('/customers', 'POST', $data);
    }
    
    /**
     * Busca cliente por CPF/CNPJ
     */
    public function buscarClientePorCpfCnpj($cpfCnpj) {
        try {
            $cpfCnpj = limparCpfCnpj($cpfCnpj);
            $result = $this->request("/customers?cpfCnpj={$cpfCnpj}");
            
            if (!empty($result['data'])) {
                return $result['data'][0];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Gera boleto
     */
    public function gerarBoleto($fatura, $clienteGatewayId) {
        $data = [
            'customer' => $clienteGatewayId,
            'billingType' => 'BOLETO',
            'value' => $fatura['valor_total'],
            'dueDate' => $fatura['data_vencimento'],
            'description' => "Locação semanal - Fatura #{$fatura['numero_fatura']}",
            'externalReference' => $fatura['numero_fatura'],
            'postalService' => false
        ];
        
        $result = $this->request('/payments', 'POST', $data);
        
        // Atualiza fatura com dados do boleto
        DB()->update('faturas_semanal', [
            'transacao_id' => $result['id'],
            'gateway_pagamento' => $this->gateway,
            'boleto_url' => $result['bankSlipUrl'],
            'boleto_linha_digitavel' => $result['identificationField'] ?? null,
            'boleto_nosso_numero' => $result['nossoNumero'] ?? null
        ], 'id = :id', ['id' => $fatura['id']]);
        
        return $result;
    }
    
    /**
     * Gera PIX
     */
    public function gerarPix($fatura, $clienteGatewayId) {
        $data = [
            'customer' => $clienteGatewayId,
            'billingType' => 'PIX',
            'value' => $fatura['valor_total'],
            'dueDate' => $fatura['data_vencimento'],
            'description' => "Locação semanal - Fatura #{$fatura['numero_fatura']}",
            'externalReference' => $fatura['numero_fatura']
        ];
        
        $result = $this->request('/payments', 'POST', $data);
        
        // Obtém QR Code do PIX
        $pixData = $this->request("/payments/{$result['id']}/pixQrCode");
        
        // Salva imagem do QR Code
        $qrCodePath = null;
        if (!empty($pixData['encodedImage'])) {
            $qrCodePath = $this->salvarQrCode($pixData['encodedImage'], $fatura['numero_fatura']);
        }
        
        // Atualiza fatura com dados do PIX
        DB()->update('faturas_semanal', [
            'transacao_id' => $result['id'],
            'gateway_pagamento' => $this->gateway,
            'pix_qrcode_imagem' => $qrCodePath,
            'pix_payload' => $pixData['payload'] ?? null,
            'pix_txid' => $pixData['txid'] ?? null
        ], 'id = :id', ['id' => $fatura['id']]);
        
        return array_merge($result, $pixData);
    }
    
    /**
     * Salva imagem do QR Code
     */
    private function salvarQrCode($base64Image, $numeroFatura) {
        $imageData = base64_decode($base64Image);
        $filename = 'pix_' . $numeroFatura . '.png';
        $filepath = UPLOADS_PATH . '/qrcodes/' . $filename;
        
        if (!is_dir(UPLOADS_PATH . '/qrcodes')) {
            mkdir(UPLOADS_PATH . '/qrcodes', 0755, true);
        }
        
        file_put_contents($filepath, $imageData);
        
        return 'uploads/qrcodes/' . $filename;
    }
    
    /**
     * Consulta status do pagamento
     */
    public function consultarPagamento($transacaoId) {
        return $this->request("/payments/{$transacaoId}");
    }
    
    /**
     * Cancela cobrança
     */
    public function cancelarCobranca($transacaoId) {
        return $this->request("/payments/{$transacaoId}", 'DELETE');
    }
    
    /**
     * Reembolsa pagamento
     */
    public function reembolsarPagamento($transacaoId) {
        return $this->request("/payments/{$transacaoId}/refund", 'POST');
    }
    
    /**
     * Processa webhook
     */
    public function processarWebhook($data) {
        $event = $data['event'] ?? '';
        $payment = $data['payment'] ?? [];
        
        if (empty($payment)) {
            return ['sucesso' => false, 'mensagem' => 'Dados de pagamento não encontrados.'];
        }
        
        $transacaoId = $payment['id'];
        $status = $payment['status'];
        $externalReference = $payment['externalReference'];
        
        // Busca fatura
        $fatura = DB()->fetch("SELECT * FROM faturas_semanal WHERE transacao_id = ? OR numero_fatura = ?", 
            [$transacaoId, $externalReference]);
        
        if (!$fatura) {
            return ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.'];
        }
        
        try {
            DB()->beginTransaction();
            
            switch ($event) {
                case 'PAYMENT_RECEIVED':
                case 'PAYMENT_CONFIRMED':
                    // Pagamento confirmado
                    DB()->update('faturas_semanal', [
                        'status' => 'pago',
                        'data_pagamento' => date('Y-m-d'),
                        'forma_pagamento' => strtolower($payment['billingType'])
                    ], 'id = :id', ['id' => $fatura['id']]);
                    
                    // Atualiza contrato
                    $contrato = DB()->fetch("SELECT * FROM contratos_semanal WHERE id = ?", [$fatura['contrato_id']]);
                    if ($contrato) {
                        DB()->update('contratos_semanal', [
                            'semanas_pagas' => $contrato['semanas_pagas'] + 1,
                            'semanas_pendentes' => max(0, $contrato['semanas_pendentes'] - 1)
                        ], 'id = :id', ['id' => $contrato['id']]);
                    }
                    
                    // Desbloqueia cliente se estiver bloqueado
                    $cliente = DB()->fetch("SELECT * FROM clientes WHERE id = ?", [$fatura['cliente_id']]);
                    if ($cliente && $cliente['status'] == 'bloqueado') {
                        desbloquearCliente($fatura['cliente_id'], $fatura['id']);
                    }
                    
                    // Registra log
                    registrarLog(
                        $fatura['id'],
                        $fatura['contrato_id'],
                        $fatura['cliente_id'],
                        'pagamento',
                        "Pagamento confirmado via {$payment['billingType']}",
                        $payment,
                        'webhook'
                    );
                    
                    // Cria notificação
                    criarNotificacao(
                        $fatura['cliente_id'],
                        null,
                        'Pagamento Confirmado',
                        "Seu pagamento da fatura #{$fatura['numero_fatura']} foi confirmado!",
                        'pagamento'
                    );
                    
                    break;
                    
                case 'PAYMENT_OVERDUE':
                    // Pagamento vencido
                    DB()->update('faturas_semanal', [
                        'status' => 'vencido'
                    ], 'id = :id', ['id' => $fatura['id']]);
                    
                    registrarLog(
                        $fatura['id'],
                        $fatura['contrato_id'],
                        $fatura['cliente_id'],
                        'vencimento',
                        'Pagamento vencido (webhook)',
                        $payment,
                        'webhook'
                    );
                    
                    break;
                    
                case 'PAYMENT_DELETED':
                case 'PAYMENT_CANCELLED':
                    // Pagamento cancelado
                    DB()->update('faturas_semanal', [
                        'status' => 'cancelado'
                    ], 'id = :id', ['id' => $fatura['id']]);
                    
                    registrarLog(
                        $fatura['id'],
                        $fatura['contrato_id'],
                        $fatura['cliente_id'],
                        'cancelamento',
                        'Pagamento cancelado (webhook)',
                        $payment,
                        'webhook'
                    );
                    
                    break;
            }
            
            DB()->commit();
            
            return ['sucesso' => true, 'mensagem' => 'Webhook processado com sucesso.'];
            
        } catch (Exception $e) {
            DB()->rollback();
            error_log("Erro ao processar webhook: " . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }
}

/**
 * Gera boleto para fatura
 */
function gerarBoletoFatura($faturaId) {
    try {
        $fatura = DB()->fetch("
            SELECT f.*, c.nome, c.email, c.cpf_cnpj, c.telefone, c.celular,
                   c.endereco, c.numero, c.complemento, c.bairro, c.cep
            FROM faturas_semanal f
            JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = ?
        ", [$faturaId]);
        
        if (!$fatura) {
            return ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.'];
        }
        
        if ($fatura['status'] == 'pago') {
            return ['sucesso' => false, 'mensagem' => 'Esta fatura já foi paga.'];
        }
        
        $gateway = new GatewayPagamento();
        
        // Cria/atualiza cliente no gateway
        $clienteGateway = $gateway->criarCliente($fatura);
        
        // Gera boleto
        $boleto = $gateway->gerarBoleto($fatura, $clienteGateway['id']);
        
        // Registra log
        registrarLog(
            $faturaId,
            $fatura['contrato_id'],
            $fatura['cliente_id'],
            'geracao',
            'Boleto gerado com sucesso'
        );
        
        return [
            'sucesso' => true,
            'mensagem' => 'Boleto gerado com sucesso.',
            'boleto_url' => $boleto['bankSlipUrl'],
            'linha_digitavel' => $boleto['identificationField']
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao gerar boleto: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}

/**
 * Gera PIX para fatura
 */
function gerarPixFatura($faturaId) {
    try {
        $fatura = DB()->fetch("
            SELECT f.*, c.nome, c.email, c.cpf_cnpj, c.telefone, c.celular,
                   c.endereco, c.numero, c.complemento, c.bairro, c.cep
            FROM faturas_semanal f
            JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = ?
        ", [$faturaId]);
        
        if (!$fatura) {
            return ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.'];
        }
        
        if ($fatura['status'] == 'pago') {
            return ['sucesso' => false, 'mensagem' => 'Esta fatura já foi paga.'];
        }
        
        $gateway = new GatewayPagamento();
        
        // Cria/atualiza cliente no gateway
        $clienteGateway = $gateway->criarCliente($fatura);
        
        // Gera PIX
        $pix = $gateway->gerarPix($fatura, $clienteGateway['id']);
        
        // Registra log
        registrarLog(
            $faturaId,
            $fatura['contrato_id'],
            $fatura['cliente_id'],
            'geracao',
            'PIX gerado com sucesso'
        );
        
        return [
            'sucesso' => true,
            'mensagem' => 'PIX gerado com sucesso.',
            'qrcode' => $pix['encodedImage'],
            'payload' => $pix['payload'],
            'qrcode_url' => UPLOADS_URL . '/' . $pix['qrCodePath'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao gerar PIX: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}

/**
 * Consulta status de pagamento
 */
function consultarStatusPagamento($faturaId) {
    try {
        $fatura = DB()->fetch("SELECT * FROM faturas_semanal WHERE id = ?", [$faturaId]);
        
        if (!$fatura || empty($fatura['transacao_id'])) {
            return ['sucesso' => false, 'mensagem' => 'Transação não encontrada.'];
        }
        
        $gateway = new GatewayPagamento();
        $result = $gateway->consultarPagamento($fatura['transacao_id']);
        
        return [
            'sucesso' => true,
            'status' => $result['status'],
            'dados' => $result
        ];
        
    } catch (Exception $e) {
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}
