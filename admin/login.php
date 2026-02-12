<?php
/**
 * Master Car - Login Administrativo
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Se já estiver logado, redireciona
if (estaLogado()) {
    redirecionar('/admin/');
}

$erro = '';

// Processa login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $resultado = loginAdmin($email, $senha);
        
        if ($resultado['sucesso']) {
            redirecionar('/admin/');
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">MC</div>
            <h1 class="login-title"><?php echo SITE_NAME; ?></h1>
            <p class="login-subtitle">Sistema de Gestão de Locadora</p>
        </div>
        
        <div class="login-body">
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $erro; ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">E-mail</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" style="margin-top: 10px;">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo BASE_URL; ?>/cliente/login.php" style="font-size: 13px;">
                    <i class="fas fa-user"></i> Área do Cliente
                </a>
            </div>
        </div>
        
        <div class="login-footer">
            <p>© <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos os direitos reservados</p>
        </div>
    </div>
    
    <style>
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        .input-group .form-control {
            padding-left: 40px;
        }
    </style>
</body>
</html>
