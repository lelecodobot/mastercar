<?php
/**
 * Master Car - Visualização de Fatura do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/gateway.php';

protegerCliente();

$cliente = clienteAtual();
$faturaId = $_GET['id'] ?? 0;

// Busca fatura
$fatura = DB()->fetch("
    SELECT f.*, cs.numero_contrato, v.placa, v.marca, v.modelo
    FROM faturas_semanal f
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE f.id = ? AND f.cliente_id = ?
", [$faturaId, $cliente['id']]);

if (!$fatura) {
    mostrarAlerta('Fatura não encontrada.', 'danger');
    redirecionar('/cliente/faturas.php');
}

// Gera boleto se não existir
if (($fatura['status'] == 'pendente' || $fatura['status'] == 'vencido') && empty($fatura['boleto_url'])) {
    try {
        $resultado = gerarBoletoFatura($fatura['id']);
        if ($resultado['sucesso']) {
            $fatura['boleto_url'] = $resultado['boleto_url'];
            $fatura['boleto_linha_digitavel'] = $resultado['linha_digitavel'];
        }
    } catch (Exception $e) {
        // Ignora erro
    }
}

// Gera PIX se não existir
if (($fatura['status'] == 'pendente' || $fatura['status'] == 'vencido') && empty($fatura['pix_payload'])) {
    try {
        $resultado = gerarPixFatura($fatura['id']);
        if ($resultado['sucesso']) {
            $fatura['pix_payload'] = $resultado['payload'];
            $fatura['pix_qrcode'] = $resultado['qrcode'];
        }
    } catch (Exception $e) {
        // Ignora erro
    }
}

$diasAtraso = 0;
if (($fatura['status'] == 'vencido' || ($fatura['status'] == 'pendente' && $fatura['data_vencimento'] < date('Y-m-d')))) {
    $diasAtraso = diasEntre($fatura['data_vencimento'], date('Y-m-d'));
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura <?php echo $fatura['numero_fatura']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cliente-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
        }
        .fatura-box {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .fatura-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }
        .fatura-valor {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark);
        }
        .fatura-valor.vencido {
            color: var(--danger);
        }
        .fatura-valor.pago {
            color: var(--success);
        }
        .pagamento-opcao {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 15px;
        }
        .pagamento-opcao h4 {
            margin: 0 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qrcode-box {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
        }
        .qrcode-box img {
            max-width: 200px;
        }
        .linha-digitavel {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            font-family: monospace;
            font-size: 16px;
            text-align: center;
            letter-spacing: 1px;
        }
    </style>
</head>
<body style="background: #f1f5f9;">
    <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
        
        <!-- Header -->
        <div class="cliente-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-file-invoice"></i> Fatura</h2>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/cliente/faturas.php" style="color: white; opacity: 0.9;">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($alerta): ?>
            <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                <span><?php echo $alerta['mensagem']; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Fatura -->
        <div class="fatura-box">
            <div class="fatura-header">
                <div>
                    <h3 style="margin: 0 0 10px;"><?php echo $fatura['numero_fatura']; ?></h3>
                    <p style="margin: 0; color: var(--gray);">
                        Contrato: <?php echo $fatura['numero_contrato']; ?> | 
                        Veículo: <?php echo $fatura['placa'] . ' - ' . $fatura['marca'] . ' ' . $fatura['modelo']; ?>
                    </p>
                    <p style="margin: 5px 0 0; color: var(--gray);">
                        Semana de referência: <?php echo $fatura['semana_referencia']; ?> | 
                        Emissão: <?php echo formatarData($fatura['data_emissao']); ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <?php 
                    $statusClass = $STATUS_FATURA[$fatura['status']]['class'] ?? 'secondary';
                    $statusLabel = $STATUS_FATURA[$fatura['status']]['label'] ?? ucfirst($fatura['status']);
                    ?>
                    <span class="badge badge-<?php echo $statusClass; ?>" style="font-size: 14px; padding: 8px 15px;">
                        <?php echo $statusLabel; ?>
                    </span>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <div>
                    <div style="color: var(--gray); font-size: 13px; margin-bottom: 5px;">Valor Original</div>
                    <div style="font-size: 18px;"><?php echo formatarMoeda($fatura['valor_original']); ?></div>
                </div>
                <?php if ($fatura['valor_multa'] > 0): ?>
                    <div>
                        <div style="color: var(--danger); font-size: 13px; margin-bottom: 5px;">Multa</div>
                        <div style="font-size: 18px; color: var(--danger);">+ <?php echo formatarMoeda($fatura['valor_multa']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($fatura['valor_juros'] > 0): ?>
                    <div>
                        <div style="color: var(--danger); font-size: 13px; margin-bottom: 5px;">Juros</div>
                        <div style="font-size: 18px; color: var(--danger);">+ <?php echo formatarMoeda($fatura['valor_juros']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($fatura['valor_desconto'] > 0): ?>
                    <div>
                        <div style="color: var(--success); font-size: 13px; margin-bottom: 5px;">Desconto</div>
                        <div style="font-size: 18px; color: var(--success);">- <?php echo formatarMoeda($fatura['valor_desconto']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="background: var(--light); padding: 20px; border-radius: var(--border-radius); text-align: center;">
                <div style="color: var(--gray); font-size: 13px; margin-bottom: 10px;">VALOR TOTAL</div>
                <div class="fatura-valor <?php echo $fatura['status']; ?>">
                    <?php echo formatarMoeda($fatura['valor_total']); ?>
                </div>
                <?php if ($diasAtraso > 0): ?>
                    <div style="color: var(--danger); margin-top: 10px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Fatura vencida há <?php echo $diasAtraso; ?> dias
                    </div>
                <?php endif; ?>
                <?php if ($fatura['data_pagamento']): ?>
                    <div style="color: var(--success); margin-top: 10px;">
                        <i class="fas fa-check-circle"></i>
                        Pago em <?php echo formatarData($fatura['data_pagamento']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Opções de Pagamento -->
        <?php if ($fatura['status'] == 'pendente' || $fatura['status'] == 'vencido'): ?>
            
            <!-- PIX -->
            <div class="pagamento-opcao">
                <h4><i class="fas fa-qrcode" style="color: var(--success);"></i> Pagar com PIX</h4>
                <?php if (!empty($fatura['pix_qrcode'])): ?>
                    <div class="qrcode-box">
                        <img src="data:image/png;base64,<?php echo $fatura['pix_qrcode']; ?>" alt="QR Code PIX">
                        <p style="margin: 15px 0 0; font-size: 13px; color: var(--gray);">
                            Escaneie o QR Code com seu aplicativo bancário
                        </p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($fatura['pix_payload'])): ?>
                    <div style="margin-top: 15px;">
                        <p style="font-size: 13px; color: var(--gray); margin-bottom: 10px;">Ou copie o código PIX:</p>
                        <div class="linha-digitavel" style="font-size: 12px; word-break: break-all;">
                            <?php echo $fatura['pix_payload']; ?>
                        </div>
                        <button class="btn btn-primary" style="margin-top: 10px; width: 100%;" onclick="copiarTexto('<?php echo $fatura['pix_payload']; ?>')">
                            <i class="fas fa-copy"></i> Copiar Código PIX
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Boleto -->
            <div class="pagamento-opcao">
                <h4><i class="fas fa-barcode" style="color: var(--primary);"></i> Pagar com Boleto</h4>
                <?php if (!empty($fatura['boleto_linha_digitavel'])): ?>
                    <div class="linha-digitavel">
                        <?php echo $fatura['boleto_linha_digitavel']; ?>
                    </div>
                    <button class="btn btn-primary" style="margin-top: 10px; width: 100%;" onclick="copiarTexto('<?php echo $fatura['boleto_linha_digitavel']; ?>')">
                        <i class="fas fa-copy"></i> Copiar Linha Digitável
                    </button>
                <?php endif; ?>
                <?php if (!empty($fatura['boleto_url'])): ?>
                    <a href="<?php echo $fatura['boleto_url']; ?>" target="_blank" class="btn btn-secondary" style="margin-top: 10px; width: 100%;">
                        <i class="fas fa-download"></i> Baixar Boleto
                    </a>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>
        
        <!-- Histórico -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> Histórico</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-date"><?php echo formatarDataHora($fatura['created_at']); ?></div>
                        <div class="timeline-title">Fatura Gerada</div>
                        <div class="timeline-desc">Fatura #<?php echo $fatura['numero_fatura']; ?> foi gerada no sistema.</div>
                    </div>
                    <?php if ($fatura['data_pagamento']): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo formatarData($fatura['data_pagamento']); ?></div>
                            <div class="timeline-title">Pagamento Confirmado</div>
                            <div class="timeline-desc">Pagamento recebido via <?php echo strtoupper($fatura['forma_pagamento']); ?>.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
