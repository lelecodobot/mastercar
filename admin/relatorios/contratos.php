<?php
/**
 * Master Car - Relatório de Contratos
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Estatísticas
$stats = DB()->fetch("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'ativo' THEN 1 END) as ativos,
        COUNT(CASE WHEN status = 'suspenso' THEN 1 END) as suspensos,
        COUNT(CASE WHEN status = 'encerrado' THEN 1 END) as encerrados,
        COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados
    FROM contratos_semanal
");

// Contratos por mês (últimos 6 meses)
$contratosPorMes = DB()->fetchAll("
    SELECT 
        DATE_FORMAT(data_inicio, '%Y-%m') as mes,
        COUNT(*) as total
    FROM contratos_semanal
    WHERE data_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data_inicio, '%Y-%m')
    ORDER BY mes DESC
");

// Contratos recentes
$contratos = DB()->fetchAll("
    SELECT cs.*, c.nome as cliente_nome, v.placa, v.marca, v.modelo
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    ORDER BY cs.created_at DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Contratos - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Relatório de Contratos</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Relatório de Contratos</span>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total de Contratos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['ativos']; ?></div>
                        <div class="stat-label">Contratos Ativos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['suspensos']; ?></div>
                        <div class="stat-label">Suspensos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon secondary">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['encerrados'] + $stats['cancelados']; ?></div>
                        <div class="stat-label">Encerrados/Cancelados</div>
                    </div>
                </div>
            </div>
            
            <!-- Contratos por Mês -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Contratos por Mês</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($contratosPorMes)): ?>
                        <p style="text-align: center; color: var(--gray);">Nenhum dado disponível.</p>
                    <?php else: ?>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <?php foreach ($contratosPorMes as $item): 
                                $data = DateTime::createFromFormat('Y-m', $item['mes']);
                                $nomeMes = $data ? $data->format('M/Y') : $item['mes'];
                            ?>
                                <div style="text-align: center; padding: 15px 25px; background: var(--light); border-radius: var(--border-radius);">
                                    <div style="font-size: 24px; font-weight: 700; color: var(--primary);"><?php echo $item['total']; ?></div>
                                    <div style="font-size: 12px; color: var(--gray);"><?php echo $nomeMes; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lista de Contratos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Contratos Recentes</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Valor Semanal</th>
                                    <th>Início</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contratos as $ctr): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/contratos/modelo.php?id=<?php echo $ctr['id']; ?>" class="btn btn-sm btn-link" style="padding: 0; font-weight: 600;">
                                                <?php echo $ctr['numero_contrato']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo $ctr['cliente_nome']; ?></td>
                                        <td><?php echo $ctr['placa'] . ' - ' . $ctr['marca'] . ' ' . $ctr['modelo']; ?></td>
                                        <td><?php echo formatarMoeda($ctr['valor_semanal']); ?></td>
                                        <td><?php echo formatarData($ctr['data_inicio']); ?></td>
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
