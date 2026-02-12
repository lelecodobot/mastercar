<?php
/**
 * Master Car - Dashboard Administrativo
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/cobranca.php';

protegerAdmin();

// Obtém estatísticas
$stats = [
    'clientes' => DB()->fetch("SELECT COUNT(*) as total FROM clientes WHERE status = 'ativo'")['total'],
    'veiculos' => DB()->fetch("SELECT COUNT(*) as total FROM veiculos WHERE status = 'disponivel'")['total'],
    'contratos' => DB()->fetch("SELECT COUNT(*) as total FROM contratos_semanal WHERE status = 'ativo'")['total'],
    'faturas_pendentes' => DB()->fetch("SELECT COUNT(*) as total FROM faturas_semanal WHERE status = 'pendente'")['total']
];

// Resumo financeiro
$resumo = obterResumoFinanceiro();

// Inadimplência
$inadimplencia = DB()->fetchAll("
    SELECT f.*, c.nome as cliente_nome, cs.numero_contrato, v.placa
    FROM faturas_semanal f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE f.status = 'vencido'
    ORDER BY f.data_vencimento ASC
    LIMIT 5
");

// Contratos recentes
$contratosRecentes = DB()->fetchAll("
    SELECT cs.*, c.nome as cliente_nome, v.placa, v.modelo
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    ORDER BY cs.created_at DESC
    LIMIT 5
");

// Alerta de alerta
$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <?php if ($alerta): ?>
                <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                    <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo $alerta['mensagem']; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Dashboard</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/contratos/novo.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Contrato
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['clientes']; ?></div>
                        <div class="stat-label">Clientes Ativos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['veiculos']; ?></div>
                        <div class="stat-label">Veículos Disponíveis</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['contratos']; ?></div>
                        <div class="stat-label">Contratos Ativos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['faturas_pendentes']; ?></div>
                        <div class="stat-label">Faturas Pendentes</div>
                    </div>
                </div>
            </div>
            
            <!-- Resumo Financeiro -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Resumo Financeiro - <?php echo date('m/Y'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                        <div class="text-center">
                            <div style="font-size: 28px; font-weight: 700; color: var(--success);">
                                <?php echo formatarMoeda($resumo['recebido']); ?>
                            </div>
                            <div style="color: var(--secondary); font-size: 13px;">Recebido</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size: 28px; font-weight: 700; color: var(--primary);">
                                <?php echo formatarMoeda($resumo['receber']); ?>
                            </div>
                            <div style="color: var(--secondary); font-size: 13px;">A Receber</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size: 28px; font-weight: 700; color: var(--danger);">
                                <?php echo formatarMoeda($resumo['atraso']); ?>
                            </div>
                            <div style="color: var(--secondary); font-size: 13px;">Em Atraso</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size: 28px; font-weight: 700; color: var(--warning);">
                                <?php echo $resumo['quantidade_atraso']; ?>
                            </div>
                            <div style="color: var(--secondary); font-size: 13px;">Faturas Atrasadas</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Inadimplência -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Inadimplência</h3>
                        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/inadimplencia.php" class="btn btn-sm btn-light">Ver Todos</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($inadimplencia)): ?>
                            <div style="padding: 30px; text-align: center; color: var(--gray);">
                                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                                <p>Nenhuma fatura em atraso!</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Vencimento</th>
                                        <th>Dias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inadimplencia as $fat): 
                                        $diasAtraso = diasEntre($fat['data_vencimento'], date('Y-m-d'));
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $fat['cliente_nome']; ?></strong>
                                                <br><small><?php echo $fat['placa']; ?></small>
                                            </td>
                                            <td><?php echo formatarMoeda($fat['valor_total']); ?></td>
                                            <td><?php echo formatarData($fat['data_vencimento']); ?></td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo $diasAtraso; ?> dias</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Contratos Recentes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-contract"></i> Contratos Recentes</h3>
                        <a href="<?php echo BASE_URL; ?>/admin/contratos/" class="btn btn-sm btn-light">Ver Todos</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contratosRecentes as $ctr): ?>
                                    <tr>
                                        <td><?php echo $ctr['numero_contrato']; ?></td>
                                        <td><?php echo $ctr['cliente_nome']; ?></td>
                                        <td><?php echo $ctr['placa']; ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = [
                                                'ativo' => 'success',
                                                'suspenso' => 'warning',
                                                'encerrado' => 'secondary',
                                                'cancelado' => 'danger'
                                            ][$ctr['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($ctr['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
