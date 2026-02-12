<?php
/**
 * Master Car - Configurações de Pagamento
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin(['master', 'admin']);

$config = DB()->fetch("SELECT * FROM config_pagamento WHERE ativo = 1 LIMIT 1");
if (!$config) {
    $config = ['gateway' => 'asaas', 'ambiente' => 'sandbox', 'ativo' => 0];
}

$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gateway = $_POST['gateway'] ?? 'asaas';
    $apiKey = $_POST['api_key'] ?? '';
    $ambiente = $_POST['ambiente'] ?? 'sandbox';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    try {
        // Atualiza ou insere configuração
        if ($config['id']) {
            DB()->update('config_pagamento', [
                'gateway' => $gateway,
                'api_key' => $apiKey,
                'ambiente' => $ambiente,
                'ativo' => $ativo,
                'multa_percentual' => $_POST['multa_percentual'] ?? MULTA_ATRASO_PADRAO,
                'juros_percentual' => $_POST['juros_percentual'] ?? JUROS_DIA_ATRASO_PADRAO
            ], 'id = :id', ['id' => $config['id']]);
        } else {
            DB()->insert('config_pagamento', [
                'gateway' => $gateway,
                'api_key' => $apiKey,
                'ambiente' => $ambiente,
                'ativo' => $ativo,
                'multa_percentual' => $_POST['multa_percentual'] ?? MULTA_ATRASO_PADRAO,
                'juros_percentual' => $_POST['juros_percentual'] ?? JUROS_DIA_ATRASO_PADRAO
            ]);
        }
        
        $sucesso = true;
        mostrarAlerta('Configurações salvas com sucesso!', 'success');
        redirecionar('/admin/configuracoes/pagamento.php');
        
    } catch (Exception $e) {
        $erros[] = 'Erro ao salvar configurações: ' . $e->getMessage();
    }
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Pagamento - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Configurações de Pagamento</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Configurações de Pagamento</span>
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
            
            <form method="POST" action="">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-credit-card"></i> Gateway de Pagamento</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Gateway</label>
                                <select name="gateway" class="form-control">
                                    <option value="asaas" <?php echo $config['gateway'] == 'asaas' ? 'selected' : ''; ?>>Asaas</option>
                                    <option value="mercadopago" <?php echo $config['gateway'] == 'mercadopago' ? 'selected' : ''; ?>>Mercado Pago</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ambiente</label>
                                <select name="ambiente" class="form-control">
                                    <option value="sandbox" <?php echo $config['ambiente'] == 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testes)</option>
                                    <option value="producao" <?php echo $config['ambiente'] == 'producao' ? 'selected' : ''; ?>>Produção</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">API Key</label>
                            <input type="text" name="api_key" class="form-control" value="<?php echo $config['api_key'] ?? ''; ?>">
                            <div class="form-hint">Chave de API fornecida pelo gateway de pagamento</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-check" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="ativo" value="1" <?php echo ($config['ativo'] ?? 0) ? 'checked' : ''; ?>>
                                <span>Ativar integração de pagamento</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-percentage"></i> Multas e Juros</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Multa por Atraso (%)</label>
                                <input type="number" name="multa_percentual" class="form-control" step="0.01" value="<?php echo $config['multa_percentual'] ?? MULTA_ATRASO_PADRAO; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Juros ao Dia (%)</label>
                                <input type="number" name="juros_percentual" class="form-control" step="0.01" value="<?php echo $config['juros_percentual'] ?? JUROS_DIA_ATRASO_PADRAO; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-link"></i> Webhook</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">URL do Webhook</label>
                            <input type="text" class="form-control" value="<?php echo BASE_URL; ?>/api/webhook.php" readonly>
                            <div class="form-hint">Configure esta URL no seu gateway de pagamento para receber notificações</div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
