<?php
/**
 * Master Car - Gerar Boleto/Carnê Avulso - Salva fatura no banco
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Buscar contratos ativos com dados completos
$contratos = DB()->fetchAll("
    SELECT cs.*, 
           c.id as cliente_id, c.nome as cliente_nome, c.cpf_cnpj, c.rg_ie, 
           c.cnh_numero, c.endereco, c.numero, c.bairro, c.cidade, c.estado, c.cep,
           c.telefone, c.email,
           v.placa, v.marca, v.modelo, v.ano_modelo, v.cor, v.chassi
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.status = 'ativo'
    ORDER BY c.nome
");

// Buscar configurações
$config = [];
$configs = DB()->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($configs as $c) {
    $config[$c['chave']] = $c['valor'];
}

// Processar geração de boleto/carnê
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contratoId = $_POST['contrato_id'] ?? 0;
    $clienteId = $_POST['cliente_id'] ?? 0;
    $tipoCobranca = $_POST['tipo_cobranca'] ?? 'unica'; // unica ou carne
    $quantidadeParcelas = (int) ($_POST['quantidade_parcelas'] ?? 1);
    $valorParcela = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_parcela'] ?? '0');
    $valorNumerico = (float) $valorParcela;
    $dataPrimeiroVencimento = $_POST['data_primeiro_vencimento'] ?? date('Y-m-d', strtotime('+7 days'));
    $descricao = $_POST['descricao'] ?? '';
    
    $faturasGeradas = [];
    $numeroFaturaBase = 'FAT-' . date('Ymd') . '-' . rand(1000, 9999);
    
    try {
        DB()->beginTransaction();
        
        if ($tipoCobranca === 'carne' && $quantidadeParcelas > 1) {
            // Gera carnê com múltiplas parcelas
            for ($i = 1; $i <= $quantidadeParcelas; $i++) {
                $dataVencimento = date('Y-m-d', strtotime($dataPrimeiroVencimento . ' + ' . (($i - 1) * 7) . ' days'));
                $numeroFatura = $numeroFaturaBase . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                $faturaId = DB()->insert('faturas_semanal', [
                    'cliente_id' => $clienteId,
                    'contrato_id' => $contratoId,
                    'numero_fatura' => $numeroFatura,
                    'descricao' => $descricao . ' - Parcela ' . $i . '/' . $quantidadeParcelas,
                    'valor_total' => $valorNumerico,
                    'valor_original' => $valorNumerico,
                    'data_emissao' => date('Y-m-d'),
                    'data_vencimento' => $dataVencimento,
                    'status' => 'pendente',
                    'gateway' => 'local',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $faturasGeradas[] = [
                    'id' => $faturaId,
                    'numero' => $numeroFatura,
                    'parcela' => $i,
                    'vencimento' => $dataVencimento,
                    'valor' => $valorNumerico
                ];
            }
        } else {
            // Gera fatura única
            $faturaId = DB()->insert('faturas_semanal', [
                'cliente_id' => $clienteId,
                'contrato_id' => $contratoId,
                'numero_fatura' => $numeroFaturaBase,
                'descricao' => $descricao,
                'valor_total' => $valorNumerico,
                'valor_original' => $valorNumerico,
                'data_emissao' => date('Y-m-d'),
                'data_vencimento' => $dataPrimeiroVencimento,
                'status' => 'pendente',
                'gateway' => 'local',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $faturasGeradas[] = [
                'id' => $faturaId,
                'numero' => $numeroFaturaBase,
                'parcela' => 1,
                'vencimento' => $dataPrimeiroVencimento,
                'valor' => $valorNumerico
            ];
        }
        
        DB()->commit();
        
        // Redireciona para visualização do carnê/boleto
        $_SESSION['carne_gerado'] = [
            'faturas' => $faturasGeradas,
            'tipo' => $tipoCobranca,
            'cliente_id' => $clienteId,
            'contrato_id' => $contratoId
        ];
        
        mostrarAlerta('Fatura(s) gerada(s) com sucesso!', 'success');
        redirecionar('/admin/cobrancas/carne_visualizar.php');
        
    } catch (Exception $e) {
        DB()->rollBack();
        $erro = 'Erro ao gerar fatura: ' . $e->getMessage();
    }
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Boleto/Carnê - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tipo-cobranca {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .tipo-option {
            flex: 1;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .tipo-option:hover {
            border-color: #2563eb;
        }
        .tipo-option.active {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .tipo-option i {
            font-size: 32px;
            margin-bottom: 10px;
            color: #2563eb;
        }
        .tipo-option h4 {
            margin-bottom: 5px;
        }
        .tipo-option p {
            font-size: 12px;
            color: #666;
        }
        #carneOptions {
            display: none;
        }
        #carneOptions.visible {
            display: block;
        }
    </style>
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
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $erro; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Gerar Boleto/Carnê</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/">Cobranças</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Boleto/Carnê</span>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="" id="formBoleto">
                <input type="hidden" name="cliente_id" id="clienteIdHidden">
                
                <!-- Seleção do Contrato -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-contract"></i> Selecionar Contrato</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Contrato Ativo *</label>
                            <select name="contrato_id" id="contratoSelect" class="form-control" required onchange="preencherDadosContrato()">
                                <option value="">-- Selecione um contrato --</option>
                                <?php foreach ($contratos as $ctr): ?>
                                    <option value="<?php echo $ctr['id']; ?>" 
                                            data-cliente-id="<?php echo $ctr['cliente_id']; ?>"
                                            data-cliente="<?php echo htmlspecialchars(json_encode([
                                                'nome' => $ctr['cliente_nome'],
                                                'cpf_cnpj' => $ctr['cpf_cnpj'],
                                                'endereco' => $ctr['endereco'],
                                                'numero' => $ctr['numero'],
                                                'bairro' => $ctr['bairro'],
                                                'cidade' => $ctr['cidade'],
                                                'estado' => $ctr['estado'],
                                                'cep' => $ctr['cep']
                                            ])); ?>"
                                            data-veiculo="<?php echo htmlspecialchars(json_encode([
                                                'placa' => $ctr['placa'],
                                                'marca' => $ctr['marca'],
                                                'modelo' => $ctr['modelo']
                                            ])); ?>"
                                            data-valor="<?php echo $ctr['valor_semanal']; ?>"
                                            data-contrato="<?php echo $ctr['numero_contrato']; ?>">
                                        <?php echo $ctr['numero_contrato']; ?> - <?php echo $ctr['cliente_nome']; ?> 
                                        (<?php echo $ctr['placa']; ?> - R$ <?php echo number_format($ctr['valor_semanal'], 2, ',', '.'); ?>/semana)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: var(--gray);">Selecione o contrato para preencher automaticamente os dados</small>
                        </div>
                    </div>
                </div>
                
                <!-- Dados do Cliente (Somente Leitura) -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Dados do Cliente</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Nome do Cliente</label>
                                <input type="text" id="clienteNome" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" id="clienteCpf" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tipo de Cobrança -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list-alt"></i> Tipo de Cobrança</h3>
                    </div>
                    <div class="card-body">
                        <div class="tipo-cobranca">
                            <div class="tipo-option active" onclick="selecionarTipo('unica')">
                                <i class="fas fa-file-invoice"></i>
                                <h4>Fatura Única</h4>
                                <p>Gera uma única fatura semanal</p>
                            </div>
                            <div class="tipo-option" onclick="selecionarTipo('carne')">
                                <i class="fas fa-book"></i>
                                <h4>Carnê</h4>
                                <p>Gera múltiplas parcelas semanais</p>
                            </div>
                        </div>
                        <input type="hidden" name="tipo_cobranca" id="tipoCobranca" value="unica">
                        
                        <div id="carneOptions">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Quantidade de Parcelas</label>
                                    <select name="quantidade_parcelas" class="form-control">
                                        <?php for ($i = 2; $i <= 24; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i == 12 ? 'selected' : ''; ?>><?php echo $i; ?> parcelas</option>
                                        <?php endfor; ?>
                                    </select>
                                    <small style="color: var(--gray);">Número de semanas (parcelas) do carnê</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dados da Cobrança -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Dados da Cobrança Semanal</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Descrição *</label>
                                <input type="text" name="descricao" id="descricao" class="form-control" required 
                                       placeholder="Ex: Locação semanal - Semana 01/2024">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Valor da Parcela (R$) *</label>
                                <input type="text" name="valor_parcela" id="valorParcela" class="form-control" required 
                                       placeholder="0,00" data-mask="moeda">
                                <small style="color: var(--gray);">Valor cobrado a cada 7 dias</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Primeiro Vencimento *</label>
                                <input type="date" name="data_primeiro_vencimento" class="form-control" required 
                                       value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                                <small style="color: var(--gray);">Data do primeiro vencimento</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/" class="btn btn-light">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-barcode"></i> Gerar Boleto/Carnê
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function selecionarTipo(tipo) {
            document.getElementById('tipoCobranca').value = tipo;
            document.querySelectorAll('.tipo-option').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            if (tipo === 'carne') {
                document.getElementById('carneOptions').classList.add('visible');
            } else {
                document.getElementById('carneOptions').classList.remove('visible');
            }
        }
        
        function preencherDadosContrato() {
            var select = document.getElementById('contratoSelect');
            var option = select.options[select.selectedIndex];
            
            if (option.value === '') {
                document.getElementById('clienteIdHidden').value = '';
                document.getElementById('clienteNome').value = '';
                document.getElementById('clienteCpf').value = '';
                document.getElementById('valorParcela').value = '';
                document.getElementById('descricao').value = '';
                return;
            }
            
            var clienteData = JSON.parse(option.getAttribute('data-cliente'));
            var veiculoData = JSON.parse(option.getAttribute('data-veiculo'));
            var valorSemanal = option.getAttribute('data-valor');
            var clienteId = option.getAttribute('data-cliente-id');
            
            // Preenche cliente
            document.getElementById('clienteIdHidden').value = clienteId;
            document.getElementById('clienteNome').value = clienteData.nome;
            document.getElementById('clienteCpf').value = clienteData.cpf_cnpj;
            
            // Preenche valor
            var valorFormatado = parseFloat(valorSemanal).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('valorParcela').value = valorFormatado;
            
            // Preenche descrição
            document.getElementById('descricao').value = 'Locação semanal - ' + veiculoData.marca + ' ' + veiculoData.modelo + ' (' + veiculoData.placa + ')';
        }
    </script>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
