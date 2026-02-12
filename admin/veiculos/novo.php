<?php
/**
 * Master Car - Cadastro de Veículo
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$erros = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validação
    $placa = strtoupper(trim($_POST['placa'] ?? ''));
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    
    if (empty($placa)) {
        $erros[] = 'A placa é obrigatória.';
    }
    
    if (empty($marca)) {
        $erros[] = 'A marca é obrigatória.';
    }
    
    if (empty($modelo)) {
        $erros[] = 'O modelo é obrigatório.';
    }
    
    // Verifica se placa já existe
    $existente = DB()->fetch("SELECT id FROM veiculos WHERE placa = ?", [$placa]);
    if ($existente) {
        $erros[] = 'Já existe um veículo cadastrado com esta placa.';
    }
    
    if (empty($erros)) {
        try {
            $data = [
                'placa' => $placa,
                'marca' => $marca,
                'modelo' => $modelo,
                'ano_fabricacao' => $_POST['ano_fabricacao'] ?: null,
                'ano_modelo' => $_POST['ano_modelo'] ?: null,
                'cor' => $_POST['cor'] ?? '',
                'chassi' => $_POST['chassi'] ?? '',
                'renavam' => $_POST['renavam'] ?? '',
                'categoria' => $_POST['categoria'] ?? 'economico',
                'km_atual' => $_POST['km_atual'] ?: 0,
                'valor_semanal' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_semanal'] ?? '0'),
                'valor_diaria' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_diaria'] ?? '0'),
                'valor_mensal' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_mensal'] ?? '0'),
                'data_aquisicao' => $_POST['data_aquisicao'] ?: null,
                'vencimento_ipva' => $_POST['vencimento_ipva'] ?: null,
                'vencimento_licenciamento' => $_POST['vencimento_licenciamento'] ?: null,
                'seguradora' => $_POST['seguradora'] ?? '',
                'apolice' => $_POST['apolice'] ?? '',
                'vencimento_seguro' => $_POST['vencimento_seguro'] ?: null,
                'observacoes' => $_POST['observacoes'] ?? ''
            ];
            
            $veiculoId = DB()->insert('veiculos', $data);
            
            // Registra log
            registrarLog(null, null, null, 'sistema', 'Veículo cadastrado: ' . $placa);
            
            mostrarAlerta('Veículo cadastrado com sucesso!', 'success');
            redirecionar('/admin/veiculos/');
            
        } catch (Exception $e) {
            $erros[] = 'Erro ao cadastrar veículo: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Veículo - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Novo Veículo</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/veiculos/">Veículos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Novo</span>
                    </div>
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
            
            <form method="POST" action="">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-car"></i> Dados do Veículo</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="width: 120px;">
                                <label class="form-label required">Placa</label>
                                <input type="text" name="placa" class="form-control" maxlength="8" style="text-transform: uppercase;" value="<?php echo $_POST['placa'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Marca</label>
                                <input type="text" name="marca" class="form-control" value="<?php echo $_POST['marca'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Modelo</label>
                                <input type="text" name="modelo" class="form-control" value="<?php echo $_POST['modelo'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Ano Fab.</label>
                                <input type="number" name="ano_fabricacao" class="form-control" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo $_POST['ano_fabricacao'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Ano Mod.</label>
                                <input type="number" name="ano_modelo" class="form-control" min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo $_POST['ano_modelo'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">Cor</label>
                                <input type="text" name="cor" class="form-control" value="<?php echo $_POST['cor'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Chassi</label>
                                <input type="text" name="chassi" class="form-control" value="<?php echo $_POST['chassi'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Renavam</label>
                                <input type="text" name="renavam" class="form-control" value="<?php echo $_POST['renavam'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-control">
                                    <?php foreach ($CATEGORIAS_VEICULO as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($_POST['categoria'] ?? 'economico') == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Valores</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Valor Semanal</label>
                                <input type="text" name="valor_semanal" class="form-control" data-mask="moeda" value="<?php echo $_POST['valor_semanal'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor Diária</label>
                                <input type="text" name="valor_diaria" class="form-control" data-mask="moeda" value="<?php echo $_POST['valor_diaria'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor Mensal</label>
                                <input type="text" name="valor_mensal" class="form-control" data-mask="moeda" value="<?php echo $_POST['valor_mensal'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tachometer-alt"></i> Quilometragem</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">KM Atual</label>
                                <input type="number" name="km_atual" class="form-control" value="<?php echo $_POST['km_atual'] ?? '0'; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data de Aquisição</label>
                                <input type="date" name="data_aquisicao" class="form-control" value="<?php echo $_POST['data_aquisicao'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt"></i> Documentação</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Vencimento IPVA</label>
                                <input type="date" name="vencimento_ipva" class="form-control" value="<?php echo $_POST['vencimento_ipva'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vencimento Licenciamento</label>
                                <input type="date" name="vencimento_licenciamento" class="form-control" value="<?php echo $_POST['vencimento_licenciamento'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Seguradora</label>
                                <input type="text" name="seguradora" class="form-control" value="<?php echo $_POST['seguradora'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Apólice</label>
                                <input type="text" name="apolice" class="form-control" value="<?php echo $_POST['apolice'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vencimento Seguro</label>
                                <input type="date" name="vencimento_seguro" class="form-control" value="<?php echo $_POST['vencimento_seguro'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note"></i> Observações</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <textarea name="observacoes" class="form-control" rows="3"><?php echo $_POST['observacoes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/" class="btn btn-light">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Veículo
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
