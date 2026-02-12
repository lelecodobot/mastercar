<?php
/**
 * Master Car - Geração de Boleto Local com PIX (Sem Gateway)
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$faturaId = $_GET['id'] ?? 0;

// Busca fatura com dados completos
$fatura = DB()->fetch("
    SELECT f.*, c.nome as cliente_nome, c.cpf_cnpj, c.endereco, c.numero, 
           c.bairro, c.cidade, c.estado, c.cep, c.telefone, c.email,
           cs.numero_contrato, v.placa, v.marca, v.modelo,
           l1.valor as locadora_nome, l2.valor as locadora_cnpj,
           l3.valor as locadora_endereco, l4.valor as locadora_telefone,
           l5.valor as pix_chave
    FROM faturas_semanal f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    LEFT JOIN configuracoes l1 ON l1.chave = 'nome_empresa'
    LEFT JOIN configuracoes l2 ON l2.chave = 'cnpj_empresa'
    LEFT JOIN configuracoes l3 ON l3.chave = 'endereco_empresa'
    LEFT JOIN configuracoes l4 ON l4.chave = 'telefone_empresa'
    LEFT JOIN configuracoes l5 ON l5.chave = 'pix_chave'
    WHERE f.id = ?
", [$faturaId]);

if (!$fatura) {
    echo '<div style="padding: 20px; text-align: center;"><h2>Fatura não encontrada.</h2></div>';
    exit;
}

// Processar atualização da chave PIX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pix_chave'])) {
    $pixChave = trim($_POST['pix_chave']);
    DB()->query("INSERT INTO configuracoes (chave, valor, descricao) VALUES ('pix_chave', ?, 'Chave PIX da empresa') ON DUPLICATE KEY UPDATE valor = ?", [$pixChave, $pixChave]);
    $fatura['pix_chave'] = $pixChave;
}

// Calcula dias de atraso
$diasAtraso = 0;
if ($fatura['status'] == 'vencido' || ($fatura['status'] == 'pendente' && $fatura['data_vencimento'] < date('Y-m-d'))) {
    $diasAtraso = diasEntre($fatura['data_vencimento'], date('Y-m-d'));
}

// Gera linha digitável simulada
$valorLimpo = str_replace(['.', ','], ['', ''], number_format($fatura['valor_total'], 2, ',', ''));
$linhaDigitavel = '34191.79001 01043.510047 61024.30000 6 840200000' . $valorLimpo;

// Nosso número
$nossoNumero = '000' . str_pad($faturaId, 8, '0', STR_PAD_LEFT);

// Data de processamento
$dataProcessamento = date('d/m/Y');

// Gerar QR Code PIX se tiver chave
$pixQRCode = '';
$pixCopiaCola = '';
if (!empty($fatura['pix_chave'])) {
    // Gera o payload PIX (Copia e Cola)
    $pixValor = number_format($fatura['valor_total'], 2, '.', '');
    $pixCopiaCola = gerarPixCopiaCola($fatura['pix_chave'], $pixValor, $fatura['numero_fatura']);
}

/**
 * Gera o payload PIX Copia e Cola
 */
