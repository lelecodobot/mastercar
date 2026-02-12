<?php
/**
 * Master Car - Gestão de Usuários
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin(['master']);

// Busca usuários
$usuarios = DB()->fetchAll("SELECT * FROM usuarios ORDER BY nome");

$erros = [];

// Processa exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Não permite excluir a si mesmo
    if ($id == $_SESSION['usuario_id']) {
        mostrarAlerta('Você não pode excluir seu próprio usuário.', 'danger');
    } else {
        DB()->delete('usuarios', 'id = ?', [$id]);
        mostrarAlerta('Usuário excluído com sucesso!', 'success');
    }
    redirecionar('/admin/usuarios/');
}

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'admin';
    
    if (empty($nome) || empty($email)) {
        $erros[] = 'Preencha todos os campos obrigatórios.';
    }
    
    // Verifica se email já existe
    $existente = DB()->fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
    if ($existente) {
        $erros[] = 'Já existe um usuário com este e-mail.';
    }
    
    if (empty($erros)) {
        $data = [
            'nome' => $nome,
            'email' => $email,
            'tipo' => $tipo,
            'ativo' => 1
        ];
        
        if (!empty($senha)) {
            $data['senha'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        
        DB()->insert('usuarios', $data);
        mostrarAlerta('Usuário criado com sucesso!', 'success');
        redirecionar('/admin/usuarios/');
    }
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Usuários</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Usuários</span>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="abrirModal('modal-usuario')">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>
            </div>
            
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo $erro; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Lista de Usuários -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Último Acesso</th>
                                    <th width="100">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td><strong><?php echo $user['nome']; ?></strong></td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['tipo'] == 'master' ? 'danger' : 'info'; ?>">
                                                <?php echo ucfirst($user['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['ativo'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['ultimo_acesso'] ? formatarDataHora($user['ultimo_acesso']) : 'Nunca'; ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                                <a href="?excluir=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger btn-icon" 
                                                   onclick="return confirm('Excluir este usuário?')" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
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
    
    <!-- Modal Novo Usuário -->
    <div id="modal-usuario" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Novo Usuário</h3>
                <button class="modal-close" onclick="fecharModal('modal-usuario')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="admin">Administrador</option>
                            <option value="operador">Operador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="fecharModal('modal-usuario')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
