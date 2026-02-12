<?php
/**
 * Master Car - Painel de Inadimplência
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/cobranca.php';

protegerAdmin();

// Obtém inadimplência
$inadimplencia = obterInadimplenciaSemanal();

// Totais
$totalDevido = array_sum(array_column($inadimplencia, 'valor_total'));
$totalClientes = count(array_unique(array_column($inadimplencia, 'cliente_id')));

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inadimplência - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Inadimplência</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/">Cobranças</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Inadimplência</span>
                    </div>
                </div>
            </div>
            
            <!-- Resumo -->
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo count($inadimplencia); ?></div>
                        <div class="stat-label">Faturas em Atraso</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $totalClientes; ?></div>
                        <div class="stat-label">Clientes Inadimplentes</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo formatarMoeda($totalDevido); ?></div>
                        <div class="stat-label">Total Devido</div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Inadimplência -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Faturas em Atraso</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Fatura</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Dias Atraso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inadimplencia)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 15px;"></i>
                                            <p style="color: var(--success); font-weight: 600;">Nenhuma inadimplência!</p>
                                            <p style="color: var(--gray);">Todos os clientes estão em dia.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inadimplencia as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $item['cliente_nome']; ?></strong>
                                                <br><small style="color: var(--gray);"><?php echo formatarCpfCnpj($item['cliente_cpf_cnpj']); ?></small>
                                                <br><small><i class="fas fa-phone"></i> <?php echo formatarTelefone($item['cliente_telefone']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo $item['veiculo_placa']; ?></strong>
                                                <br><small style="color: var(--gray);"><?php echo $item['veiculo_modelo']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo $item['numero_fatura']; ?></strong>
                                                <br><small style="color: var(--gray);">Contrato: <?php echo $item['numero_contrato']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo formatarMoeda($item['valor_total']); ?></strong>
                                                <?php if ($item['valor_multa'] > 0 || $item['valor_juros'] > 0): ?>
                                                    <br><small style="color: var(--danger);">
                                                        Multa: <?php echo formatarMoeda($item['valor_multa']); ?> | 
                                                        Juros: <?php echo formatarMoeda($item['valor_juros']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatarData($item['data_vencimento']); ?></td>
                                            <td>
                                                <span class="badge badge-danger" style="font-size: 14px; padding: 8px 15px;">
                                                    <?php echo $item['dias_atraso']; ?> dias
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/ver.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="tel:<?php echo $item['cliente_telefone']; ?>" class="btn btn-sm btn-success btn-icon" title="Ligar">
                                                        <i class="fas fa-phone"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Ações em Massa -->
            <?php if (!empty($inadimplencia)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bolt"></i> Ações em Massa</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="alert('Função de envio de e-mail em massa')">
                                <i class="fas fa-envelope"></i> Enviar E-mails
                            </button>
                            <button class="btn btn-success" onclick="alert('Função de envio de WhatsApp em massa')">
                                <i class="fas fa-whatsapp"></i> Enviar WhatsApp
                            </button>
                            <button class="btn btn-warning" onclick="alert('Função de gerar relatório')">
                                <i class="fas fa-file-pdf"></i> Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
