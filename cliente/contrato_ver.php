<?php
/**
 * Master Car - Visualizar Contrato do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();
$contratoId = $_GET['id'] ?? 0;

// Busca contrato do cliente
$contrato = DB()->fetch("
    SELECT cs.*, 
           c.nome as CLIENTE_NOME, c.cpf_cnpj as CLIENTE_CPF_CNPJ, c.rg_ie as CLIENTE_RG,
           c.cnh_numero as CLIENTE_CNH, c.endereco as CLIENTE_ENDERECO, c.numero as CLIENTE_NUMERO,
           c.bairro as CLIENTE_BAIRRO, c.cidade as CLIENTE_CIDADE, c.estado as CLIENTE_ESTADO,
           c.cep as CLIENTE_CEP, c.telefone as CLIENTE_TELEFONE, c.email as CLIENTE_EMAIL,
           c.data_nascimento as CLIENTE_DATA_NASCIMENTO,
           v.marca as VEICULO_MARCA, v.modelo as VEICULO_MODELO, v.ano_modelo as VEICULO_ANO,
           v.placa as VEICULO_PLACA, v.chassi as VEICULO_CHASSI, v.cor as VEICULO_COR,
           v.km_atual as VEICULO_KM_ATUAL
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.id = ? AND cs.cliente_id = ?
", [$contratoId, $cliente['id']]);

if (!$contrato) {
    mostrarAlerta('Contrato não encontrado.', 'danger');
    redirecionar('/cliente/contratos.php');
}

// Configurações da locadora
$config = [];
$configs = DB()->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($configs as $c) {
    $config[$c['chave']] = $c['valor'];
}

// Variáveis do contrato
$vars = [
    '{{LOCADORA_NOME}}' => $config['nome_empresa'] ?? SITE_NAME,
    '{{LOCADORA_CNPJ}}' => $config['cnpj_empresa'] ?? '00.000.000/0000-00',
    '{{LOCADORA_ENDERECO}}' => $config['endereco_empresa'] ?? '',
    '{{LOCADORA_TELEFONE}}' => $config['telefone_empresa'] ?? '',
    '{{LOCADORA_EMAIL}}' => $config['email_empresa'] ?? '',
    '{{LOCADORA_RESPONSAVEL}}' => $config['responsavel_empresa'] ?? 'Administrador',
    
    '{{CLIENTE_NOME}}' => $contrato['CLIENTE_NOME'] ?? '',
    '{{CLIENTE_CPF_CNPJ}}' => formatarCpfCnpj($contrato['CLIENTE_CPF_CNPJ'] ?? ''),
    '{{CLIENTE_RG}}' => $contrato['CLIENTE_RG'] ?? '',
    '{{CLIENTE_CNH}}' => $contrato['CLIENTE_CNH'] ?? '',
    '{{CLIENTE_ENDERECO}}' => ($contrato['CLIENTE_ENDERECO'] ?? '') . ', ' . ($contrato['CLIENTE_NUMERO'] ?? '') . ' - ' . ($contrato['CLIENTE_BAIRRO'] ?? '') . ', ' . ($contrato['CLIENTE_CIDADE'] ?? '') . '/' . ($contrato['CLIENTE_ESTADO'] ?? '') . ' - CEP: ' . ($contrato['CLIENTE_CEP'] ?? ''),
    '{{CLIENTE_TELEFONE}}' => formatarTelefone($contrato['CLIENTE_TELEFONE'] ?? ''),
    '{{CLIENTE_EMAIL}}' => $contrato['CLIENTE_EMAIL'] ?? '',
    '{{CLIENTE_DATA_NASCIMENTO}}' => formatarData($contrato['CLIENTE_DATA_NASCIMENTO'] ?? ''),
    
    '{{VEICULO_MARCA}}' => $contrato['VEICULO_MARCA'] ?? '',
    '{{VEICULO_MODELO}}' => $contrato['VEICULO_MODELO'] ?? '',
    '{{VEICULO_ANO}}' => $contrato['VEICULO_ANO'] ?? '',
    '{{VEICULO_PLACA}}' => $contrato['VEICULO_PLACA'] ?? '',
    '{{VEICULO_CHASSI}}' => $contrato['VEICULO_CHASSI'] ?? '',
    '{{VEICULO_COR}}' => $contrato['VEICULO_COR'] ?? '',
    '{{VEICULO_KM_ATUAL}}' => number_format($contrato['VEICULO_KM_ATUAL'] ?? 0, 0, ',', '.'),
    '{{VEICULO_COMBUSTIVEL}}' => 'Gasolina',
    
    '{{DATA_INICIO}}' => formatarData($contrato['data_inicio'] ?? ''),
    '{{DATA_FIM}}' => $contrato['data_fim'] ? formatarData($contrato['data_fim']) : 'Indeterminado',
    '{{DIARIAS}}' => '7',
    '{{VALOR_DIARIA}}' => formatarMoeda(($contrato['valor_semanal'] ?? 0) / 7),
    '{{VALOR_TOTAL}}' => formatarMoeda($contrato['valor_semanal'] ?? 0),
    '{{KM_LIVRE_OU_LIMITADO}}' => 'Livre',
    '{{LIMITE_KM}}' => 'Ilimitado',
    '{{VALOR_KM_EXTRA}}' => 'Não aplicável',
    
    '{{FORMA_PAGAMENTO}}' => 'Semanal',
    '{{VALOR_CALCAO}}' => formatarMoeda($contrato['valor_caucao'] ?? 0),
    '{{TIPO_CALCAO}}' => 'Caução',
    '{{DATA_PAGAMENTO}}' => formatarData($contrato['data_proxima_cobranca'] ?? ''),
    '{{MULTA_ATRASO}}' => '2% + 0,33% ao dia',
    
    '{{NIVEL_COMBUSTIVEL}}' => '1/2 tanque',
    '{{AVARIAS_EXISTENTES}}' => 'Nenhuma',
    '{{ACESSORIOS}}' => 'Chave reserva, manual, estepe, macaco',
    '{{STATUS_LIMPEZA}}' => 'Limpo',
    
    '{{DATA_ASSINATURA}}' => date('d/m/Y'),
    '{{ASSINATURA_CLIENTE}}' => '',
    '{{ASSINATURA_LOCADORA}}' => '',
    '{{NUMERO_CONTRATO}}' => $contrato['numero_contrato'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato <?php echo $contrato['numero_contrato']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: #f5f5f5;
            padding: 20px;
        }
        .contrato-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        .header h1 {
            font-size: 18pt;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 10pt;
            color: #333;
        }
        .titulo-clausula {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        .texto-clausula {
            text-align: justify;
            margin-bottom: 15px;
            text-indent: 30px;
        }
        .dados-box {
            border: 1px solid #000;
            padding: 15px;
            margin: 15px 0;
            background: #f9f9f9;
        }
        .dados-box h4 {
            margin-bottom: 10px;
            font-size: 11pt;
            text-transform: uppercase;
        }
        .dados-box p {
            margin-bottom: 5px;
            font-size: 11pt;
        }
        .assinaturas {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        .assinatura-box {
            text-align: center;
        }
        .linha-assinatura {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 10pt;
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
        }
        .print-btn:hover { background: #1d4ed8; }
        .voltar-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 12px 24px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .voltar-btn:hover { background: #4b5563; }
        @media print {
            .print-btn, .voltar-btn { display: none; }
            body { padding: 0; background: #fff; }
            .contrato-container { box-shadow: none; }
        }
        .checklist {
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ccc;
        }
        .checklist-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ccc;
        }
        .checklist-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <a href="<?php echo BASE_URL; ?>/cliente/contratos.php" class="voltar-btn">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimir Contrato
    </button>
    
    <div class="contrato-container">
        <div class="header">
            <h1>CONTRATO DE LOCAÇÃO DE VEÍCULO</h1>
            <p><strong><?php echo $vars['{{LOCADORA_NOME}}']; ?></strong> - CNPJ: <?php echo $vars['{{LOCADORA_CNPJ}}']; ?></p>
            <p><?php echo $vars['{{LOCADORA_ENDERECO}}']; ?> - Tel: <?php echo $vars['{{LOCADORA_TELEFONE}}']; ?></p>
            <p>E-mail: <?php echo $vars['{{LOCADORA_EMAIL}}']; ?></p>
        </div>
        
        <p class="texto-clausula">
            Pelo presente instrumento particular, de um lado <strong><?php echo $vars['{{LOCADORA_NOME}}']; ?></strong>, inscrita no CNPJ sob nº <strong><?php echo $vars['{{LOCADORA_CNPJ}}']; ?></strong>, com sede em <strong><?php echo $vars['{{LOCADORA_ENDERECO}}']; ?></strong>, neste ato representada por <strong><?php echo $vars['{{LOCADORA_RESPONSAVEL}}']; ?></strong>, doravante denominada <strong>LOCADORA</strong>, e de outro lado <strong><?php echo $vars['{{CLIENTE_NOME}}']; ?></strong>, inscrito no CPF/CNPJ sob nº <strong><?php echo $vars['{{CLIENTE_CPF_CNPJ}}']; ?></strong>, portador do RG nº <strong><?php echo $vars['{{CLIENTE_RG}}']; ?></strong>, CNH nº <strong><?php echo $vars['{{CLIENTE_CNH}}']; ?></strong>, residente em <strong><?php echo $vars['{{CLIENTE_ENDERECO}}']; ?></strong>, telefone <strong><?php echo $vars['{{CLIENTE_TELEFONE}}']; ?></strong>, e-mail <strong><?php echo $vars['{{CLIENTE_EMAIL}}']; ?></strong>, doravante denominado <strong>LOCATÁRIO</strong>, têm entre si justo e acertado o presente Contrato de Locação de Veículo, que se regerá pelas cláusulas e condições seguintes:
        </p>
        
        <p class="titulo-clausula">CLÁUSULA PRIMEIRA - DO OBJETO</p>
        <p class="texto-clausula">
            A LOCADORA cede em locação ao LOCATÁRIO, que aceita, o veículo de propriedade da LOCADORA, especificado a seguir:
        </p>
        
        <div class="dados-box">
            <h4>DADOS DO VEÍCULO</h4>
            <p><strong>Marca/Modelo:</strong> <?php echo $vars['{{VEICULO_MARCA}}']; ?> <?php echo $vars['{{VEICULO_MODELO}}']; ?></p>
            <p><strong>Ano:</strong> <?php echo $vars['{{VEICULO_ANO}}']; ?></p>
            <p><strong>Placa:</strong> <?php echo $vars['{{VEICULO_PLACA}}']; ?></p>
            <p><strong>Chassi:</strong> <?php echo $vars['{{VEICULO_CHASSI}}']; ?></p>
            <p><strong>Cor:</strong> <?php echo $vars['{{VEICULO_COR}}']; ?></p>
            <p><strong>Quilometragem Inicial:</strong> <?php echo $vars['{{VEICULO_KM_ATUAL}}']; ?> km</p>
            <p><strong>Combustível:</strong> <?php echo $vars['{{VEICULO_COMBUSTIVEL}}']; ?></p>
        </div>
        
        <p class="titulo-clausula">CLÁUSULA SEGUNDA - DO PRAZO DA LOCAÇÃO</p>
        <p class="texto-clausula">
            O prazo de locação é de <strong><?php echo $vars['{{DIARIAS}}']; ?> dias</strong>, iniciando-se em <strong><?php echo $vars['{{DATA_INICIO}}']; ?></strong> e terminando em <strong><?php echo $vars['{{DATA_FIM}}']; ?></strong>, podendo ser prorrogado mediante acordo entre as partes, com reajuste do valor conforme tabela vigente.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA TERCEIRA - DO VALOR E FORMA DE PAGAMENTO</p>
        <p class="texto-clausula">
            3.1. O valor da locação é de <strong><?php echo $vars['{{VALOR_DIARIA}}']; ?></strong> por dia, totalizando <strong><?php echo $vars['{{VALOR_TOTAL}}']; ?></strong> semanal, a ser pago de forma <strong><?php echo $vars['{{FORMA_PAGAMENTO}}']; ?></strong>.
        </p>
        <p class="texto-clausula">
            3.2. O pagamento deverá ser realizado até a data de vencimento indicada na fatura, sendo o primeiro pagamento em <?php echo $vars['{{DATA_PAGAMENTO}}']; ?>.
        </p>
        <p class="texto-clausula">
            3.3. O atraso no pagamento acarretará multa de <strong><?php echo $vars['{{MULTA_ATRASO}}']; ?></strong> sobre o valor devido, além da suspensão da utilização do veículo.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA QUARTA - DA CAUÇÃO</p>
        <p class="texto-clausula">
            4.1. O LOCATÁRIO deverá pagar à LOCADORA, no ato da assinatura deste contrato, o valor de <strong><?php echo $vars['{{VALOR_CALCAO}}']; ?></strong> a título de <strong><?php echo $vars['{{TIPO_CALCAO}}']; ?></strong>.
        </p>
        <p class="texto-clausula">
            4.2. A caução será restituída ao LOCATÁRIO no prazo de até 30 (trinta) dias após a devolução do veículo, desde que não haja débitos pendentes ou danos ao veículo.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA QUINTA - DO USO DO VEÍCULO</p>
        <p class="texto-clausula">
            5.1. O veículo destina-se exclusivamente ao uso particular do LOCATÁRIO, sendo vedada sua utilização para:
        </p>
        <p class="texto-clausula" style="margin-left: 30px;">
            a) Transporte de passageiros mediante pagamento (táxi, aplicativos);<br>
            b) Participação em competições automobilísticas;<br>
            c) Transporte de mercadorias em volume ou peso superior à capacidade do veículo;<br>
            d) Reboque de outros veículos;<br>
            e) Condução por pessoa não autorizada no contrato.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA SEXTA - DA QUILOMETRAGEM</p>
        <p class="texto-clausula">
            6.1. O veículo será locado com quilometragem <strong><?php echo $vars['{{KM_LIVRE_OU_LIMITADO}}']; ?></strong>.
        </p>
        <p class="texto-clausula">
            6.2. O limite de quilometragem é de <strong><?php echo $vars['{{LIMITE_KM}}']; ?></strong>.
        </p>
        <p class="texto-clausula">
            6.3. O valor do quilômetro excedente é de <strong><?php echo $vars['{{VALOR_KM_EXTRA}}']; ?></strong>.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA SÉTIMA - DO COMBUSTÍVEL</p>
        <p class="texto-clausula">
            7.1. O veículo será entregue ao LOCATÁRIO com o tanque de combustível no nível de <strong><?php echo $vars['{{NIVEL_COMBUSTIVEL}}']; ?></strong>.
        </p>
        <p class="texto-clausula">
            7.2. O LOCATÁRIO se obriga a devolver o veículo com o mesmo nível de combustível da entrega, sob pena de pagamento da diferença acrescida de taxa de serviço.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA OITAVA - DAS AVARIAS E DANOS</p>
        <p class="texto-clausula">
            8.1. O LOCATÁRIO declara ter recebido o veículo nas seguintes condições:
        </p>
        
        <div class="checklist">
            <div class="checklist-item">
                <span>Avarias Existentes:</span>
                <span><strong><?php echo $vars['{{AVARIAS_EXISTENTES}}']; ?></strong></span>
            </div>
            <div class="checklist-item">
                <span>Acessórios:</span>
                <span><strong><?php echo $vars['{{ACESSORIOS}}']; ?></strong></span>
            </div>
            <div class="checklist-item">
                <span>Status de Limpeza:</span>
                <span><strong><?php echo $vars['{{STATUS_LIMPEZA}}']; ?></strong></span>
            </div>
        </div>
        
        <p class="texto-clausula">
            8.2. O LOCATÁRIO é responsável por quaisquer danos causados ao veículo durante o período de locação, incluindo colisões, batidas, arranhões, danos internos e extravio de acessórios.
        </p>
        <p class="texto-clausula">
            8.3. Em caso de acidente, o LOCATÁRIO deverá comunicar imediatamente a LOCADORA e providenciar o Boletim de Ocorrência Policial.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA NONA - DO SEGURO</p>
        <p class="texto-clausula">
            9.1. O veículo está segurado contra danos de terceiros (RCF-V) conforme apólice da seguradora.
        </p>
        <p class="texto-clausula">
            9.2. O LOCATÁRIO poderá contratar seguro adicional para cobertura de danos ao veículo locado (casco), mediante pagamento de prêmio à parte.
        </p>
        <p class="texto-clausula">
            9.3. A franquia do seguro, quando aplicável, será de responsabilidade do LOCATÁRIO.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA DÉCIMA - DA MANUTENÇÃO</p>
        <p class="texto-clausula">
            10.1. A LOCADORA se responsabiliza pela manutenção preventiva do veículo.
        </p>
        <p class="texto-clausula">
            10.2. O LOCATÁRIO deverá comunicar imediatamente qualquer defeito ou anomalia no funcionamento do veículo.
        </p>
        <p class="texto-clausula">
            10.3. O LOCATÁRIO é responsável pela troca de óleo e filtros quando necessário durante o período de locação.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA DÉCIMA PRIMEIRA - DA DEVOLUÇÃO</p>
        <p class="texto-clausula">
            11.1. O LOCATÁRIO se obriga a devolver o veículo na data de término da locação, no mesmo estado em que o recebeu, salvo desgaste natural pelo uso.
        </p>
        <p class="texto-clausula">
            11.2. A não devolução do veículo na data combinada acarretará multa diária equivalente a 3 (três) vezes o valor da diária, sem prejuízo das medidas legais cabíveis.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA DÉCIMA SEGUNDA - DA RESCISÃO</p>
        <p class="texto-clausula">
            12.1. O presente contrato poderá ser rescindido por qualquer das partes, mediante comunicação prévia de 7 (sete) dias.
        </p>
        <p class="texto-clausula">
            12.2. A rescisão por inadimplência do LOCATÁRIO acarretará a retenção da caução e a cobrança dos valores devidos.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA DÉCIMA TERCEIRA - DO FORO</p>
        <p class="texto-clausula">
            13.1. As partes elegem o Foro da Comarca de <?php echo $vars['{{CLIENTE_CIDADE}}'] ?? $contrato['CLIENTE_CIDADE']; ?> para dirimir quaisquer dúvidas ou controvérsias oriundas do presente contrato.
        </p>
        
        <p class="texto-clausula" style="margin-top: 30px;">
            E, por estarem assim justos e contratados, firmam o presente instrumento em 02 (duas) vias de igual teor e forma, na presença das testemunhas abaixo.
        </p>
        
        <p style="text-align: center; margin-top: 30px;">
            <strong><?php echo $contrato['CLIENTE_CIDADE'] ?? ''; ?></strong>, <strong><?php echo $vars['{{DATA_ASSINATURA}}']; ?></strong>.
        </p>
        
        <div class="assinaturas">
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    <strong><?php echo $vars['{{LOCADORA_NOME}}']; ?></strong><br>
                    CNPJ: <?php echo $vars['{{LOCADORA_CNPJ}}']; ?><br>
                    <?php echo $vars['{{LOCADORA_RESPONSAVEL}}']; ?>
                </div>
            </div>
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    <strong><?php echo $vars['{{CLIENTE_NOME}}']; ?></strong><br>
                    CPF: <?php echo $vars['{{CLIENTE_CPF_CNPJ}}']; ?><br>
                    Assinatura do Locatário
                </div>
            </div>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc;">
            <p style="font-size: 10pt; text-align: center; color: #666;">
                Contrato nº <?php echo $vars['{{NUMERO_CONTRATO}}']; ?> - Sistema Master Car<br>
                Documento gerado em <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>
