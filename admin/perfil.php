<?php
/**
 * Master Car - Perfil do Administrador
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerAdmin();

$usuario = usuarioAtual();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    
    if (empty($nome) || empty($email)) {
        $erros[] = 'Preencha todos os campos obrigatórios.';
    }
    
    // Verifica se email já existe (de outro usuário)
    $existente = DB()->fetch("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $usuario['id']]);
    if ($existente) {
        $erros[] = 'Este e-mail já está em uso.';
    }
    
    // Se quer alterar senha
    if (!empty($novaSenha)) {
        if (empty($senhaAtual)) {
            $erros[] = 'Informe a senha atual para alterar.';
        } else {
            $userData = DB()->fetch("SELECT senha FROM usuarios WHERE id = ?", [$usuario['id']]);
            if (!password_verify($senhaAtual, $userData['senha'])) {
                $erros[] = 'Senha atual incorreta.';
            }
        }
    }
    
    if (empty($erros)) {
        try {
            $data = [
                'nome' => $nome,
                'email' => $email
            ];
            
            if (!empty($novaSenha)) {
                $data['senha'] = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            
            DB()->update('usuarios', $data, 'id = :id', ['id' => $usuario['id']]);
            
            // Atualiza sessão
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_email'] = $email;
            
            mostrarAlerta('Perfil atualizado com sucesso!', 'success');
            redirecionar('/admin/perfil.php');
            
        } catch (Exception $e) {
            $erros[] = 'Erro ao atualizar perfil: ' . $e->getMessage();
        }
    }
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Meu Perfil</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Meu Perfil</span>
                    </div>
                </div>
            </div>
            
            <?php if ($alerta): ?>
                <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                    <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo $alerta['mensagem']; ?></span>
                </div>
            <?php endif; ?>
            
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
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Dados Pessoais -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Dados Pessoais</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo $usuario['nome']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $usuario['email']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tipo de Usuário</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($usuario['tipo']); ?>" disabled>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Dados
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Alterar Senha -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lock"></i> Alterar Senha</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" name="senha_atual" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="nova_senha" class="form-control" minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
