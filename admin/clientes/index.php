<?php
/**
 * Master Car - Listagem de Clientes
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Filtros
$filtro = $_GET['filtro'] ?? '';
$busca = $_GET['busca'] ?? '';

// Query base
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM contratos_semanal WHERE cliente_id = c.id AND status = 'ativo') as total_contratos,
        (SELECT COUNT(*) FROM faturas_semanal WHERE cliente_id = c.id AND status IN ('pendente', 'vencido')) as faturas_pendentes,
        (SELECT COUNT(*) FROM clientes_documentos WHERE cliente_id = c.id AND status = 'pendente') as docs_pendentes
        FROM clientes c WHERE 1=1";
$params = [];

if ($filtro) {
    $sql .= " AND c.status = ?";
    $params[] = $filtro;
}

if ($busca) {
    $sql .= " AND (c.nome LIKE ? OR c.email LIKE ? OR c.cpf_cnpj LIKE ? OR c.telefone LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY c.nome ASC";

$clientes = DB()->fetchAll($sql, $params);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Clientes</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Clientes</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/clientes/novo.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Cliente
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por nome, email, CPF/CNPJ..." value="<?php echo $busca; ?>">
                        </div>
                        <div style="width: 150px;">
                            <select name="filtro" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="ativo" <?php echo $filtro == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="bloqueado" <?php echo $filtro == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                <option value="inadimplente" <?php echo $filtro == 'inadimplente' ? 'selected' : ''; ?>>Inadimplente</option>
                                <option value="cancelado" <?php echo $filtro == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <?php if ($busca || $filtro): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/clientes/" class="btn btn-light">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Clientes -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Contato</th>
                                    <th>Documento</th>
                                    <th>Contratos</th>
                                    <th>Pendências</th>
                                    <th>Status</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clientes)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-users" style="font-size: 48px; color: var(--gray-light); margin-bottom: 15px;"></i>
                                            <p>Nenhum cliente encontrado.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $cliente['nome']; ?></strong>
                                                <br><small style="color: var(--gray);"><?php echo formatarData($cliente['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($cliente['email']): ?>
                                                    <i class="fas fa-envelope" style="color: var(--gray);"></i> <?php echo $cliente['email']; ?>
                                                    <br>
                                                <?php endif; ?>
                                                <?php if ($cliente['telefone']): ?>
                                                    <i class="fas fa-phone" style="color: var(--gray);"></i> <?php echo formatarTelefone($cliente['telefone']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatarCpfCnpj($cliente['cpf_cnpj']); ?></td>
                                            <td>
                                                <?php if ($cliente['total_contratos'] > 0): ?>
                                                    <span class="badge badge-info"><?php echo $cliente['total_contratos']; ?> ativo(s)</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Nenhum</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($cliente['faturas_pendentes'] > 0): ?>
                                                    <span class="badge badge-danger"><?php echo $cliente['faturas_pendentes']; ?> pendente(s)</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Em dia</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'ativo' => 'success',
                                                    'bloqueado' => 'danger',
                                                    'inadimplente' => 'warning',
                                                    'cancelado' => 'secondary'
                                                ][$cliente['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($cliente['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="<?php echo BASE_URL; ?>/admin/clientes/editar.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/clientes/ver.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/clientes/documentos.php?cliente_id=<?php echo $cliente['id']; ?>" 
                                                       class="btn btn-sm btn-info btn-icon" 
                                                       title="Documentos Pessoais">
                                                        <i class="fas fa-folder-open"></i>
                                                        <?php if ($cliente['docs_pendentes'] > 0): ?>
                                                            <span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 10px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                                <?php echo $cliente['docs_pendentes']; ?>
                                                            </span>
                                                        <?php endif; ?>
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
