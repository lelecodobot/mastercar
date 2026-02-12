<?php
/**
 * Master Car - Visualizar Boleto Avulso
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Recupera dados do boleto avulso
if (!isset($_SESSION['boleto_avulso'])) {
    mostrarAlerta('Nenhum boleto para visualizar.', 'danger');
    redirecionar('/admin/cobrancas/boleto_avulso.php');
}

$fatura = $_SESSION['boleto_avulso'];

// Garante que o valor é numérico
$valorTotal = (float) ($fatura['valor_total'] ?? 0);

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

// Calcula dias de atraso
$diasAtraso = 0;
if ($fatura['data_vencimento'] < date('Y-m-d')) {
    $diasAtraso = diasEntre($fatura['data_vencimento'], date('Y-m-d'));
}

// Gera linha digitável simulada
$valorLimpo = str_replace(['.', ','], ['', ''], number_format($valorTotal, 2, ',', ''));
$linhaDigitavel = '34191.79001 01043.510047 61024.30000 6 840200000' . $valorLimpo;

// Nosso número
$nossoNumero = '000' . substr(preg_replace('/[^0-9]/', '', $fatura['numero_fatura']), -8);

// Data de processamento
$dataProcessamento = date('d/m/Y');

// Gerar QR Code PIX
$pixCopiaCola = '';
if (!empty($pixChave)) {
    $pixValor = number_format($valorTotal, 2, '.', '');
    $pixCopiaCola = gerarPixCopiaCola($pixChave, $pixValor, $fatura['numero_fatura']);
}

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

// Limpa sessão após visualização
unset($_SESSION['boleto_avulso']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleto Avulso - <?php echo $fatura['numero_fatura']; ?></title>
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
        .container { max-width: 900px; margin: 0 auto; }
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
        .barras-numeros {
            font-size: 10pt;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
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
        }
        .corte {
            border-top: 2px dashed #000;
            margin: 15px 0;
            padding-top: 5px;
            font-size: 8pt;
            text-align: right;
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
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
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
        .pix-qr { text-align: center; }
        .pix-info { flex: 1; min-width: 300px; }
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
        .pix-chave-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11pt;
        }
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
        .pix-copia-cola {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .pix-copia-cola textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #4caf50;
            border-radius: 4px;
            font-size: 9pt;
            font-family: monospace;
            resize: none;
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
    </style>
</head>
<body>
    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/boleto_avulso.php" class="novo-btn">
        <i class="fas fa-plus"></i> Novo Boleto
    </a>
    
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir
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
                        R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?>
                    </div>
                    
                    <form method="POST" action="" class="no-print">
                        <div class="pix-chave-box">
                            <label><i class="fas fa-key"></i> Chave PIX da Empresa:</label>
                            <input type="text" name="pix_chave" value="<?php echo htmlspecialchars($pixChave); ?>" 
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
                </div>
            </div>
        </div>
        
        <!-- Boleto -->
        <div class="boleto-container">
            <div class="boleto-header">
                <div class="logo-area">
                    <h2><?php echo substr($fatura['locadora_nome'], 0, 15); ?></h2>
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
                    <div class="campo-value"><?php echo formatarData($fatura['data_vencimento']); ?></div>
                </div>
            </div>
            
            <div class="boleto-section">
                <div class="campo campo-grande">
                    <div class="campo-label">Cedente</div>
                    <div class="campo-value"><?php echo $fatura['locadora_nome']; ?> - CNPJ: <?php echo formatarCpfCnpj($fatura['locadora_cnpj']); ?></div>
                </div>
                <div class="campo campo-pequeno">
                    <div class="campo-label">Agência/Código Cedente</div>
                    <div class="campo-value">0001/12345-6</div>
                </div>
            </div>
            
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
                    <strong>Instruções:</strong> <?php echo $fatura['descricao']; ?>
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
                    <?php echo $fatura['cliente_nome']; ?> - CPF/CNPJ: <?php echo formatarCpfCnpj($fatura['cliente_cpf_cnpj']); ?><br>
                    <?php echo $fatura['cliente_endereco']; ?>, <?php echo $fatura['cliente_numero']; ?> - <?php echo $fatura['cliente_bairro']; ?><br>
                    <?php echo $fatura['cliente_cidade']; ?>/<?php echo $fatura['cliente_estado']; ?> - CEP: <?php echo $fatura['cliente_cep']; ?>
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
                <p class="barras-numeros"><?php echo $linhaDigitavel; ?></p>
            </div>
        </div>
        
        <div class="corte">Corte aqui -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</div>
        
        <div class="boleto-container">
            <div style="padding: 10px; border-bottom: 1px solid #000;">
                <strong>RECIBO DO SACADO</strong>
            </div>
            <div style="padding: 10px;">
                <p><strong>Cedente:</strong> <?php echo $fatura['locadora_nome']; ?></p>
                <p><strong>Sacado:</strong> <?php echo $fatura['cliente_nome']; ?></p>
                <p><strong>Referente a:</strong> <?php echo $fatura['descricao']; ?></p>
                <p><strong>Vencimento:</strong> <?php echo formatarData($fatura['data_vencimento']); ?></p>
                <p><strong>Valor:</strong> <?php echo formatarMoeda($valorTotal); ?></p>
                <p style="margin-top: 10px; font-size: 8pt;">Recebi de <?php echo $fatura['cliente_nome']; ?> a importância acima.</p>
                <div style="margin-top: 30px; text-align: center;">
                    <div style="border-top: 1px solid #000; width: 300px; margin: 0 auto; padding-top: 5px;">
                        Assinatura do Cedente
                    </div>
                    <p style="font-size: 8pt; margin-top: 5px;"><?php echo $dataProcessamento; ?></p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 8pt; color: #666;">
            Boleto avulso gerado pelo Sistema Master Car em <?php echo date('d/m/Y H:i:s'); ?><br>
            Documento para controle interno.
        </div>
    </div>
    
    <script>
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
                alert('Código PIX copiado!');
            });
        }
    </script>
</body>
</html>
