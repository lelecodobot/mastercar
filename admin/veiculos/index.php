<?php
/**
 * Master Car - Listagem de Veículos
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$filtroCategoria = $_GET['categoria'] ?? '';
$busca = $_GET['busca'] ?? '';

// Query base
$sql = "SELECT v.*, 
        (SELECT COUNT(*) FROM contratos_semanal WHERE veiculo_id = v.id AND status = 'ativo') as em_contrato
        FROM veiculos v WHERE 1=1";
$params = [];

if ($filtroStatus) {
    $sql .= " AND v.status = ?";
    $params[] = $filtroStatus;
}

if ($filtroCategoria) {
    $sql .= " AND v.categoria = ?";
    $params[] = $filtroCategoria;
}

if ($busca) {
    $sql .= " AND (v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY v.marca, v.modelo ASC";

$veiculos = DB()->fetchAll($sql, $params);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veículos - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Veículos</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Veículos</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/novo.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Veículo
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por placa, marca, modelo..." value="<?php echo $busca; ?>">
                        </div>
                        <div style="width: 150px;">
                            <select name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="disponivel" <?php echo $filtroStatus == 'disponivel' ? 'selected' : ''; ?>>Disponível</option>
                                <option value="alugado" <?php echo $filtroStatus == 'alugado' ? 'selected' : ''; ?>>Alugado</option>
                                <option value="manutencao" <?php echo $filtroStatus == 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
                                <option value="bloqueado" <?php echo $filtroStatus == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                            </select>
                        </div>
                        <div style="width: 150px;">
                            <select name="categoria" class="form-control">
                                <option value="">Todas categorias</option>
                                <?php foreach ($CATEGORIAS_VEICULO as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $filtroCategoria == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <?php if ($busca || $filtroStatus || $filtroCategoria): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/veiculos/" class="btn btn-light">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Veículos -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Veículo</th>
                                    <th>Placa</th>
                                    <th>Categoria</th>
                                    <th>KM Atual</th>
                                    <th>Valor Semanal</th>
                                    <th>Status</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($veiculos)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-car" style="font-size: 48px; color: var(--gray-light); margin-bottom: 15px;"></i>
                                            <p>Nenhum veículo encontrado.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($veiculos as $veiculo): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $veiculo['marca'] . ' ' . $veiculo['modelo']; ?></strong>
                                                <br><small style="color: var(--gray);"><?php echo $veiculo['ano_fabricacao'] . '/' . $veiculo['ano_modelo']; ?> - <?php echo $veiculo['cor']; ?></small>
                                            </td>
                                            <td><strong><?php echo $veiculo['placa']; ?></strong></td>
                                            <td><?php echo $CATEGORIAS_VEICULO[$veiculo['categoria']] ?? $veiculo['categoria']; ?></td>
                                            <td><?php echo number_format($veiculo['km_atual'], 0, ',', '.'); ?> km</td>
                                            <td><?php echo formatarMoeda($veiculo['valor_semanal']); ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'disponivel' => 'success',
                                                    'alugado' => 'warning',
                                                    'manutencao' => 'info',
                                                    'bloqueado' => 'danger',
                                                    'inativo' => 'secondary'
                                                ][$veiculo['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo $STATUS_VEICULO[$veiculo['status']]['label'] ?? ucfirst($veiculo['status']); ?>
                                                </span>
                                                <?php if ($veiculo['em_contrato'] > 0): ?>
                                                    <br><small class="badge badge-info" style="margin-top: 5px;">Em contrato</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/editar.php?id=<?php echo $veiculo['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/ver.php?id=<?php echo $veiculo['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/fotos.php?id=<?php echo $veiculo['id']; ?>" class="btn btn-sm btn-info btn-icon" title="Fotos">
                                                        <i class="fas fa-camera"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/documentos.php?id=<?php echo $veiculo['id']; ?>" class="btn btn-sm btn-warning btn-icon" title="Documentos">
                                                        <i class="fas fa-folder-open"></i>
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
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
