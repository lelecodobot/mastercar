<?php
/**
 * Master Car - Configurações Gerais
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Busca configurações
$configuracoes = DB()->fetchAll("SELECT * FROM configuracoes ORDER BY chave");

$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        foreach ($_POST['config'] as $chave => $valor) {
            // Verifica se a configuração existe
            $existe = DB()->fetch("SELECT id FROM configuracoes WHERE chave = ?", [$chave]);
            if ($existe) {
                DB()->query("UPDATE configuracoes SET valor = ? WHERE chave = ?", [$valor, $chave]);
            } else {
                DB()->query("INSERT INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)", [$chave, $valor, 'Configuração: ' . $chave]);
            }
        }
        
        $sucesso = true;
        mostrarAlerta('Configurações salvas com sucesso!', 'success');
        redirecionar('/admin/configuracoes/');
        
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
    <title>Configurações - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Configurações Gerais</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Configurações</span>
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
                        <h3 class="card-title"><i class="fas fa-building"></i> Dados da Empresa</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Nome da Empresa</label>
                                <input type="text" name="config[nome_empresa]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'nome_empresa'")['valor'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">CNPJ</label>
                                <input type="text" name="config[cnpj_empresa]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'cnpj_empresa'")['valor'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="config[telefone_empresa]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'telefone_empresa'")['valor'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="config[email_empresa]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'email_empresa'")['valor'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Endereço</label>
                            <input type="text" name="config[endereco_empresa]" class="form-control" 
                                value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'endereco_empresa'")['valor'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cog"></i> Configurações de Cobrança</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Dias de Tolerância Padrão</label>
                                <input type="number" name="config[dias_tolerancia_padrao]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'dias_tolerancia_padrao'")['valor'] ?? '3'; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Multa por Atraso (%)</label>
                                <input type="number" name="config[multa_atraso]" class="form-control" step="0.01"
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'multa_atraso'")['valor'] ?? '2.00'; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Juros ao Dia (%)</label>
                                <input type="number" name="config[juros_dia_atraso]" class="form-control" step="0.01"
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'juros_dia_atraso'")['valor'] ?? '0.33'; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clock"></i> Configurações do Sistema</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">URL do Sistema</label>
                                <input type="text" name="config[url_sistema]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'url_sistema'")['valor'] ?? BASE_URL; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Horário do CRON</label>
                                <input type="time" name="config[hora_cron]" class="form-control" 
                                    value="<?php echo DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'hora_cron'")['valor'] ?? '06:00'; ?>">
                            </div>
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
