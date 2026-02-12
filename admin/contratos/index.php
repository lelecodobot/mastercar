<?php
/**
 * Master Car - Listagem de Contratos
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';

// Query base
$sql = "SELECT cs.*, c.nome as cliente_nome, c.cpf_cnpj, v.placa, v.marca, v.modelo,
        DATEDIFF(cs.data_proxima_cobranca, CURDATE()) as dias_proxima_cobranca
        FROM contratos_semanal cs
        JOIN clientes c ON cs.cliente_id = c.id
        JOIN veiculos v ON cs.veiculo_id = v.id
        WHERE 1=1";
$params = [];

if ($filtroStatus) {
    $sql .= " AND cs.status = ?";
    $params[] = $filtroStatus;
}

if ($busca) {
    $sql .= " AND (cs.numero_contrato LIKE ? OR c.nome LIKE ? OR v.placa LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY cs.created_at DESC";

$contratos = DB()->fetchAll($sql, $params);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratos - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Contratos</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Contratos</span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="<?php echo BASE_URL; ?>/admin/contratos/editor.php" class="btn btn-light">
                        <i class="fas fa-edit"></i> Editor de Contrato
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/contratos/novo.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Contrato
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por contrato, cliente, placa..." value="<?php echo htmlspecialchars($busca); ?>">
                        </div>
                        <div style="width: 150px;">
                            <select name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="ativo" <?php echo $filtroStatus == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="suspenso" <?php echo $filtroStatus == 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                                <option value="encerrado" <?php echo $filtroStatus == 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
                                <option value="cancelado" <?php echo $filtroStatus == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <?php if ($busca || $filtroStatus): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/contratos/" class="btn btn-light">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Contratos -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Tipo</th>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Valor Semanal</th>
                                    <th>Próx. Cobrança</th>
                                    <th>Status</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contratos)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-file-contract" style="font-size: 48px; color: var(--gray-light); margin-bottom: 15px;"></i>
                                            <p>Nenhum contrato encontrado.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($contratos as $ctr): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ctr['numero_contrato']); ?></strong>
                                                <br><small style="color: var(--gray);">Início: <?php echo formatarData($ctr['data_inicio']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($ctr['tipo_contrato'] == 'aplicativo'): ?>
                                                    <span class="badge badge-info" title="Uber, 99, etc."><i class="fas fa-mobile-alt"></i> Aplicativo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Padrão</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ctr['cliente_nome']); ?></strong>
                                                <br><small style="color: var(--gray);"><?php echo formatarCpfCnpj($ctr['cpf_cnpj']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ctr['placa']); ?></strong>
                                                <br><small style="color: var(--gray);"><?php echo htmlspecialchars($ctr['marca'] . ' ' . $ctr['modelo']); ?></small>
                                            </td>
                                            <td><?php echo formatarMoeda($ctr['valor_semanal']); ?></td>
                                            <td>
                                                <?php echo formatarData($ctr['data_proxima_cobranca']); ?>
                                                <?php if ($ctr['dias_proxima_cobranca'] <= 3 && $ctr['dias_proxima_cobranca'] >= 0): ?>
                                                    <br><span class="badge badge-warning">Em <?php echo $ctr['dias_proxima_cobranca']; ?> dias</span>
                                                <?php elseif ($ctr['dias_proxima_cobranca'] < 0): ?>
                                                    <br><span class="badge badge-danger">Atrasada</span>
                                                <?php endif; ?>
                                            </td>
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
                                            <td>
                                                <div class="actions">
                                                    <a href="<?php echo BASE_URL; ?>/admin/contratos/ver.php?id=<?php echo $ctr['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/contratos/modelo.php?id=<?php echo $ctr['id']; ?>" class="btn btn-sm btn-info btn-icon" title="Modelo de Contrato" target="_blank">
                                                        <i class="fas fa-file-contract"></i>
                                                    </a>
                                                    <?php if ($ctr['status'] == 'ativo'): ?>
                                                        <a href="<?php echo BASE_URL; ?>/admin/contratos/editar.php?id=<?php echo $ctr['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
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
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
