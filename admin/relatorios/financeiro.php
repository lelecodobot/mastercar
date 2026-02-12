<?php
/**
 * Master Car - Relatório Financeiro
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Filtros
$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

// Resumo do período
$resumo = DB()->fetch("
    SELECT 
        COUNT(CASE WHEN status = 'pago' THEN 1 END) as total_pagos,
        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as total_pendentes,
        COUNT(CASE WHEN status = 'vencido' THEN 1 END) as total_vencidos,
        SUM(CASE WHEN status = 'pago' THEN valor_total ELSE 0 END) as valor_recebido,
        SUM(CASE WHEN status IN ('pendente', 'vencido') THEN valor_total ELSE 0 END) as valor_receber
    FROM faturas_semanal
    WHERE MONTH(data_emissao) = ? AND YEAR(data_emissao) = ?
", [$mes, $ano]);

// Faturas do período
$faturas = DB()->fetchAll("
    SELECT f.*, c.nome as cliente_nome, cs.numero_contrato
    FROM faturas_semanal f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    WHERE MONTH(f.data_emissao) = ? AND YEAR(f.data_emissao) = ?
    ORDER BY f.data_emissao DESC
", [$mes, $ano]);

$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Relatório Financeiro</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Relatório Financeiro</span>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px;">
                        <div style="width: 150px;">
                            <select name="mes" class="form-control">
                                <?php foreach ($meses as $num => $nome): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $mes == $num ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="width: 100px;">
                            <select name="ano" class="form-control">
                                <?php for ($a = date('Y'); $a >= date('Y') - 5; $a--): ?>
                                    <option value="<?php echo $a; ?>" <?php echo $ano == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Resumo -->
            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $resumo['total_pagos'] ?? 0; ?></div>
                        <div class="stat-label">Faturas Pagas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $resumo['total_pendentes'] ?? 0; ?></div>
                        <div class="stat-label">Faturas Pendentes</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo formatarMoeda($resumo['valor_recebido'] ?? 0); ?></div>
                        <div class="stat-label">Valor Recebido</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo formatarMoeda($resumo['valor_receber'] ?? 0); ?></div>
                        <div class="stat-label">Valor a Receber</div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Faturas -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Faturas do Período</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th>Cliente</th>
                                    <th>Contrato</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($faturas)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            Nenhuma fatura encontrada neste período.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($faturas as $fat): ?>
                                        <tr>
                                            <td><?php echo $fat['numero_fatura']; ?></td>
                                            <td><?php echo $fat['cliente_nome']; ?></td>
                                            <td><?php echo $fat['numero_contrato']; ?></td>
                                            <td><?php echo formatarMoeda($fat['valor_total']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $STATUS_FATURA[$fat['status']]['class'] ?? 'secondary'; ?>">
                                                    <?php echo $STATUS_FATURA[$fat['status']]['label'] ?? $fat['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatarData($fat['data_emissao']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
