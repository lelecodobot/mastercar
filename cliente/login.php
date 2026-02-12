<?php
/**
 * Master Car - Login do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Se já estiver logado, redireciona
if (clienteLogado()) {
    redirecionar('/cliente/');
}

$erro = '';

// Processa login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $resultado = loginCliente($email, $senha);
        
        if ($resultado['sucesso']) {
            redirecionar('/cliente/');
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
    <title>Área do Cliente - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">MC</div>
            <h1 class="login-title">Área do Cliente</h1>
            <p class="login-subtitle">Acesse suas cobranças e contratos</p>
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
                <a href="<?php echo BASE_URL; ?>/admin/login.php" style="font-size: 13px;">
                    <i class="fas fa-user-shield"></i> Acesso Administrativo
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
