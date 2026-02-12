<?php
/**
 * Master Car - Diagnóstico do Sistema
 * Acesse este arquivo para verificar a configuração
 */

// Desativa exibição de erros para não quebrar o layout
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Cores para o diagnóstico
$corSucesso = '#10b981';
$corErro = '#ef4444';
$corAviso = '#f59e0b';
$corInfo = '#3b82f6';

function check($condicao, $mensagemSucesso, $mensagemErro) {
    global $corSucesso, $corErro;
    if ($condicao) {
        return "<span style='color: $corSucesso;'>✓ $mensagemSucesso</span>";
    } else {
        return "<span style='color: $corErro;'>✗ $mensagemErro</span>";
    }
}

function info($mensagem) {
    global $corInfo;
    return "<span style='color: $corInfo;'>ℹ $mensagem</span>";
}

function aviso($mensagem) {
    global $corAviso;
    return "<span style='color: $corAviso;'>⚠ $mensagem</span>";
}

// Detecta URL atual
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$urlAtual = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$urlBaseDetectada = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$urlBaseDetectada = str_replace('\\', '/', $urlBaseDetectada);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Master Car</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .item {
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .item:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #475569;
            display: inline-block;
            width: 200px;
        }
        .code {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
            border: 1px solid #e2e8f0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .status-ok { color: #10b981; font-weight: 600; }
        .status-erro { color: #ef4444; font-weight: 600; }
        .status-aviso { color: #f59e0b; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-stethoscope"></i> Diagnóstico do Sistema</h1>
            <p>Verificação de configuração do Master Car</p>
        </div>
        
        <!-- Informações do Servidor -->
        <div class="card">
            <h2>🌐 Informações do Servidor</h2>
            <div class="item">
                <span class="label">URL Atual:</span>
                <span><?php echo htmlspecialchars($urlAtual); ?></span>
            </div>
            <div class="item">
                <span class="label">URL Base Detectada:</span>
                <span><?php echo htmlspecialchars($urlBaseDetectada); ?></span>
            </div>
            <div class="item">
                <span class="label">Protocolo:</span>
                <span><?php echo $protocol; ?></span>
            </div>
            <div class="item">
                <span class="label">Host:</span>
                <span><?php echo $_SERVER['HTTP_HOST']; ?></span>
            </div>
            <div class="item">
                <span class="label">IP do Servidor:</span>
                <span><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></span>
            </div>
            <div class="item">
                <span class="label">PHP Version:</span>
                <span><?php echo phpversion(); ?></span>
            </div>
        </div>
        
        <!-- Configuração do Sistema -->
        <div class="card">
            <h2>⚙️ Configuração do Sistema</h2>
            <?php
            // Tenta carregar o config.php
            $configOk = false;
            $baseUrlConfig = 'Não carregado';
            
            if (file_exists('includes/config.php')) {
                // Captura as constantes definidas
                ob_start();
                include 'includes/config.php';
                ob_end_clean();
                
                if (defined('BASE_URL')) {
                    $configOk = true;
                    $baseUrlConfig = BASE_URL;
                }
            }
            ?>
            <div class="item">
                <span class="label">Arquivo config.php:</span>
                <?php echo check($configOk, 'Carregado com sucesso', 'Não encontrado ou erro'); ?>
            </div>
            <div class="item">
                <span class="label">BASE_URL Configurada:</span>
                <span><?php echo htmlspecialchars($baseUrlConfig); ?></span>
            </div>
            <div class="item">
                <span class="label">BASE_URL vs URL Atual:</span>
                <?php 
                if ($configOk) {
                    $urlsIguais = (strpos($urlBaseDetectada, BASE_URL) === 0 || strpos(BASE_URL, $urlBaseDetectada) === 0);
                    echo check($urlsIguais, 'URLs compatíveis', 'URLs podem estar diferentes');
                }
                ?>
            </div>
        </div>
        
        <!-- Permissões de Pastas -->
        <div class="card">
            <h2>📁 Permissões de Pastas</h2>
            <?php
            $pastas = [
                'uploads' => 'uploads',
                'uploads/veiculos' => 'uploads/veiculos',
                'uploads/veiculos_docs' => 'uploads/veiculos_docs',
                'uploads/documentos' => 'uploads/documentos',
                'logs' => 'logs',
            ];
            
            foreach ($pastas as $nome => $pasta):
                $existe = is_dir($pasta);
                $gravavel = $existe ? is_writable($pasta) : false;
            ?>
            <div class="item">
                <span class="label"><?php echo $nome; ?>:</span>
                <?php 
                if (!$existe) {
                    echo aviso("Pasta não existe - será criada automaticamente");
                } elseif (!$gravavel) {
                    echo "<span class='status-erro'>✗ Existe mas não tem permissão de escrita</span>";
                } else {
                    echo "<span class='status-ok'>✓ OK</span>";
                }
                ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Banco de Dados -->
        <div class="card">
            <h2>🗄️ Banco de Dados</h2>
            <?php
            $dbOk = false;
            $dbErro = '';
            
            if (file_exists('includes/config.php') && file_exists('includes/database.php')) {
                try {
                    ob_start();
                    include 'includes/config.php';
                    include 'includes/database.php';
                    ob_end_clean();
                    
                    // Tenta conectar
                    $pdo = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                        DB_USER,
                        DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $dbOk = true;
                } catch (PDOException $e) {
                    $dbErro = $e->getMessage();
                }
            }
            ?>
            <div class="item">
                <span class="label">Conexão MySQL:</span>
                <?php echo check($dbOk, 'Conectado com sucesso', 'Erro: ' . $dbErro); ?>
            </div>
            <?php if ($dbOk): ?>
            <div class="item">
                <span class="label">Host:</span>
                <span><?php echo DB_HOST; ?></span>
            </div>
            <div class="item">
                <span class="label">Banco:</span>
                <span><?php echo DB_NAME; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Extensões PHP -->
        <div class="card">
            <h2>🔌 Extensões PHP</h2>
            <?php
            $extensoes = [
                'pdo' => 'PDO (MySQL)',
                'pdo_mysql' => 'PDO MySQL Driver',
                'json' => 'JSON',
                'mbstring' => 'Multibyte String',
                'gd' => 'GD (Imagens)',
                'fileinfo' => 'Fileinfo',
                'session' => 'Session',
            ];
            
            foreach ($extensoes as $ext => $nome):
                $carregada = extension_loaded($ext);
            ?>
            <div class="item">
                <span class="label"><?php echo $nome; ?>:</span>
                <?php echo check($carregada, 'Carregada', 'Não carregada'); ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Módulos Apache -->
        <div class="card">
            <h2>🖥️ Módulos Apache</h2>
            <?php
            if (function_exists('apache_get_modules')) {
                $modulos = apache_get_modules();
                $modulosNecessarios = [
                    'mod_rewrite' => 'Rewrite (URLs amigáveis)',
                    'mod_headers' => 'Headers (Segurança)',
                    'mod_deflate' => 'Deflate (Compressão)',
                ];
                
                foreach ($modulosNecessarios as $mod => $nome):
                    $carregado = in_array($mod, $modulos);
                ?>
                <div class="item">
                    <span class="label"><?php echo $nome; ?>:</span>
                    <?php echo check($carregado, 'Ativo', 'Não ativo'); ?>
                </div>
                <?php endforeach; ?>
            <?php } else { ?>
                <div class="item">
                    <?php echo info("Não foi possível verificar (pode não estar usando Apache)"); ?>
                </div>
            <?php } ?>
        </div>
        
        <!-- Ações -->
        <div class="card">
            <h2>🚀 Ações</h2>
            <p>Acesse as páginas principais do sistema:</p>
            <div style="margin-top: 15px;">
                <a href="./" class="btn">Página Inicial</a>
                <a href="./admin/" class="btn" style="background: #059669;">Painel Admin</a>
                <a href="./cliente/" class="btn" style="background: #7c3aed;">Área do Cliente</a>
                <a href="./install/" class="btn" style="background: #ea580c;">Instalador</a>
            </div>
        </div>
        
        <div style="text-align: center; color: #64748b; margin-top: 30px;">
            <p>Master Car - Diagnóstico gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
