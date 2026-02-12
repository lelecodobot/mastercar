<?php
/**
 * Master Car - Visualizar Cobrança/Fatura
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/gateway.php';

protegerAdmin();

$id = $_GET['id'] ?? 0;

$fatura = DB()->fetch("
    SELECT f.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone as cliente_telefone,
           cs.numero_contrato, v.placa, v.marca, v.modelo
    FROM faturas_semanal f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE f.id = ?
", [$id]);

if (!$fatura) {
    mostrarAlerta('Fatura não encontrada.', 'danger');
    redirecionar('/admin/cobrancas/');
}

// Gera boleto/PIX se não existir
if (($fatura['status'] == 'pendente' || $fatura['status'] == 'vencido') && empty($fatura['boleto_url'])) {
    try {
        $resultado = gerarBoletoFatura($fatura['id']);
        if ($resultado['sucesso']) {
            $fatura['boleto_url'] = $resultado['boleto_url'];
            $fatura['boleto_linha_digitavel'] = $resultado['linha_digitavel'];
        }
    } catch (Exception $e) {}
}

if (($fatura['status'] == 'pendente' || $fatura['status'] == 'vencido') && empty($fatura['pix_payload'])) {
    try {
        $resultado = gerarPixFatura($fatura['id']);
        if ($resultado['sucesso']) {
            $fatura['pix_payload'] = $resultado['payload'];
            $fatura['pix_qrcode'] = $resultado['qrcode'];
        }
    } catch (Exception $e) {}
}

$diasAtraso = 0;
if ($fatura['status'] == 'vencido' || ($fatura['status'] == 'pendente' && $fatura['data_vencimento'] < date('Y-m-d'))) {
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
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php if ($alerta): ?>
                <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                    <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo $alerta['mensagem']; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Fatura <?php echo $fatura['numero_fatura']; ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/">Cobranças</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Visualizar</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/?acao=reprocessar&id=<?php echo $id; ?>" class="btn btn-warning" onclick="return confirm('Reprocessar esta cobrança?')">
                        <i class="fas fa-sync"></i> Reprocessar
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Dados da Fatura -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-invoice"></i> Dados da Fatura</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">STATUS</div>
                            <span class="badge badge-<?php echo $STATUS_FATURA[$fatura['status']]['class'] ?? 'secondary'; ?>" style="font-size: 14px;">
                                <?php echo $STATUS_FATURA[$fatura['status']]['label'] ?? $fatura['status']; ?>
                            </span>
                            <?php if ($diasAtraso > 0): ?>
                                <span class="badge badge-danger"><?php echo $diasAtraso; ?> dias de atraso</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">VALOR ORIGINAL</div>
                            <div><?php echo formatarMoeda($fatura['valor_original']); ?></div>
                        </div>
                        <?php if ($fatura['valor_multa'] > 0): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="color: var(--gray); font-size: 12px;">MULTA</div>
                                <div style="color: var(--danger);">+ <?php echo formatarMoeda($fatura['valor_multa']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($fatura['valor_juros'] > 0): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="color: var(--gray); font-size: 12px;">JUROS</div>
                                <div style="color: var(--danger);">+ <?php echo formatarMoeda($fatura['valor_juros']); ?></div>
                            </div>
                        <?php endif; ?>
                        <div style="background: var(--light); padding: 15px; border-radius: var(--border-radius); margin-top: 20px;">
                            <div style="color: var(--gray); font-size: 12px;">VALOR TOTAL</div>
                            <div style="font-size: 24px; font-weight: 700; color: var(--primary);"><?php echo formatarMoeda($fatura['valor_total']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Cliente -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Cliente</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">NOME</div>
                            <div style="font-weight: 600;"><?php echo $fatura['cliente_nome']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">E-MAIL</div>
                            <div><?php echo $fatura['cliente_email']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">TELEFONE</div>
                            <div><?php echo formatarTelefone($fatura['cliente_telefone']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Veículo -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-car"></i> Veículo</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">PLACA</div>
                            <div style="font-weight: 600;"><?php echo $fatura['placa']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">VEÍCULO</div>
                            <div><?php echo $fatura['marca'] . ' ' . $fatura['modelo']; ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px;">CONTRATO</div>
                            <div><?php echo $fatura['numero_contrato']; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Pagamento -->
                <?php if ($fatura['status'] == 'pendente' || $fatura['status'] == 'vencido'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-credit-card"></i> Opções de Pagamento</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($fatura['pix_payload'])): ?>
                                <div style="margin-bottom: 20px;">
                                    <h4 style="margin-bottom: 10px;"><i class="fas fa-qrcode"></i> PIX</h4>
                                    <div style="background: var(--light); padding: 15px; border-radius: var(--border-radius); word-break: break-all; font-family: monospace; font-size: 12px;">
                                        <?php echo $fatura['pix_payload']; ?>
                                    </div>
                                    <button class="btn btn-primary" style="margin-top: 10px; width: 100%;" onclick="copiarTexto('<?php echo $fatura['pix_payload']; ?>')">
                                        <i class="fas fa-copy"></i> Copiar Código PIX
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($fatura['boleto_linha_digitavel'])): ?>
                                <div>
                                    <h4 style="margin-bottom: 10px;"><i class="fas fa-barcode"></i> Boleto</h4>
                                    <div style="background: var(--light); padding: 15px; border-radius: var(--border-radius); font-family: monospace; font-size: 14px; text-align: center; letter-spacing: 2px;">
                                        <?php echo $fatura['boleto_linha_digitavel']; ?>
                                    </div>
                                    <?php if (!empty($fatura['boleto_url'])): ?>
                                        <a href="<?php echo $fatura['boleto_url']; ?>" target="_blank" class="btn btn-secondary" style="margin-top: 10px; width: 100%;">
                                            <i class="fas fa-download"></i> Baixar Boleto
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
