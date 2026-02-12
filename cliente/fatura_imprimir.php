<?php
/**
 * Master Car - Fatura para Impressão (Sem Gateway)
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();
$faturaId = $_GET['id'] ?? 0;

// Busca fatura
$fatura = DB()->fetch("
    SELECT f.*, cs.numero_contrato, v.placa, v.marca, v.modelo,
           l1.valor as locadora_nome, l2.valor as locadora_cnpj,
           l3.valor as locadora_endereco, l4.valor as locadora_telefone
    FROM faturas_semanal f
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    LEFT JOIN configuracoes l1 ON l1.chave = 'nome_empresa'
    LEFT JOIN configuracoes l2 ON l2.chave = 'cnpj_empresa'
    LEFT JOIN configuracoes l3 ON l3.chave = 'endereco_empresa'
    LEFT JOIN configuracoes l4 ON l4.chave = 'telefone_empresa'
    WHERE f.id = ? AND f.cliente_id = ?
", [$faturaId, $cliente['id']]);

if (!$fatura) {
    echo 'Fatura não encontrada.';
    exit;
}

$diasAtraso = 0;
if ($fatura['status'] == 'vencido' || ($fatura['status'] == 'pendente' && $fatura['data_vencimento'] < date('Y-m-d'))) {
    $diasAtraso = diasEntre($fatura['data_vencimento'], date('Y-m-d'));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura <?php echo $fatura['numero_fatura']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        .fatura-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2563eb;
        }
        .logo-area h1 {
            color: #2563eb;
            font-size: 28px;
            margin-bottom: 5px;
        }
        .logo-area p {
            color: #666;
            font-size: 11px;
        }
        .fatura-info {
            text-align: right;
        }
        .fatura-info h2 {
            color: #2563eb;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-pendente { background: #fef3c7; color: #92400e; }
        .status-pago { background: #d1fae5; color: #065f46; }
        .status-vencido { background: #fee2e2; color: #991b1b; }
        .dados-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .dados-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }
        .dados-box h3 {
            color: #2563eb;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .dados-box p {
            margin-bottom: 5px;
        }
        .valor-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .valor-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        .valor-row:last-child {
            border-bottom: none;
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #2563eb;
        }
        .codigo-barras {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .codigo-barras .linha {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 3px;
            margin: 15px 0;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
        }
        .instrucoes {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 11px;
        }
        .instrucoes h4 {
            margin-bottom: 10px;
            color: #92400e;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-btn:hover {
            background: #1d4ed8;
        }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .fatura-container { border: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir / Salvar PDF
    </button>
    
    <div class="fatura-container">
        <div class="header">
            <div class="logo-area">
                <h1><?php echo $fatura['locadora_nome'] ?? SITE_NAME; ?></h1>
                <p>CNPJ: <?php echo $fatura['locadora_cnpj'] ?? '00.000.000/0000-00'; ?></p>
                <p><?php echo $fatura['locadora_endereco'] ?? ''; ?></p>
                <p><?php echo $fatura['locadora_telefone'] ?? ''; ?></p>
            </div>
            <div class="fatura-info">
                <h2>FATURA</h2>
                <p><strong>Nº:</strong> <?php echo $fatura['numero_fatura']; ?></p>
                <p><strong>Emissão:</strong> <?php echo formatarData($fatura['data_emissao']); ?></p>
                <p><strong>Vencimento:</strong> <?php echo formatarData($fatura['data_vencimento']); ?></p>
                <br>
                <span class="status status-<?php echo $fatura['status']; ?>">
                    <?php echo strtoupper($STATUS_FATURA[$fatura['status']]['label'] ?? $fatura['status']); ?>
                </span>
                <?php if ($diasAtraso > 0): ?>
                    <p style="color: #dc2626; margin-top: 10px; font-weight: bold;">
                        <?php echo $diasAtraso; ?> DIAS EM ATRASO
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dados-section">
            <div class="dados-box">
                <h3>Cliente</h3>
                <p><strong><?php echo $cliente['nome']; ?></strong></p>
                <p>CPF/CNPJ: <?php echo formatarCpfCnpj($cliente['cpf_cnpj']); ?></p>
                <p><?php echo $cliente['endereco']; ?>, <?php echo $cliente['numero']; ?></p>
                <p><?php echo $cliente['cidade']; ?>/<?php echo $cliente['estado']; ?></p>
                <p>Tel: <?php echo formatarTelefone($cliente['telefone'] ?? $cliente['celular']); ?></p>
            </div>
            <div class="dados-box">
                <h3>Referência</h3>
                <p><strong>Contrato:</strong> <?php echo $fatura['numero_contrato']; ?></p>
                <p><strong>Veículo:</strong> <?php echo $fatura['placa']; ?> - <?php echo $fatura['marca']; ?> <?php echo $fatura['modelo']; ?></p>
                <p><strong>Semana:</strong> <?php echo $fatura['semana_referencia']; ?></p>
                <p><strong>Período:</strong> <?php echo formatarData($fatura['data_referencia']); ?></p>
            </div>
        </div>
        
        <div class="valor-section">
            <div class="valor-row">
                <span>Valor da Locação Semanal</span>
                <span><?php echo formatarMoeda($fatura['valor_original']); ?></span>
            </div>
            <?php if ($fatura['valor_multa'] > 0): ?>
            <div class="valor-row" style="color: #dc2626;">
                <span>Multa por Atraso</span>
                <span>+ <?php echo formatarMoeda($fatura['valor_multa']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($fatura['valor_juros'] > 0): ?>
            <div class="valor-row" style="color: #dc2626;">
                <span>Juros (<?php echo $diasAtraso; ?> dias)</span>
                <span>+ <?php echo formatarMoeda($fatura['valor_juros']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($fatura['valor_desconto'] > 0): ?>
            <div class="valor-row" style="color: #16a34a;">
                <span>Desconto</span>
                <span>- <?php echo formatarMoeda($fatura['valor_desconto']); ?></span>
            </div>
            <?php endif; ?>
            <div class="valor-row">
                <span>TOTAL A PAGAR</span>
                <span><?php echo formatarMoeda($fatura['valor_total']); ?></span>
            </div>
        </div>
        
        <div class="codigo-barras">
            <h3 style="margin-bottom: 15px; color: #333;">Código de Barras para Pagamento</h3>
            <div style="font-size: 48px; letter-spacing: 8px; margin: 20px 0;">
                ||| |||| | |||| || |||| ||||
            </div>
            <div class="linha">
                <?php 
                // Gera linha digitável simulada
                $linha = '34191.79001' . ' 01043.510047' . ' 61024.30000' . ' 6 ' . '840200000' . str_replace(['.', ','], ['', ''], number_format($fatura['valor_total'], 2));
                echo wordwrap($linha, 11, ' ', true);
                ?>
            </div>
            <p style="font-size: 11px; color: #666; margin-top: 10px;">
                Use este código para pagamento em qualquer banco, casa lotérica ou internet banking
            </p>
        </div>
        
        <div class="instrucoes">
            <h4>Instruções de Pagamento</h4>
            <p>1. O pagamento pode ser realizado em qualquer agência bancária, casa lotérica ou via internet banking.</p>
            <p>2. Após o pagamento, o comprovante deve ser enviado pelo portal do cliente ou WhatsApp.</p>
            <p>3. O prazo de compensação é de até 2 dias úteis.</p>
            <p>4. Em caso de atraso, serão aplicados multa de 2% + juros de 0,33% ao dia.</p>
            <p>5. Dúvidas entre em contato: <?php echo $fatura['locadora_telefone'] ?? '(00) 0000-0000'; ?></p>
        </div>
        
        <div class="footer">
            <p><?php echo $fatura['locadora_nome'] ?? SITE_NAME; ?> - Sistema de Gestão de Locadora</p>
            <p>Documento gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
