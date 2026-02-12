<?php
/**
 * Master Car - Cadastro de Contrato
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Busca clientes e veículos disponíveis
$clientes = DB()->fetchAll("SELECT id, nome, cpf_cnpj FROM clientes WHERE status = 'ativo' ORDER BY nome");
$veiculos = DB()->fetchAll("SELECT id, placa, marca, modelo, valor_semanal, ano_fabricacao, ano_modelo, cor, renavam, chassi, combustivel FROM veiculos WHERE status = 'disponivel' ORDER BY marca, modelo");

// Busca modelos de contrato
$modelosContrato = DB()->fetchAll("SELECT * FROM modelos_contrato WHERE ativo = 1 ORDER BY nome");
if (empty($modelosContrato)) {
    // Cria modelos padrão se não existirem
    $modelosContrato = [
        ['id' => 1, 'nome' => 'Contrato Padrão', 'tipo' => 'padrao'],
        ['id' => 2, 'nome' => 'Contrato para Aplicativos', 'tipo' => 'aplicativo']
    ];
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validação
    $clienteId = $_POST['cliente_id'] ?? '';
    $veiculoId = $_POST['veiculo_id'] ?? '';
    $dataInicio = $_POST['data_inicio'] ?? '';
    $valorSemanal = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_semanal'] ?? '0');
    $tipoContrato = $_POST['tipo_contrato'] ?? 'padrao';
    
    if (empty($clienteId)) {
        $erros[] = 'Selecione um cliente.';
    }
    
    if (empty($veiculoId)) {
        $erros[] = 'Selecione um veículo.';
    }
    
    if (empty($dataInicio)) {
        $erros[] = 'A data de início é obrigatória.';
    }
    
    if ($valorSemanal <= 0) {
        $erros[] = 'O valor semanal deve ser maior que zero.';
    }
    
    // Verifica se veículo está disponível
    $veiculo = DB()->fetch("SELECT status FROM veiculos WHERE id = ?", [$veiculoId]);
    if ($veiculo && $veiculo['status'] != 'disponivel') {
        $erros[] = 'O veículo selecionado não está disponível.';
    }
    
    if (empty($erros)) {
        try {
            DB()->beginTransaction();
            
            // Gera número do contrato
            $numeroContrato = gerarNumeroContrato();
            
            // Calcula próxima cobrança (7 dias após início)
            $dataProximaCobranca = date('Y-m-d', strtotime($dataInicio . ' +7 days'));
            
            $data = [
                'cliente_id' => $clienteId,
                'veiculo_id' => $veiculoId,
                'numero_contrato' => $numeroContrato,
                'tipo_contrato' => $tipoContrato,
                'data_inicio' => $dataInicio,
                'data_fim' => $_POST['data_fim'] ?? null,
                'valor_semanal' => $valorSemanal,
                'valor_caucao' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_caucao'] ?? '0'),
                'valor_multa_diaria' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_multa_diaria'] ?? '0'),
                'km_limite_semanal' => $_POST['km_limite_semanal'] ?? 0,
                'valor_km_extra' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_km_extra'] ?? '0'),
                'dias_tolerancia' => $_POST['dias_tolerancia'] ?: DIAS_TOLERANCIA_PADRAO,
                'data_proxima_cobranca' => $dataProximaCobranca,
                'status' => 'ativo',
                'recorrencia_ativa' => 1,
                'observacoes' => $_POST['observacoes'] ?? ''
            ];
            
            $contratoId = DB()->insert('contratos_semanal', $data);
            
            // Atualiza status do veículo
            DB()->update('veiculos', ['status' => 'alugado'], 'id = :id', ['id' => $veiculoId]);
            
            // Registra log
            registrarLog(null, $contratoId, $clienteId, 'sistema', 'Contrato criado: ' . $numeroContrato);
            
            DB()->commit();
            
            mostrarAlerta('Contrato criado com sucesso!', 'success');
            redirecionar('/admin/contratos/');
            
        } catch (Exception $e) {
            DB()->rollback();
            $erros[] = 'Erro ao criar contrato: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Contrato - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Novo Contrato</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/contratos/">Contratos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Novo</span>
                    </div>
                </div>
            </div>
            
            <?php if (empty($clientes)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Não há clientes ativos cadastrados. <a href="<?php echo BASE_URL; ?>/admin/clientes/novo.php">Cadastre um cliente primeiro</a>.</span>
                </div>
            <?php endif; ?>
            
            <?php if (empty($veiculos)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Não há veículos disponíveis. <a href="<?php echo BASE_URL; ?>/admin/veiculos/novo.php">Cadastre um veículo primeiro</a>.</span>
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
                        <h3 class="card-title"><i class="fas fa-user"></i> Cliente e Veículo</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label required">Cliente</label>
                                <select name="cliente_id" class="form-control" required>
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?php echo $cli['id']; ?>" <?php echo ($_POST['cliente_id'] ?? '') == $cli['id'] ? 'selected' : ''; ?>>
                                            <?php echo $cli['nome'] . ' - ' . formatarCpfCnpj($cli['cpf_cnpj']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">Um cliente pode ter múltiplos contratos ativos</div>
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label required">Veículo</label>
                                <select name="veiculo_id" class="form-control" id="veiculo_select" required>
                                    <option value="">Selecione um veículo</option>
                                    <?php foreach ($veiculos as $vei): ?>
                                        <option value="<?php echo $vei['id']; ?>" 
                                                data-valor="<?php echo $vei['valor_semanal']; ?>"
                                                data-marca="<?php echo $vei['marca']; ?>"
                                                data-modelo="<?php echo $vei['modelo']; ?>"
                                                data-ano="<?php echo $vei['ano_modelo']; ?>"
                                                data-cor="<?php echo $vei['cor']; ?>"
                                                data-placa="<?php echo $vei['placa']; ?>"
                                                data-renavam="<?php echo $vei['renavam']; ?>"
                                                data-chassi="<?php echo $vei['chassi']; ?>"
                                                data-combustivel="<?php echo $vei['combustivel']; ?>"
                                                <?php echo ($_POST['veiculo_id'] ?? '') == $vei['id'] ? 'selected' : ''; ?>>
                                            <?php echo $vei['placa'] . ' - ' . $vei['marca'] . ' ' . $vei['modelo']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row" style="margin-top: 15px;">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label required">Tipo de Contrato</label>
                                <select name="tipo_contrato" class="form-control" id="tipo_contrato" required onchange="atualizarModeloContrato()">
                                    <option value="padrao" <?php echo ($_POST['tipo_contrato'] ?? 'padrao') == 'padrao' ? 'selected' : ''; ?>>Contrato Padrão</option>
                                    <option value="aplicativo" <?php echo ($_POST['tipo_contrato'] ?? '') == 'aplicativo' ? 'selected' : ''; ?>>Contrato para Aplicativos (Uber, 99, etc.)</option>
                                </select>
                                <div class="form-hint">Selecione o modelo de contrato adequado ao uso do veículo</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Dados do Contrato</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Data de Início</label>
                                <input type="date" name="data_inicio" class="form-control" value="<?php echo $_POST['data_inicio'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data de Término</label>
                                <input type="date" name="data_fim" class="form-control" value="<?php echo $_POST['data_fim'] ?? ''; ?>">
                                <div class="form-hint">Deixe em branco para contrato indeterminado</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Valor Semanal</label>
                                <input type="text" name="valor_semanal" class="form-control" data-mask="moeda" id="valor_semanal" value="<?php echo $_POST['valor_semanal'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor Caução</label>
                                <input type="text" name="valor_caucao" class="form-control" data-mask="moeda" id="valor_caucao" value="<?php echo $_POST['valor_caucao'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row" id="campos_aplicativo" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Limite KM Semanal</label>
                                <input type="number" name="km_limite_semanal" class="form-control" id="km_limite_semanal" value="<?php echo $_POST['km_limite_semanal'] ?? '1250'; ?>">
                                <div class="form-hint">Ex: 1250 km para aplicativos</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor KM Extra</label>
                                <input type="text" name="valor_km_extra" class="form-control" data-mask="moeda" id="valor_km_extra" value="<?php echo $_POST['valor_km_extra'] ?? '0,50'; ?>">
                                <div class="form-hint">Valor por km excedente</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Multa Diária (atraso)</label>
                                <input type="text" name="valor_multa_diaria" class="form-control" data-mask="moeda" value="<?php echo $_POST['valor_multa_diaria'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dias de Tolerância</label>
                                <input type="number" name="dias_tolerancia" class="form-control" value="<?php echo $_POST['dias_tolerancia'] ?? DIAS_TOLERANCIA_PADRAO; ?>">
                                <div class="form-hint">Dias após vencimento antes de bloquear</div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span>A primeira cobrança será gerada automaticamente 7 dias após a data de início.</span>
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
                    <a href="<?php echo BASE_URL; ?>/admin/contratos/" class="btn btn-light">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" <?php echo (empty($clientes) || empty($veiculos)) ? 'disabled' : ''; ?>>
                        <i class="fas fa-save"></i> Criar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    <script>
        // Preenche valor semanal automaticamente ao selecionar veículo
        document.getElementById('veiculo_select').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const valor = option.getAttribute('data-valor');
            if (valor) {
                document.getElementById('valor_semanal').value = 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
            }
        });
        
        // Atualiza campos baseado no tipo de contrato
        function atualizarModeloContrato() {
            var tipo = document.getElementById('tipo_contrato').value;
            var camposAplicativo = document.getElementById('campos_aplicativo');
            
            if (tipo === 'aplicativo') {
                camposAplicativo.style.display = 'flex';
                // Valores padrão para aplicativos
                if (!document.getElementById('km_limite_semanal').value) {
                    document.getElementById('km_limite_semanal').value = '1250';
                }
                if (!document.getElementById('valor_km_extra').value) {
                    document.getElementById('valor_km_extra').value = '0,50';
                }
                // Sugestão de caução para aplicativos
                if (!document.getElementById('valor_caucao').value) {
                    document.getElementById('valor_caucao').value = '1.500,00';
                }
            } else {
                camposAplicativo.style.display = 'none';
            }
        }
        
        // Executar na carga da página
        atualizarModeloContrato();
    </script>
</body>
</html>
