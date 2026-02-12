<?php
/**
 * Master Car - Funções de Cobrança Semanal
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Gera cobranças semanais para contratos ativos
 * Executado via CRON
 */
function gerarCobrancasSemanais($dataReferencia = null) {
    $dataReferencia = $dataReferencia ?: date('Y-m-d');
    $cobrancasGeradas = 0;
    $erros = [];
    
    try {
        // Busca contratos ativos que precisam de cobrança
        $contratos = DB()->fetchAll("
            SELECT cs.*, 
                   c.nome as cliente_nome, 
                   c.email as cliente_email,
                   c.dias_tolerancia,
                   v.placa as veiculo_placa,
                   v.modelo as veiculo_modelo
            FROM contratos_semanal cs
            JOIN clientes c ON cs.cliente_id = c.id
            JOIN veiculos v ON cs.veiculo_id = v.id
            WHERE cs.status = 'ativo'
            AND cs.recorrencia_ativa = 1
            AND cs.data_proxima_cobranca <= ?
        ", [$dataReferencia]);
        
        foreach ($contratos as $contrato) {
            try {
                DB()->beginTransaction();
                
                // Verifica se já existe fatura para esta semana
                $faturaExistente = DB()->fetch("
                    SELECT id FROM faturas_semanal 
                    WHERE contrato_id = ? 
                    AND data_referencia = ?
                    AND status != 'cancelado'
                ", [$contrato['id'], $contrato['data_proxima_cobranca']]);
                
                if ($faturaExistente) {
                    DB()->rollback();
                    continue;
                }
                
                // Calcula datas
                $dataVencimento = calcularDataVencimento(
                    $contrato['data_proxima_cobranca'], 
                    $contrato['dias_tolerancia'] ?? DIAS_TOLERANCIA_PADRAO
                );
                
                $dataBloqueio = calcularDataBloqueio($dataVencimento);
                
                // Calcula valores
                $valorOriginal = $contrato['valor_semanal'];
                $valorTotal = $valorOriginal;
                
                // Gera número da fatura
                $numeroFatura = gerarNumeroFatura();
                
                // Calcula semana de referência
                $semanaReferencia = calcularSemanaReferencia($contrato['data_inicio'], $contrato['data_proxima_cobranca']);
                
                // Cria a fatura
                $faturaData = [
                    'contrato_id' => $contrato['id'],
                    'cliente_id' => $contrato['cliente_id'],
                    'numero_fatura' => $numeroFatura,
                    'semana_referencia' => $semanaReferencia,
                    'data_referencia' => $contrato['data_proxima_cobranca'],
                    'valor_original' => $valorOriginal,
                    'valor_multa' => 0,
                    'valor_juros' => 0,
                    'valor_desconto' => 0,
                    'valor_total' => $valorTotal,
                    'data_emissao' => date('Y-m-d'),
                    'data_vencimento' => $dataVencimento,
                    'data_bloqueio' => $dataBloqueio,
                    'status' => 'pendente'
                ];
                
                $faturaId = DB()->insert('faturas_semanal', $faturaData);
                
                // Atualiza contrato
                $proximaCobranca = date('Y-m-d', strtotime($contrato['data_proxima_cobranca'] . ' +7 days'));
                
                DB()->update('contratos_semanal', [
                    'data_ultima_cobranca' => $contrato['data_proxima_cobranca'],
                    'data_proxima_cobranca' => $proximaCobranca,
                    'total_semanas' => $contrato['total_semanas'] + 1,
                    'semanas_pendentes' => $contrato['semanas_pendentes'] + 1
                ], 'id = :id', ['id' => $contrato['id']]);
                
                // Registra log
                registrarLog(
                    $faturaId,
                    $contrato['id'],
                    $contrato['cliente_id'],
                    'geracao',
                    "Cobrança semanal gerada - Fatura #{$numeroFatura}",
                    $faturaData
                );
                
                // Cria notificação para o cliente
                criarNotificacao(
                    $contrato['cliente_id'],
                    null,
                    'Nova Cobrança',
                    "Sua cobrança semanal #{$numeroFatura} no valor de " . formatarMoeda($valorTotal) . " foi gerada. Vencimento: " . formatarData($dataVencimento),
                    'cobranca',
                    '/cliente/faturas.php?id=' . $faturaId
                );
                
                DB()->commit();
                $cobrancasGeradas++;
                
            } catch (Exception $e) {
                DB()->rollback();
                $erros[] = "Contrato {$contrato['numero_contrato']}: " . $e->getMessage();
                error_log("Erro ao gerar cobrança para contrato {$contrato['id']}: " . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro ao gerar cobranças semanais: " . $e->getMessage());
        return [
            'sucesso' => false,
            'mensagem' => $e->getMessage(),
            'cobrancas_geradas' => $cobrancasGeradas,
            'erros' => $erros
        ];
    }
    
    return [
        'sucesso' => true,
        'cobrancas_geradas' => $cobrancasGeradas,
        'erros' => $erros
    ];
}

/**
 * Calcula data de vencimento
 */
function calcularDataVencimento($dataReferencia, $diasTolerancia) {
    return date('Y-m-d', strtotime($dataReferencia . " +{$diasTolerancia} days"));
}

/**
 * Calcula data de bloqueio
 */
function calcularDataBloqueio($dataVencimento) {
    $diasBloqueio = DIAS_BLOQUEIO_PADRAO;
    return date('Y-m-d', strtotime($dataVencimento . " +{$diasBloqueio} days"));
}

/**
 * Calcula semana de referência
 */
function calcularSemanaReferencia($dataInicio, $dataReferencia) {
    $inicio = new DateTime($dataInicio);
    $referencia = new DateTime($dataReferencia);
    $diferenca = $inicio->diff($referencia);
    return floor($diferenca->days / 7) + 1;
}

/**
 * Processa faturas vencidas
 */
function processarFaturasVencidas() {
    $hoje = date('Y-m-d');
    $processadas = 0;
    
    try {
        // Busca faturas pendentes que venceram
        $faturas = DB()->fetchAll("
            SELECT f.*, c.nome as cliente_nome, cs.numero_contrato
            FROM faturas_semanal f
            JOIN clientes c ON f.cliente_id = c.id
            JOIN contratos_semanal cs ON f.contrato_id = cs.id
            WHERE f.status = 'pendente'
            AND f.data_vencimento < ?
        ", [$hoje]);
        
        foreach ($faturas as $fatura) {
            try {
                DB()->beginTransaction();
                
                // Calcula dias de atraso
                $diasAtraso = diasEntre($fatura['data_vencimento'], $hoje);
                
                // Calcula multa e juros
                $config = DB()->fetch("SELECT * FROM config_pagamento WHERE ativo = 1 LIMIT 1");
                $multaPercentual = $config['multa_percentual'] ?? MULTA_ATRASO_PADRAO;
                $jurosPercentual = $config['juros_percentual'] ?? JUROS_DIA_ATRASO_PADRAO;
                
                $valorMulta = $fatura['valor_original'] * ($multaPercentual / 100);
                $valorJuros = $fatura['valor_original'] * ($jurosPercentual / 100) * $diasAtraso;
                
                $valorTotal = $fatura['valor_original'] + $valorMulta + $valorJuros;
                
                // Atualiza fatura
                DB()->update('faturas_semanal', [
                    'status' => 'vencido',
                    'valor_multa' => $valorMulta,
                    'valor_juros' => $valorJuros,
                    'valor_total' => $valorTotal
                ], 'id = :id', ['id' => $fatura['id']]);
                
                // Registra log
                registrarLog(
                    $fatura['id'],
                    $fatura['contrato_id'],
                    $fatura['cliente_id'],
                    'vencimento',
                    "Fatura #{$fatura['numero_fatura']} vencida. Dias de atraso: {$diasAtraso}",
                    ['dias_atraso' => $diasAtraso, 'multa' => $valorMulta, 'juros' => $valorJuros]
                );
                
                // Verifica se precisa bloquear
                if ($hoje >= $fatura['data_bloqueio']) {
                    bloquearClientePorFatura($fatura);
                }
                
                DB()->commit();
                $processadas++;
                
            } catch (Exception $e) {
                DB()->rollback();
                error_log("Erro ao processar fatura vencida {$fatura['id']}: " . $e->getMessage());
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar faturas vencidas: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
    
    return [
        'sucesso' => true,
        'processadas' => $processadas
    ];
}

/**
 * Bloqueia cliente por fatura em atraso
 */
function bloquearClientePorFatura($fatura) {
    try {
        // Bloqueia cliente
        DB()->update('clientes', [
            'status' => 'bloqueado',
            'bloqueado_ate' => null
        ], 'id = :id', ['id' => $fatura['cliente_id']]);
        
        // Bloqueia veículo
        $contrato = DB()->fetch("SELECT veiculo_id FROM contratos_semanal WHERE id = ?", [$fatura['contrato_id']]);
        if ($contrato) {
            DB()->update('veiculos', [
                'status' => 'bloqueado'
            ], 'id = :id', ['id' => $contrato['veiculo_id']]);
        }
        
        // Atualiza fatura
        DB()->update('faturas_semanal', [
            'status' => 'bloqueado'
        ], 'id = :id', ['id' => $fatura['id']]);
        
        // Atualiza contrato
        DB()->update('contratos_semanal', [
            'status' => 'suspenso',
            'motivo_bloqueio' => 'Inadimplência - Fatura #' . $fatura['numero_fatura'],
            'data_bloqueio' => date('Y-m-d')
        ], 'id = :id', ['id' => $fatura['contrato_id']]);
        
        // Registra log
        registrarLog(
            $fatura['id'],
            $fatura['contrato_id'],
            $fatura['cliente_id'],
            'bloqueio',
            "Cliente bloqueado por inadimplência - Fatura #{$fatura['numero_fatura']}"
        );
        
        // Cria notificação
        criarNotificacao(
            $fatura['cliente_id'],
            null,
            'Conta Bloqueada',
            "Sua conta foi bloqueada devido à inadimplência da fatura #{$fatura['numero_fatura']}. Regularize seu pagamento para desbloqueio.",
            'bloqueio'
        );
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao bloquear cliente: " . $e->getMessage());
        return false;
    }
}

/**
 * Desbloqueia cliente após pagamento
 */
function desbloquearCliente($clienteId, $faturaId = null) {
    try {
        DB()->beginTransaction();
        
        // Desbloqueia cliente
        DB()->update('clientes', [
            'status' => 'ativo',
            'bloqueado_ate' => null
        ], 'id = :id', ['id' => $clienteId]);
        
        // Busca contratos suspensos
        $contratos = DB()->fetchAll("
            SELECT * FROM contratos_semanal 
            WHERE cliente_id = ? AND status = 'suspenso'
        ", [$clienteId]);
        
        foreach ($contratos as $contrato) {
            // Desbloqueia veículo
            DB()->update('veiculos', [
                'status' => 'alugado'
            ], 'id = :id', ['id' => $contrato['veiculo_id']]);
            
            // Reativa contrato
            DB()->update('contratos_semanal', [
                'status' => 'ativo',
                'motivo_bloqueio' => null,
                'data_bloqueio' => null
            ], 'id = :id', ['id' => $contrato['id']]);
        }
        
        // Registra log
        registrarLog(
            $faturaId,
            null,
            $clienteId,
            'desbloqueio',
            'Cliente desbloqueado após pagamento'
        );
        
        // Cria notificação
        criarNotificacao(
            $clienteId,
            null,
            'Conta Desbloqueada',
            'Sua conta foi desbloqueada. Obrigado pelo pagamento!',
            'sistema'
        );
        
        DB()->commit();
        return true;
        
    } catch (Exception $e) {
        DB()->rollback();
        error_log("Erro ao desbloquear cliente: " . $e->getMessage());
        return false;
    }
}

/**
 * Reprocessa uma cobrança
 */
function reprocessarCobranca($faturaId) {
    try {
        $fatura = DB()->fetch("SELECT * FROM faturas_semanal WHERE id = ?", [$faturaId]);
        
        if (!$fatura) {
            return ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.'];
        }
        
        if ($fatura['status'] == 'pago') {
            return ['sucesso' => false, 'mensagem' => 'Não é possível reprocessar uma fatura paga.'];
        }
        
        DB()->beginTransaction();
        
        // Recalcula valores
        $hoje = date('Y-m-d');
        $diasAtraso = 0;
        $valorMulta = 0;
        $valorJuros = 0;
        
        if ($fatura['data_vencimento'] < $hoje) {
            $diasAtraso = diasEntre($fatura['data_vencimento'], $hoje);
            
            $config = DB()->fetch("SELECT * FROM config_pagamento WHERE ativo = 1 LIMIT 1");
            $multaPercentual = $config['multa_percentual'] ?? MULTA_ATRASO_PADRAO;
            $jurosPercentual = $config['juros_percentual'] ?? JUROS_DIA_ATRASO_PADRAO;
            
            $valorMulta = $fatura['valor_original'] * ($multaPercentual / 100);
            $valorJuros = $fatura['valor_original'] * ($jurosPercentual / 100) * $diasAtraso;
        }
        
        $valorTotal = $fatura['valor_original'] + $valorMulta + $valorJuros;
        
        // Atualiza fatura
        DB()->update('faturas_semanal', [
            'valor_multa' => $valorMulta,
            'valor_juros' => $valorJuros,
            'valor_total' => $valorTotal,
            'status' => $diasAtraso > 0 ? 'vencido' : 'pendente'
        ], 'id = :id', ['id' => $faturaId]);
        
        // Registra log
        registrarLog(
            $faturaId,
            $fatura['contrato_id'],
            $fatura['cliente_id'],
            'reprocessamento',
            "Fatura reprocessada. Novo valor: " . formatarMoeda($valorTotal),
            ['dias_atraso' => $diasAtraso, 'multa' => $valorMulta, 'juros' => $valorJuros]
        );
        
        DB()->commit();
        
        return [
            'sucesso' => true,
            'mensagem' => 'Cobrança reprocessada com sucesso.',
            'novo_valor' => $valorTotal
        ];
        
    } catch (Exception $e) {
        DB()->rollback();
        error_log("Erro ao reprocessar cobrança: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}

/**
 * Cancela uma cobrança
 */
function cancelarCobranca($faturaId, $motivo = '') {
    try {
        $fatura = DB()->fetch("SELECT * FROM faturas_semanal WHERE id = ?", [$faturaId]);
        
        if (!$fatura) {
            return ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.'];
        }
        
        if ($fatura['status'] == 'pago') {
            return ['sucesso' => false, 'mensagem' => 'Não é possível cancelar uma fatura paga.'];
        }
        
        DB()->beginTransaction();
        
        // Atualiza fatura
        DB()->update('faturas_semanal', [
            'status' => 'cancelado',
            'motivo_cancelamento' => $motivo
        ], 'id = :id', ['id' => $faturaId]);
        
        // Atualiza contrato
        $contrato = DB()->fetch("SELECT * FROM contratos_semanal WHERE id = ?", [$fatura['contrato_id']]);
        if ($contrato) {
            DB()->update('contratos_semanal', [
                'semanas_pendentes' => max(0, $contrato['semanas_pendentes'] - 1)
            ], 'id = :id', ['id' => $contrato['id']]);
        }
        
        // Registra log
        registrarLog(
            $faturaId,
            $fatura['contrato_id'],
            $fatura['cliente_id'],
            'cancelamento',
            "Fatura cancelada. Motivo: {$motivo}"
        );
        
        DB()->commit();
        
        return ['sucesso' => true, 'mensagem' => 'Cobrança cancelada com sucesso.'];
        
    } catch (Exception $e) {
        DB()->rollback();
        error_log("Erro ao cancelar cobrança: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}

/**
 * Exclui uma cobrança (apenas master)
 */
function excluirCobranca($faturaId) {
    try {
        $fatura = DB()->fetch("SELECT * FROM faturas_semanal WHERE id = ?", [$faturaId]);
        
        if (!$fatura) {
            return ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.'];
        }
        
        DB()->beginTransaction();
        
        // Remove fatura
        DB()->delete('faturas_semanal', 'id = ?', [$faturaId]);
        
        // Registra log
        registrarLog(
            null,
            $fatura['contrato_id'],
            $fatura['cliente_id'],
            'cancelamento',
            "Fatura #{$fatura['numero_fatura']} excluída permanentemente"
        );
        
        DB()->commit();
        
        return ['sucesso' => true, 'mensagem' => 'Cobrança excluída com sucesso.'];
        
    } catch (Exception $e) {
        DB()->rollback();
        error_log("Erro ao excluir cobrança: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => $e->getMessage()];
    }
}

/**
 * Cria notificação
 */
function criarNotificacao($clienteId, $usuarioId, $titulo, $mensagem, $tipo = 'sistema', $link = null) {
    try {
        DB()->insert('notificacoes', [
            'cliente_id' => $clienteId,
            'usuario_id' => $usuarioId,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'tipo' => $tipo,
            'link' => $link
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Erro ao criar notificação: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém resumo financeiro
 */
function obterResumoFinanceiro($mes = null, $ano = null) {
    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');
    
    try {
        // Total recebido no mês
        $recebido = DB()->fetch("
            SELECT COALESCE(SUM(valor_total), 0) as total
            FROM faturas_semanal
            WHERE status = 'pago'
            AND MONTH(data_pagamento) = ?
            AND YEAR(data_pagamento) = ?
        ", [$mes, $ano]);
        
        // Total a receber
        $receber = DB()->fetch("
            SELECT COALESCE(SUM(valor_total), 0) as total
            FROM faturas_semanal
            WHERE status IN ('pendente', 'vencido')
        ");
        
        // Total em atraso
        $atraso = DB()->fetch("
            SELECT COALESCE(SUM(valor_total), 0) as total,
                   COUNT(*) as quantidade
            FROM faturas_semanal
            WHERE status = 'vencido'
        ");
        
        // Faturas do mês
        $faturasMes = DB()->fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as pagas,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 'vencido' THEN 1 ELSE 0 END) as vencidas
            FROM faturas_semanal
            WHERE MONTH(data_emissao) = ?
            AND YEAR(data_emissao) = ?
        ", [$mes, $ano]);
        
        return [
            'recebido' => $recebido['total'],
            'receber' => $receber['total'],
            'atraso' => $atraso['total'],
            'quantidade_atraso' => $atraso['quantidade'],
            'faturas_mes' => $faturasMes
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao obter resumo financeiro: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtém inadimplência semanal
 */
function obterInadimplenciaSemanal() {
    try {
        return DB()->fetchAll("
            SELECT 
                f.*,
                c.nome as cliente_nome,
                c.email as cliente_email,
                c.telefone as cliente_telefone,
                cs.numero_contrato,
                v.placa as veiculo_placa,
                v.modelo as veiculo_modelo,
                DATEDIFF(CURDATE(), f.data_vencimento) as dias_atraso
            FROM faturas_semanal f
            JOIN clientes c ON f.cliente_id = c.id
            JOIN contratos_semanal cs ON f.contrato_id = cs.id
            JOIN veiculos v ON cs.veiculo_id = v.id
            WHERE f.status IN ('pendente', 'vencido')
            AND f.data_vencimento < CURDATE()
            ORDER BY f.data_vencimento ASC
        ");
    } catch (Exception $e) {
        error_log("Erro ao obter inadimplência: " . $e->getMessage());
        return [];
    }
}
