<?php
/**
 * Master Car - Instalador do Sistema
 */

// Verifica se já está instalado
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
    if (defined('MASTER_CAR')) {
        // Tenta conectar ao banco
        try {
            require_once '../includes/database.php';
            DB()->query("SELECT 1");
            echo "<h2>Sistema já instalado!</h2>";
            echo "<p>O Master Car já está instalado e configurado.</p>";
            echo "<a href='../admin/login.php'>Acessar o Sistema</a>";
            exit;
        } catch (Exception $e) {
            // Continua com a instalação
        }
    }
}

$passo = $_GET['passo'] ?? 1;
$erros = [];
$sucesso = false;

// Passo 1: Verificar requisitos
if ($passo == 1) {
    $requisitos = [
        'PHP >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'Extensão PDO' => extension_loaded('pdo'),
        'Extensão PDO MySQL' => extension_loaded('pdo_mysql'),
        'Extensão cURL' => extension_loaded('curl'),
        'Extensão JSON' => extension_loaded('json'),
        'Permissão de escrita' => is_writable('../') || @mkdir('../test_write', 0755, true)
    ];
    
    if (isset($_POST['proximo'])) {
        if (in_array(false, $requisitos, true)) {
            $erros[] = 'Alguns requisitos não foram atendidos. Por favor, corrija antes de continuar.';
        } else {
            header('Location: ?passo=2');
            exit;
        }
    }
}

// Passo 2: Configurar banco de dados
if ($passo == 2) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $dbHost = $_POST['db_host'] ?? 'localhost';
        $dbUser = $_POST['db_user'] ?? 'root';
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = $_POST['db_name'] ?? 'mastercar';
        
        // Testa conexão
        try {
            $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Cria banco de dados
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Salva configuração
            $configContent = "<?php
/**
 * Master Car - Configuração Gerada pelo Instalador
 */

define('DB_HOST', '{$dbHost}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');
define('DB_NAME', '{$dbName}');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://' . \$_SERVER['HTTP_HOST'] . '/mastercar');
define('SITE_NAME', 'Master Car');
define('SITE_VERSION', '1.0.0');

define('ROOT_PATH', dirname(__FILE__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('CRON_PATH', ROOT_PATH . '/cron');

define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log');

define('ASAAS_SANDBOX_URL', 'https://sandbox.asaas.com/api/v3');
define('ASAAS_PROD_URL', 'https://api.asaas.com/v3');

define('DIAS_TOLERANCIA_PADRAO', 3);
define('MULTA_ATRASO_PADRAO', 2.00);
define('JUROS_DIA_ATRASO_PADRAO', 0.33);
define('DIAS_BLOQUEIO_PADRAO', 7);

if (!defined('MASTER_CAR')) {
    define('MASTER_CAR', true);
}
";
            
            file_put_contents('../includes/config.php', $configContent);
            
            // Importa SQL
            $pdo->exec("USE {$dbName}");
            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);
            
            header('Location: ?passo=3');
            exit;
            
        } catch (PDOException $e) {
            $erros[] = 'Erro ao conectar ao banco de dados: ' . $e->getMessage();
        }
    }
}

// Passo 3: Configurar administrador
if ($passo == 3) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nome = $_POST['admin_nome'] ?? '';
        $email = $_POST['admin_email'] ?? '';
        $senha = $_POST['admin_senha'] ?? '';
        $senhaConfirm = $_POST['admin_senha_confirm'] ?? '';
        
        if (empty($nome) || empty($email) || empty($senha)) {
            $erros[] = 'Preencha todos os campos.';
        } elseif ($senha != $senhaConfirm) {
            $erros[] = 'As senhas não conferem.';
        } elseif (strlen($senha) < 6) {
            $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                require_once '../includes/config.php';
                require_once '../includes/database.php';
                
                $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
                
                DB()->update('usuarios', [
                    'nome' => $nome,
                    'email' => $email,
                    'senha' => $hash
                ], 'tipo = ?', ['master']);
                
                $sucesso = true;
                
            } catch (Exception $e) {
                $erros[] = 'Erro ao criar administrador: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Master Car</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        .install-header {
            background: #1e293b;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .install-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .install-header p {
            opacity: 0.8;
        }
        .install-body {
            padding: 30px;
        }
        .install-steps {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #64748b;
        }
        .step.active {
            background: #2563eb;
            color: white;
        }
        .step.completed {
            background: #10b981;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fee2e2;
            color: #ef4444;
            border-left: 4px solid #ef4444;
        }
        .alert-success {
            background: #d1fae5;
            color: #10b981;
            border-left: 4px solid #10b981;
        }
        .requirement {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .requirement:last-child {
            border-bottom: none;
        }
        .requirement-status {
            font-weight: 600;
        }
        .requirement-status.ok {
            color: #10b981;
        }
        .requirement-status.error {
            color: #ef4444;
        }
        .success-icon {
            font-size: 64px;
            color: #10b981;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="install-box">
        <div class="install-header">
            <h1><i class="fas fa-car"></i> Master Car</h1>
            <p>Instalação do Sistema</p>
        </div>
        
        <div class="install-body">
            <!-- Steps -->
            <div class="install-steps">
                <div class="step <?php echo $passo >= 1 ? 'active' : ''; ?> <?php echo $passo > 1 ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo $passo >= 2 ? 'active' : ''; ?> <?php echo $passo > 2 ? 'completed' : ''; ?>">2</div>
                <div class="step <?php echo $passo >= 3 ? 'active' : ''; ?>">3</div>
            </div>
            
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($erros as $erro): ?>
                        <div><?php echo $erro; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($passo == 1): ?>
                <h3 style="margin-bottom: 20px;">Requisitos do Sistema</h3>
                
                <div style="border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                    <?php foreach ($requisitos as $nome => $ok): ?>
                        <div class="requirement">
                            <span><?php echo $nome; ?></span>
                            <span class="requirement-status <?php echo $ok ? 'ok' : 'error'; ?>">
                                <?php echo $ok ? '✓ OK' : '✗ Falha'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" action="">
                    <button type="submit" name="proximo" class="btn btn-primary" style="width: 100%;">
                        Próximo Passo →
                    </button>
                </form>
                
            <?php elseif ($passo == 2): ?>
                <h3 style="margin-bottom: 20px;">Configuração do Banco de Dados</h3>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Servidor MySQL</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usuário MySQL</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Senha MySQL</label>
                        <input type="password" name="db_pass" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nome do Banco de Dados</label>
                        <input type="text" name="db_name" class="form-control" value="mastercar" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Instalar Banco de Dados →
                    </button>
                </form>
                
            <?php elseif ($passo == 3 && !$sucesso): ?>
                <h3 style="margin-bottom: 20px;">Criar Administrador</h3>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="admin_nome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="admin_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="admin_senha" class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="admin_senha_confirm" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Finalizar Instalação →
                    </button>
                </form>
                
            <?php elseif ($sucesso): ?>
                <div class="success-icon">✓</div>
                <h3 style="text-align: center; margin-bottom: 10px;">Instalação Concluída!</h3>
                <p style="text-align: center; color: #64748b; margin-bottom: 30px;">
                    O Master Car foi instalado com sucesso.
                </p>
                
                <div style="background: #f1f5f9; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 0 0 10px;"><strong>Dados de acesso:</strong></p>
                    <p style="margin: 0; font-size: 14px;">
                        <strong>URL:</strong> <?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/mastercar/admin/'; ?><br>
                        <strong>E-mail:</strong> <?php echo $_POST['admin_email']; ?>
                    </p>
                </div>
                
                <a href="../admin/login.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                    Acessar o Sistema →
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