function gerarPixCopiaCola($chave, $valor, $descricao) {
    // Merchant Account Information
    $mai = '0014BR.GOV.BCB.PIX' . sprintf('%02d', strlen($chave)) . $chave;
    
    // Transaction Amount
    $tam = sprintf('%02d', strlen($valor));
    $transactionAmount = $tam . $valor;
    
    // Merchant Name (simplificado)
    $merchantName = 'MasterCar';
    $merchantNameField = sprintf('%02d', strlen($merchantName)) . $merchantName;
    
    // Merchant City
    $merchantCity = 'SAOPAULO';
    $merchantCityField = sprintf('%02d', strlen($merchantCity)) . $merchantCity;
    
    // Additional Data Field (descrição)
    $txid = substr($descricao, 0, 25);
    $adf = '05' . sprintf('%02d', strlen($txid)) . $txid;
    $additionalData = '62' . sprintf('%04d', strlen($adf)) . $adf;
    
    // Monta o payload
    $payload = '000201';
    $payload .= '26' . sprintf('%04d', strlen($mai)) . $mai;
    $payload .= '52040000'; // Merchant Category Code
    $payload .= '5303986'; // Currency (986 = BRL)
    $payload .= '54' . $transactionAmount;
    $payload .= '5802BR'; // Country Code
    $payload .= '59' . $merchantNameField;
    $payload .= '60' . $merchantCityField;
    $payload .= $additionalData;
    $payload .= '6304'; // CRC16 placeholder
    
    // Calcula CRC16
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleto + PIX - <?php echo $fatura['numero_fatura']; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .boleto-container {
            background: #fff;
            border: 1px solid #000;
            margin-bottom: 20px;
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
        .campo:last-child {
            border-right: none;
        }
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
        .campo-medio { flex: 2; }
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
        .barras {
            font-size: 40pt;
            letter-spacing: 2px;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', monospace;
        }
        .barras-numeros {
            font-size: 10pt;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        .autenticacao {
            padding: 10px;
            border-bottom: 1px solid #000;
        }
        .autenticacao-label {
            font-size: 7pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }
        .instrucoes {
            padding: 10px;
            font-size: 8pt;
        }
        .instrucoes h4 {
            font-size: 9pt;
            margin-bottom: 5px;
        }
        .instrucoes p {
            margin-bottom: 3px;
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
        @media print {
            .print-btn, .no-print { display: none !important; }
            body { padding: 0; background: #fff; }
            .boleto-container { border: 1px solid #000; }
        }
        .corte {
            border-top: 2px dashed #000;
            margin: 15px 0;
            padding-top: 5px;
            font-size: 8pt;
            text-align: right;
        }
        
        /* Seção PIX */
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
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .pix-header img {
            width: 40px;
            height: 40px;
        }
        .pix-header h2 {
            color: #00bfa5;
            font-family: Arial, sans-serif;
            font-size: 18pt;
        }
        .pix-content {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .pix-qr {
            text-align: center;
        }
        .pix-qr img, #qrcode img {
            margin: 0 auto;
        }
        .pix-info {
            flex: 1;
            min-width: 300px;
        }
        .pix-valor {
            font-size: 24pt;
            font-weight: bold;
            color: #00bfa5;
            margin-bottom: 15px;
        }
        .pix-chave-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .pix-chave-box label {
            font-size: 10pt;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .pix-chave-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11pt;
            font-family: monospace;
        }
        .pix-copia-cola {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .pix-copia-cola label {
            font-size: 10pt;
            color: #2e7d32;
            display: block;
            margin-bottom: 5px;
        }
        .pix-copia-cola textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #4caf50;
            border-radius: 4px;
            font-size: 9pt;
            font-family: monospace;
            resize: none;
            background: #fff;
        }
        .btn-copiar {
            background: #00bfa5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11pt;
            margin-top: 10px;
        }
        .btn-copiar:hover { background: #008e76; }
        .btn-salvar {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10pt;
            margin-top: 10px;
        }
        .pix-instrucoes {
            background: #fff3e0;
            padding: 15px;
            border-radius: 8px;
            font-size: 10pt;
        }
        .pix-instrucoes h4 {
            color: #e65100;
            margin-bottom: 10px;
        }
        .pix-instrucoes ol {
            padding-left: 20px;
        }
        .pix-instrucoes li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir Boleto + PIX
    </button>
    
    <div class="container">
        <!-- Seção PIX -->
        <div class="pix-section no-print">
            <div class="pix-header">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="40" height="40" rx="8" fill="#00BFA5"/>
                    <path d="M28.5 15.5L23 21L20.5 18.5L15.5 23.5" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="2" fill="white"/>
                </svg>
                <h2>Pague com PIX</h2>
            </div>
            
            <div class="pix-content">
                <div class="pix-qr">
                    <div id="qrcode"></div>
                    <p style="margin-top: 10px; font-size: 9pt; color: #666;">Escaneie o QR Code</p>
                </div>
                
                <div class="pix-info">
                    <div class="pix-valor">
                        R$ <?php echo number_format($fatura['valor_total'], 2, ',', '.'); ?>
                    </div>
                    
                    <form method="POST" action="" class="no-print">
                        <div class="pix-chave-box">
                            <label><i class="fas fa-key"></i> Chave PIX da Empresa:</label>
                            <input type="text" name="pix_chave" value="<?php echo htmlspecialchars($fatura['pix_chave'] ?? ''); ?>" 
                                   placeholder="CPF, CNPJ, Email, Celular ou Chave Aleatória">
                            <button type="submit" class="btn-salvar">
                                <i class="fas fa-save"></i> Salvar Chave
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($pixCopiaCola)): ?>
                    <div class="pix-copia-cola">
                        <label><i class="fas fa-copy"></i> Copia e Cola:</label>
                        <textarea id="pixCode" rows="4" readonly><?php echo $pixCopiaCola; ?></textarea>
                        <button class="btn-copiar" onclick="copiarPix()">
                            <i class="fas fa-copy"></i> Copiar Código PIX
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pix-instrucoes">
                        <h4><i class="fas fa-info-circle"></i> Como pagar com PIX:</h4>
                        <ol>
                            <li>Abra o aplicativo do seu banco</li>
                            <li>Escolha a opção "Pagar com PIX"</li>
                            <li>Escaneie o QR Code ou cole o código "Copia e Cola"</li>
                            <li>Confirme o valor e finalize o pagamento</li>
                            <li>O pagamento será compensado em segundos</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Boleto -->
        <div class="boleto-container">
            <!-- Cabeçalho com Logo e Linha Digitável -->
            <div class="boleto-header">
                <div class="logo-area">
                    <h2><?php echo substr($fatura['locadora_nome'] ?? SITE_NAME, 0, 15); ?></h2>
                </div>
                <div class="linha-digitavel-area">
                    <p>Linha Digitável</p>
                    <div class="linha-digitavel"><?php echo wordwrap($linhaDigitavel, 11, ' ', true); ?></div>
                </div>
            </div>
            
            <!-- Local de Pagamento -->
            <div class="boleto-section">
                <div class="campo campo-grande">
                    <div class="campo-label">Local de Pagamento</div>
                    <div class="campo-value">Pagável em qualquer banco, casa lotérica ou internet banking</div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Vencimento</div>
                    <div class="campo-value"><?php echo formatarData($fatura['data_vencimento']); ?></div>
                </div>
            </div>
            
            <!-- Cedente -->
            <div class="boleto-section">
                <div class="campo campo-grande">
                    <div class="campo-label">Cedente</div>
                    <div class="campo-value"><?php echo $fatura['locadora_nome'] ?? SITE_NAME; ?> - CNPJ: <?php echo formatarCpfCnpj($fatura['locadora_cnpj'] ?? ''); ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Agência/Código Cedente</div>
                    <div class="campo-value">0001/12345-6</div>
                </div>
            </div>
            
            <!-- Data Documento, Número Documento, Espécie, Aceite, Data Processamento -->
            <div class="boleto-section">
                <div class="campo campo-pequeno">
                    <div class="campo-label">Data do Documento</div>
                    <div class="campo-value"><?php echo formatarData($fatura['data_emissao']); ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Número do Documento</div>
                    <div class="campo-value"><?php echo $fatura['numero_fatura']; ?></div>
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
                    <div class="campo-value"><?php echo $dataProcessamento; ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Nosso Número</div>
                    <div class="campo-value"><?php echo $nossoNumero; ?></div>
                </div>
            </div>
            
            <!-- Uso do Banco, Carteira, Espécie Moeda, Quantidade, Valor -->
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
                    <div class="campo-value"><?php echo formatarMoeda($fatura['valor_total']); ?></div>
                </div>
            </div>
            
            <!-- Instruções -->
            <div class="boleto-section">
                <div class="campo campo-grande instrucoes">
                    <h4>Instruções (Texto de Responsabilidade do Cedente)</h4>
                    <p>- Após o vencimento, multa de 2% + juros de 0,33% ao dia</p>
                    <p>- Em caso de atraso, entrar em contato: <?php echo $fatura['locadora_telefone'] ?? '(00) 0000-0000'; ?></p>
                    <p>- Referente à locação semanal do veículo <?php echo $fatura['placa']; ?></p>
                    <?php if ($diasAtraso > 0): ?>
                    <p style="color: #c00; font-weight: bold;">- ATENÇÃO: <?php echo $diasAtraso; ?> DIAS EM ATRASO</p>
                    <?php endif; ?>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(-) Desconto / Abatimento</div>
                        <div class="campo-value"><?php echo $fatura['valor_desconto'] > 0 ? formatarMoeda($fatura['valor_desconto']) : ''; ?></div>
                    </div>
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(-) Outras Deduções</div>
                        <div class="campo-value"></div>
                    </div>
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(+) Mora / Multa</div>
                        <div class="campo-value"><?php echo $fatura['valor_multa'] > 0 ? formatarMoeda($fatura['valor_multa']) : ''; ?></div>
                    </div>
                    <div class="campo" style="border-bottom: 1px solid #000;">
                        <div class="campo-label">(+) Outros Acréscimos</div>
                        <div class="campo-value"><?php echo $fatura['valor_juros'] > 0 ? formatarMoeda($fatura['valor_juros']) : ''; ?></div>
                    </div>
                    <div class="campo">
                        <div class="campo-label">(=) Valor Cobrado</div>
                        <div class="campo-value"><?php echo formatarMoeda($fatura['valor_total']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Sacado -->
            <div class="sacado-area">
                <div class="campo-label">Sacado</div>
                <div class="campo-value">
                    <?php echo $fatura['cliente_nome']; ?> - CPF/CNPJ: <?php echo formatarCpfCnpj($fatura['cpf_cnpj']); ?><br>
                    <?php echo $fatura['endereco']; ?>, <?php echo $fatura['numero']; ?> - <?php echo $fatura['bairro']; ?><br>
                    <?php echo $fatura['cidade']; ?>/<?php echo $fatura['estado']; ?> - CEP: <?php echo $fatura['cep']; ?>
                </div>
            </div>
            
            <!-- Código de Barras -->
            <div class="codigo-barras">
                <svg width="400" height="60" viewBox="0 0 400 60" style="margin: 0 auto;">
                    <?php
                    // Gera barras aleatórias para simulação visual
                    $x = 10;
                    for ($i = 0; $i < 60; $i++) {
                        $largura = rand(1, 4);
                        echo '<rect x="' . $x . '" y="5" width="' . $largura . '" height="50" fill="#000"/>';
                        $x += $largura + rand(1, 3);
                    }
                    ?>
                </svg>
                <p class="barras-numeros"><?php echo $linhaDigitavel; ?></p>
            </div>
            
            <!-- Autenticação Mecânica -->
            <div class="autenticacao">
                <div class="autenticacao-label">Autenticação Mecânica</div>
                <div style="height: 30px;"></div>
            </div>
        </div>
        
        <div class="corte">Corte aqui -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</div>
        
        <!-- Recibo do Sacado -->
        <div class="boleto-container">
            <div style="padding: 10px; border-bottom: 1px solid #000;">
                <strong>RECIBO DO SACADO</strong>
            </div>
            <div style="padding: 10px;">
                <p><strong>Cedente:</strong> <?php echo $fatura['locadora_nome'] ?? SITE_NAME; ?></p>
                <p><strong>Sacado:</strong> <?php echo $fatura['cliente_nome']; ?></p>
                <p><strong>Referente a:</strong> Locação semanal veículo <?php echo $fatura['placa']; ?> - <?php echo $fatura['marca']; ?> <?php echo $fatura['modelo']; ?></p>
                <p><strong>Vencimento:</strong> <?php echo formatarData($fatura['data_vencimento']); ?></p>
                <p><strong>Valor:</strong> <?php echo formatarMoeda($fatura['valor_total']); ?></p>
                <p style="margin-top: 10px; font-size: 8pt;">Recebi de <?php echo $fatura['cliente_nome']; ?> a importância acima referente à locação do veículo.</p>
                <div style="margin-top: 30px; text-align: center;">
                    <div style="border-top: 1px solid #000; width: 300px; margin: 0 auto; padding-top: 5px;">
                        Assinatura do Cedente
                    </div>
                    <p style="font-size: 8pt; margin-top: 5px;"><?php echo $dataProcessamento; ?></p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 8pt; color: #666;">
            Boleto gerado pelo Sistema Master Car em <?php echo date('d/m/Y H:i:s'); ?><br>
            Este é um boleto local para registro interno. Para pagamento via banco, utilize o gateway integrado.
        </div>
    </div>
    
    <script>
        // Gerar QR Code PIX
        <?php if (!empty($pixCopiaCola)): ?>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $pixCopiaCola; ?>",
            width: 180,
            height: 180,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        <?php endif; ?>
        
        function copiarPix() {
            var copyText = document.getElementById("pixCode");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(function() {
                alert('Código PIX copiado! Cole no aplicativo do seu banco.');
            });
        }
    </script>
</body>
</html>
