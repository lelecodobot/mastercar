<?php
/**
 * Master Car - Listagem de Faturas (Admin)
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';

// Query base
$sql = "SELECT f.*, c.nome as cliente_nome, cs.numero_contrato, v.placa
        FROM faturas_semanal f
        JOIN clientes c ON f.cliente_id = c.id
        JOIN contratos_semanal cs ON f.contrato_id = cs.id
        JOIN veiculos v ON cs.veiculo_id = v.id
        WHERE 1=1";
$params = [];

if ($filtroStatus) {
    $sql .= " AND f.status = ?";
    $params[] = $filtroStatus;
}

if ($busca) {
    $sql .= " AND (f.numero_fatura LIKE ? OR c.nome LIKE ? OR v.placa LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY f.data_emissao DESC, f.created_at DESC";

$faturas = DB()->fetchAll($sql, $params);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faturas - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Faturas</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Faturas</span>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por fatura, cliente, placa..." value="<?php echo $busca; ?>">
                        </div>
                        <div style="width: 150px;">
                            <select name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="pendente" <?php echo $filtroStatus == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="pago" <?php echo $filtroStatus == 'pago' ? 'selected' : ''; ?>>Pago</option>
                                <option value="vencido" <?php echo $filtroStatus == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                                <option value="bloqueado" <?php echo $filtroStatus == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                <option value="cancelado" <?php echo $filtroStatus == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <?php if ($busca || $filtroStatus): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/faturas/" class="btn btn-light">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Faturas -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th width="100">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($faturas)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-receipt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 15px;"></i>
                                            <p>Nenhuma fatura encontrada.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($faturas as $fat): 
                                        $diasAtraso = 0;
                                        if ($fat['status'] == 'vencido' || ($fat['status'] == 'pendente' && $fat['data_vencimento'] < date('Y-m-d'))) {
                                            $diasAtraso = diasEntre($fat['data_vencimento'], date('Y-m-d'));
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $fat['numero_fatura']; ?></strong>
                                                <br><small style="color: var(--gray);">Semana <?php echo $fat['semana_referencia']; ?></small>
                                            </td>
                                            <td><?php echo $fat['cliente_nome']; ?></td>
                                            <td><?php echo $fat['placa']; ?></td>
                                            <td>
                                                <?php echo formatarMoeda($fat['valor_total']); ?>
                                                <?php if ($fat['valor_multa'] > 0 || $fat['valor_juros'] > 0): ?>
                                                    <br><small style="color: var(--danger);">+ multa/juros</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo formatarData($fat['data_vencimento']); ?>
                                                <?php if ($diasAtraso > 0): ?>
                                                    <br><span class="badge badge-danger"><?php echo $diasAtraso; ?> dias atraso</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = $STATUS_FATURA[$fat['status']]['class'] ?? 'secondary';
                                                $statusLabel = $STATUS_FATURA[$fat['status']]['label'] ?? ucfirst($fat['status']);
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo $statusLabel; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/admin/cobrancas/ver.php?id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
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
