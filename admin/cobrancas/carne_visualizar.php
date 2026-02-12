<?php
/**
 * Master Car - Visualizar Carnê/Boleto Gerado
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Recupera dados do carnê gerado
if (!isset($_SESSION['carne_gerado'])) {
    mostrarAlerta('Nenhum carnê para visualizar.', 'danger');
    redirecionar('/admin/cobrancas/boleto_avulso.php');
}

$carne = $_SESSION['carne_gerado'];
$faturas = $carne['faturas'];
$clienteId = $carne['cliente_id'];
$contratoId = $carne['contrato_id'];

// Busca dados do cliente
$cliente = DB()->fetch("
    SELECT c.*, cs.numero_contrato, v.placa, v.marca, v.modelo
    FROM clientes c
    JOIN contratos_semanal cs ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE c.id = ? AND cs.id = ?
", [$clienteId, $contratoId]);

// Configurações
$config = [];
$configs = DB()->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($configs as $c) {
    $config[$c['chave']] = $c['valor'];
}

// Buscar chave PIX
$pixConfig = DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'pix_chave'");
$pixChave = $pixConfig['valor'] ?? '';

// Processar atualização da chave PIX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pix_chave'])) {
    $pixChave = trim($_POST['pix_chave']);
    $existe = DB()->fetch("SELECT id FROM configuracoes WHERE chave = 'pix_chave'");
    if ($existe) {
        DB()->query("UPDATE configuracoes SET valor = ? WHERE chave = 'pix_chave'", [$pixChave]);
    } else {
        DB()->query("INSERT INTO configuracoes (chave, valor, descricao) VALUES ('pix_chave', ?, 'Chave PIX da empresa')", [$pixChave]);
    }
}

// Função para gerar PIX
function gerarPixCopiaCola($chave, $valor, $descricao) {
    $mai = '0014BR.GOV.BCB.PIX' . sprintf('%02d', strlen($chave)) . $chave;
    $tam = sprintf('%02d', strlen($valor));
    $transactionAmount = $tam . $valor;
    $merchantName = 'MasterCar';
    $merchantNameField = sprintf('%02d', strlen($merchantName)) . $merchantName;
    $merchantCity = 'SAOPAULO';
    $merchantCityField = sprintf('%02d', strlen($merchantCity)) . $merchantCity;
    $txid = substr($descricao, 0, 25);
    $adf = '05' . sprintf('%02d', strlen($txid)) . $txid;
    $additionalData = '62' . sprintf('%04d', strlen($adf)) . $adf;
    
    $payload = '000201';
    $payload .= '26' . sprintf('%04d', strlen($mai)) . $mai;
    $payload .= '52040000';
    $payload .= '5303986';
    $payload .= '54' . $transactionAmount;
    $payload .= '5802BR';
    $payload .= '59' . $merchantNameField;
    $payload .= '60' . $merchantCityField;
    $payload .= $additionalData;
    $payload .= '6304';
    
    $crc = crc16($payload);
    $payload .= $crc;
    
    return $payload;
}

function crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(dechex($crc));
}

// Limpa sessão
unset($_SESSION['carne_gerado']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carnê Gerado - <?php echo SITE_NAME; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .boleto-container {
            background: #fff;
            border: 1px solid #000;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .boleto-header {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .logo-area {
            width: 150px;
            padding: 10px;
            border-right: 1px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .logo-area h2 {
            font-size: 12pt;
            color: #003366;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .linha-digitavel-area {
            flex: 1;
            padding: 10px;
        }
        .linha-digitavel-area p {
            font-size: 7pt;
            margin-bottom: 3px;
            color: #666;
        }
        .linha-digitavel {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
        }
        .boleto-section {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .campo {
            padding: 5px 8px;
            border-right: 1px solid #000;
        }
        .campo:last-child { border-right: none; }
        .campo-label {
            font-size: 6pt;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 2px;
        }
        .campo-value {
            font-size: 9pt;
            font-weight: bold;
        }
        .campo-grande { flex: 3; }
        .campo-pequeno { flex: 1; min-width: 100px; }
        .sacado-area {
            padding: 10px;
            border-bottom: 1px solid #000;
        }
        .codigo-barras {
            text-align: center;
            padding: 15px;
            border-bottom: 1px solid #000;
            background: #f8f9fa;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            z-index: 1000;
        }
        .print-btn:hover { background: #1d4ed8; }
        .novo-btn {
            position: fixed;
            top: 20px;
            right: 160px;
            padding: 12px 24px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            text-decoration: none;
            z-index: 1000;
        }
        .novo-btn:hover { background: #059669; }
        @media print {
            .print-btn, .novo-btn, .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .boleto-container { page-break-after: always; }
            .boleto-container:last-child { page-break-after: auto; }
        }
        .corte {
            border-top: 2px dashed #000;
            margin: 15px 0;
            padding-top: 5px;
            font-size: 8pt;
            text-align: right;
        }
        .resumo-carne {
            background: #fff;
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .resumo-carne h2 {
            color: #2563eb;
            margin-bottom: 15px;
        }
        .parcelas-resumo {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .parcela-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .parcela-item .numero {
            font-weight: bold;
            color: #2563eb;
        }
        .parcela-item .data {
            font-size: 11px;
            color: #666;
        }
        .parcela-item .valor {
            font-weight: bold;
        }
        .pix-section {
            background: #fff;
            border: 2px solid #00bfa5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .pix-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .pix-header h2 {
            color: #00bfa5;
            font-family: Arial, sans-serif;
            font-size: 18pt;
        }
        .pix-valor {
            font-size: 24pt;
            font-weight: bold;
            color: #00bfa5;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/boleto_avulso.php" class="novo-btn no-print">
        <i class="fas fa-plus"></i> Novo Carnê
    </a>
    
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir Carnê
    </button>
    
    <div class="container">
        <!-- Resumo do Carnê -->
        <div class="resumo-carne no-print">
            <h2><i class="fas fa-check-circle"></i> Carnê Gerado com Sucesso!</h2>
            <p><strong>Cliente:</strong> <?php echo $cliente['nome']; ?></p>
            <p><strong>Contrato:</strong> <?php echo $cliente['numero_contrato']; ?></p>
            <p><strong>Veículo:</strong> <?php echo $cliente['marca'] . ' ' . $cliente['modelo'] . ' (' . $cliente['placa'] . ')'; ?></p>
            <p><strong>Total de Parcelas:</strong> <?php echo count($faturas); ?></p>
            
            <div class="parcelas-resumo">
                <?php foreach ($faturas as $fat): ?>
                    <div class="parcela-item">
                        <div class="numero">Parcela <?php echo $fat['parcela']; ?>/<?php echo count($faturas); ?></div>
                        <div class="data"><?php echo formatarData($fat['vencimento']); ?></div>
                        <div class="valor">R$ <?php echo number_format($fat['valor'], 2, ',', '.'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                <p><strong>Total do Carnê:</strong> R$ <?php echo number_format(array_sum(array_column($faturas, 'valor')), 2, ',', '.'); ?></p>
                <p style="font-size: 12px; color: #666;">As faturas foram salvas no sistema e o cliente pode visualizá-las em sua área.</p>
            </div>
        </div>
        
        <?php foreach ($faturas as $index => $fatura): 
            $valorTotal = (float) $fatura['valor'];
            $diasAtraso = 0;
            if ($fatura['vencimento'] < date('Y-m-d')) {
                $diasAtraso = diasEntre($fatura['vencimento'], date('Y-m-d'));
            }
            $valorLimpo = str_replace(['.', ','], ['', ''], number_format($valorTotal, 2, ',', ''));
            $linhaDigitavel = '34191.79001 01043.510047 61024.30000 6 840200000' . $valorLimpo;
            $nossoNumero = '000' . substr(preg_replace('/[^0-9]/', '', $fatura['numero']), -8);
        ?>
        
        <!-- Boleto da Parcela -->
        <div class="boleto-container">
            <div style="background: #2563eb; color: white; padding: 5px 10px; text-align: center; font-weight: bold;">
                PARCELA <?php echo $fatura['parcela']; ?>/<?php echo count($faturas); ?> - VENCIMENTO: <?php echo formatarData($fatura['vencimento']); ?>
            </div>
            
            <div class="boleto-header">
                <div class="logo-area">
                    <h2><?php echo substr($config['nome_empresa'] ?? SITE_NAME, 0, 15); ?></h2>
                </div>
                <div class="linha-digitavel-area">
                    <p>Linha Digitável</p>
                    <div class="linha-digitavel"><?php echo wordwrap($linhaDigitavel, 11, ' ', true); ?></div>
                </div>
            </div>
            
            <div class="boleto-section">
                <div class="campo campo-grande">
                    <div class="campo-label">Local de Pagamento</div>
                    <div class="campo-value">Pagável em qualquer banco, casa lotérica ou internet banking</div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Vencimento</div>
                    <div class="campo-value"><?php echo formatarData($fatura['vencimento']); ?></div>
                </div>
            </div>
            
            <div class="boleto-section">
                <div class="campo campo-grande">
                    <div class="campo-label">Cedente</div>
                    <div class="campo-value"><?php echo $config['nome_empresa'] ?? SITE_NAME; ?> - CNPJ: <?php echo formatarCpfCnpj($config['cnpj_empresa'] ?? ''); ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Agência/Código Cedente</div>
                    <div class="campo-value">0001/12345-6</div>
                </div>
            </div>
            
            <div class="boleto-section">
                <div class="campo campo-pequeno">
                    <div class="campo-label">Data do Documento</div>
                    <div class="campo-value"><?php echo date('d/m/Y'); ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Número do Documento</div>
                    <div class="campo-value"><?php echo $fatura['numero']; ?></div>
                </div>
                <div class="campo" style="width: 80px;">
                    <div class="campo-label">Espécie Doc.</div>
                    <div class="campo-value">DM</div>
                </div>
                <div class="campo" style="width: 60px;">
                    <div class="campo-label">Aceite</div>
                    <div class="campo-value">N</div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Data Processamento</div>
                    <div class="campo-value"><?php echo date('d/m/Y'); ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Nosso Número</div>
                    <div class="campo-value"><?php echo $nossoNumero; ?></div>
                </div>
            </div>
            
            <div class="boleto-section">
                <div class="campo" style="width: 100px;">
                    <div class="campo-label">Uso do Banco</div>
                    <div class="campo-value"></div>
                </div>
                <div class="campo" style="width: 80px;">
                    <div class="campo-label">Carteira</div>
                    <div class="campo-value">109</div>
                </div>
                <div class="campo" style="width: 80px;">
                    <div class="campo-label">Espécie Moeda</div>
                    <div class="campo-value">R$</div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Quantidade</div>
                    <div class="campo-value"></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">(x) Valor</div>
                    <div class="campo-value"></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">(=) Valor Documento</div>
                    <div class="campo-value"><?php echo formatarMoeda($valorTotal); ?></div>
                </div>
            </div>
            
            <div class="boleto-section">
                <div class="campo campo-grande" style="padding: 10px; font-size: 8pt;">
                    <strong>Instruções:</strong> <?php echo $cliente['marca'] . ' ' . $cliente['modelo'] . ' (' . $cliente['placa'] . ')'; ?> - Parcela <?php echo $fatura['parcela']; ?>/<?php echo count($faturas); ?>
                    <?php if ($diasAtraso > 0): ?>
                    <p style="color: #c00; font-weight: bold; margin-top: 5px;">ATENÇÃO: <?php echo $diasAtraso; ?> DIAS EM ATRASO</p>
                    <?php endif; ?>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(-) Desconto / Abatimento</div>
                        <div class="campo-value"></div>
                    </div>
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(-) Outras Deduções</div>
                        <div class="campo-value"></div>
                    </div>
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(+) Mora / Multa</div>
                        <div class="campo-value"></div>
                    </div>
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(+) Outros Acréscimos</div>
                        <div class="campo-value"></div>
                    </div>
                    <div class="campo">
                        <div class="campo-label">(=) Valor Cobrado</div>
                        <div class="campo-value"><?php echo formatarMoeda($valorTotal); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="sacado-area">
                <div class="campo-label">Sacado</div>
                <div class="campo-value">
                    <?php echo $cliente['nome']; ?> - CPF/CNPJ: <?php echo formatarCpfCnpj($cliente['cpf_cnpj']); ?><br>
                    <?php echo $cliente['endereco']; ?>, <?php echo $cliente['numero']; ?> - <?php echo $cliente['bairro']; ?><br>
                    <?php echo $cliente['cidade']; ?>/<?php echo $cliente['estado']; ?> - CEP: <?php echo $cliente['cep']; ?>
                </div>
            </div>
            
            <div class="codigo-barras">
                <svg width="400" height="60" viewBox="0 0 400 60" style="margin: 0 auto;">
                    <?php
                    $x = 10;
                    for ($i = 0; $i < 60; $i++) {
                        $largura = rand(1, 4);
                        echo '<rect x="' . $x . '" y="5" width="' . $largura . '" height="50" fill="#000"/>';
                        $x += $largura + rand(1, 3);
                    }
                    ?>
                </svg>
                <p style="font-size: 10pt; letter-spacing: 3px; font-family: monospace;"><?php echo $linhaDigitavel; ?></p>
            </div>
        </div>
        
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 20px; font-size: 8pt; color: #666;">
            Carnê gerado pelo Sistema Master Car em <?php echo date('d/m/Y H:i:s'); ?><br>
            Documento para controle interno. Baixa manual no sistema.
        </div>
    </div>
</body>
</html>
