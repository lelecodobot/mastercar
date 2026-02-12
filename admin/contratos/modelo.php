<?php
/**
 * Master Car - Modelo de Contrato de Locação
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$id = $_GET['id'] ?? 0;

// Busca o tipo de contrato
$tipoContrato = DB()->fetch("SELECT tipo_contrato FROM contratos_semanal WHERE id = ?", [$id]);

// Redireciona para o modelo correto baseado no tipo
if ($tipoContrato && $tipoContrato['tipo_contrato'] == 'aplicativo') {
    include 'modelo_aplicativo.php';
    exit;
}

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
    WHERE cs.id = ?
", [$id]);

// Configurações da locadora
$config = [];
$configs = DB()->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($configs as $c) {
    $config[$c['chave']] = $c['valor'];
}

// Variáveis do contrato
$vars = [
    // Locadora
    '{{LOCADORA_NOME}}' => $config['nome_empresa'] ?? SITE_NAME,
    '{{LOCADORA_CNPJ}}' => $config['cnpj_empresa'] ?? '00.000.000/0000-00',
    '{{LOCADORA_ENDERECO}}' => $config['endereco_empresa'] ?? '',
    '{{LOCADORA_TELEFONE}}' => $config['telefone_empresa'] ?? '',
    '{{LOCADORA_EMAIL}}' => $config['email_empresa'] ?? '',
    '{{LOCADORA_RESPONSAVEL}}' => $config['responsavel_empresa'] ?? 'Administrador',
    
    // Cliente
    '{{CLIENTE_NOME}}' => $contrato['CLIENTE_NOME'] ?? '',
    '{{CLIENTE_CPF_CNPJ}}' => formatarCpfCnpj($contrato['CLIENTE_CPF_CNPJ'] ?? ''),
    '{{CLIENTE_RG}}' => $contrato['CLIENTE_RG'] ?? '',
    '{{CLIENTE_CNH}}' => $contrato['CLIENTE_CNH'] ?? '',
    '{{CLIENTE_ENDERECO}}' => ($contrato['CLIENTE_ENDERECO'] ?? '') . ', ' . ($contrato['CLIENTE_NUMERO'] ?? '') . ' - ' . ($contrato['CLIENTE_BAIRRO'] ?? '') . ', ' . ($contrato['CLIENTE_CIDADE'] ?? '') . '/' . ($contrato['CLIENTE_ESTADO'] ?? '') . ' - CEP: ' . ($contrato['CLIENTE_CEP'] ?? ''),
    '{{CLIENTE_CIDADE}}' => $contrato['CLIENTE_CIDADE'] ?? '',
    '{{CLIENTE_ESTADO}}' => $contrato['CLIENTE_ESTADO'] ?? '',
    '{{CLIENTE_TELEFONE}}' => formatarTelefone($contrato['CLIENTE_TELEFONE'] ?? ''),
    '{{CLIENTE_EMAIL}}' => $contrato['CLIENTE_EMAIL'] ?? '',
    '{{CLIENTE_DATA_NASCIMENTO}}' => formatarData($contrato['CLIENTE_DATA_NASCIMENTO'] ?? ''),
    
    // Veículo
    '{{VEICULO_MARCA}}' => $contrato['VEICULO_MARCA'] ?? '',
    '{{VEICULO_MODELO}}' => $contrato['VEICULO_MODELO'] ?? '',
    '{{VEICULO_ANO}}' => $contrato['VEICULO_ANO'] ?? '',
    '{{VEICULO_PLACA}}' => $contrato['VEICULO_PLACA'] ?? '',
    '{{VEICULO_CHASSI}}' => $contrato['VEICULO_CHASSI'] ?? '',
    '{{VEICULO_COR}}' => $contrato['VEICULO_COR'] ?? '',
    '{{VEICULO_KM_ATUAL}}' => number_format($contrato['VEICULO_KM_ATUAL'] ?? 0, 0, ',', '.'),
    '{{VEICULO_COMBUSTIVEL}}' => 'Gasolina',
    
    // Locação
    '{{DATA_INICIO}}' => formatarData($contrato['data_inicio'] ?? ''),
    '{{DATA_FIM}}' => $contrato['data_fim'] ? formatarData($contrato['data_fim']) : 'Indeterminado',
    '{{DIARIAS}}' => '7',
    '{{VALOR_DIARIA}}' => formatarMoeda(($contrato['valor_semanal'] ?? 0) / 7),
    '{{VALOR_TOTAL}}' => formatarMoeda($contrato['valor_semanal'] ?? 0),
    '{{KM_LIVRE_OU_LIMITADO}}' => 'Livre',
    '{{LIMITE_KM}}' => 'Ilimitado',
    '{{VALOR_KM_EXTRA}}' => 'Não aplicável',
    
    // Pagamento
    '{{FORMA_PAGAMENTO}}' => 'Semanal',
    '{{VALOR_CALCAO}}' => formatarMoeda($contrato['valor_caucao'] ?? 0),
    '{{TIPO_CALCAO}}' => 'Caução',
    '{{DATA_PAGAMENTO}}' => formatarData($contrato['data_proxima_cobranca'] ?? ''),
    '{{MULTA_ATRASO}}' => '2% + 0,33% ao dia',
    
    // Condições
    '{{NIVEL_COMBUSTIVEL}}' => '1/2 tanque',
    '{{AVARIAS_EXISTENTES}}' => 'Nenhuma',
    '{{ACESSORIOS}}' => 'Chave reserva, manual, estepe, macaco',
    '{{STATUS_LIMPEZA}}' => 'Limpo',
    
    // Assinaturas
    '{{DATA_ASSINATURA}}' => date('d/m/Y'),
    '{{ASSINATURA_CLIENTE}}' => '',
    '{{ASSINATURA_LOCADORA}}' => '',
    
    // Outros
    '{{NUMERO_CONTRATO}}' => $contrato['numero_contrato'] ?? ''
];

// Modelo do contrato
$modeloContrato = <<<'CONTRATO'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Locação - {{LOCADORA_NOME}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: #fff;
            padding: 40px;
        }
        .contrato-container {
            max-width: 210mm;
            margin: 0 auto;
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
        @media print {
            .print-btn, .variaveis-panel { display: none !important; }
            body { padding: 20px; }
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
        .variaveis-panel {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 320px;
            max-height: 80vh;
            overflow-y: auto;
            background: #fff;
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: Arial, sans-serif;
            font-size: 11px;
            z-index: 1000;
        }
        .variaveis-panel h3 {
            color: #2563eb;
            font-size: 13px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ddd;
        }
        .variaveis-panel h4 {
            color: #333;
            font-size: 11px;
            margin: 10px 0 5px 0;
        }
        .variaveis-panel code {
            display: inline-block;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            color: #2563eb;
            margin: 2px;
        }
        .variaveis-panel p {
            color: #666;
            font-size: 10px;
            margin-bottom: 8px;
        }
        .variaveis-panel .toggle-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc2626;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
        }
        .variaveis-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            z-index: 999;
        }
    </style>
</head>
<body>
    <!-- Botão para mostrar variáveis -->
    <button class="variaveis-toggle" onclick="document.getElementById('variaveisPanel').style.display='block'; this.style.display='none';">
        <i class="fas fa-code"></i> Ver Variáveis
    </button>
    
    <!-- Painel de Variáveis -->
    <div id="variaveisPanel" class="variaveis-panel" style="display: none;">
        <button class="toggle-btn" onclick="document.getElementById('variaveisPanel').style.display='none'; document.querySelector('.variaveis-toggle').style.display='block';">✕ Fechar</button>
        <h3><i class="fas fa-code"></i> Variáveis do Contrato</h3>
        <p>Use estas variáveis no modelo de contrato. Elas serão substituídas automaticamente pelos dados reais.</p>
        
        <h4>🏢 Locadora</h4>
        <code>{{LOCADORA_NOME}}</code>
        <code>{{LOCADORA_CNPJ}}</code>
        <code>{{LOCADORA_ENDERECO}}</code>
        <code>{{LOCADORA_TELEFONE}}</code>
        <code>{{LOCADORA_EMAIL}}</code>
        <code>{{LOCADORA_RESPONSAVEL}}</code>
        
        <h4>👤 Cliente</h4>
        <code>{{CLIENTE_NOME}}</code>
        <code>{{CLIENTE_CPF_CNPJ}}</code>
        <code>{{CLIENTE_RG}}</code>
        <code>{{CLIENTE_CNH}}</code>
        <code>{{CLIENTE_ENDERECO}}</code>
        <code>{{CLIENTE_TELEFONE}}</code>
        <code>{{CLIENTE_EMAIL}}</code>
        <code>{{CLIENTE_DATA_NASCIMENTO}}</code>
        
        <h4>🚗 Veículo</h4>
        <code>{{VEICULO_MARCA}}</code>
        <code>{{VEICULO_MODELO}}</code>
        <code>{{VEICULO_ANO}}</code>
        <code>{{VEICULO_PLACA}}</code>
        <code>{{VEICULO_CHASSI}}</code>
        <code>{{VEICULO_COR}}</code>
        <code>{{VEICULO_KM_ATUAL}}</code>
        <code>{{VEICULO_COMBUSTIVEL}}</code>
        
        <h4>📅 Locação</h4>
        <code>{{DATA_INICIO}}</code>
        <code>{{DATA_FIM}}</code>
        <code>{{DIARIAS}}</code>
        <code>{{VALOR_DIARIA}}</code>
        <code>{{VALOR_TOTAL}}</code>
        <code>{{KM_LIVRE_OU_LIMITADO}}</code>
        <code>{{LIMITE_KM}}</code>
        <code>{{VALOR_KM_EXTRA}}</code>
        
        <h4>💰 Pagamento</h4>
        <code>{{FORMA_PAGAMENTO}}</code>
        <code>{{VALOR_CALCAO}}</code>
        <code>{{TIPO_CALCAO}}</code>
        <code>{{DATA_PAGAMENTO}}</code>
        <code>{{MULTA_ATRASO}}</code>
        
        <h4>✅ Condições</h4>
        <code>{{NIVEL_COMBUSTIVEL}}</code>
        <code>{{AVARIAS_EXISTENTES}}</code>
        <code>{{ACESSORIOS}}</code>
        <code>{{STATUS_LIMPEZA}}</code>
        
        <h4>✍️ Assinaturas</h4>
        <code>{{DATA_ASSINATURA}}</code>
        <code>{{ASSINATURA_CLIENTE}}</code>
        <code>{{ASSINATURA_LOCADORA}}</code>
        
        <h4>📄 Outros</h4>
        <code>{{NUMERO_CONTRATO}}</code>
        
        <p style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <strong>Dica:</strong> Para editar o modelo do contrato, acesse o arquivo <code>/admin/contratos/modelo.php</code>
        </p>
    </div>
    
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>
    
    <div class="contrato-container">
        <div class="header">
            <h1>CONTRATO DE LOCAÇÃO DE VEÍCULO</h1>
            <p><strong>{{LOCADORA_NOME}}</strong> - CNPJ: {{LOCADORA_CNPJ}}</p>
            <p>{{LOCADORA_ENDERECO}} - Tel: {{LOCADORA_TELEFONE}}</p>
            <p>E-mail: {{LOCADORA_EMAIL}}</p>
        </div>
        
        <p class="texto-clausula">
            Pelo presente instrumento particular, de um lado <strong>{{LOCADORA_NOME}}</strong>, inscrita no CNPJ sob nº <strong>{{LOCADORA_CNPJ}}</strong>, com sede em <strong>{{LOCADORA_ENDERECO}}</strong>, neste ato representada por <strong>{{LOCADORA_RESPONSAVEL}}</strong>, doravante denominada <strong>LOCADORA</strong>, e de outro lado <strong>{{CLIENTE_NOME}}</strong>, inscrito no CPF/CNPJ sob nº <strong>{{CLIENTE_CPF_CNPJ}}</strong>, portador do RG nº <strong>{{CLIENTE_RG}}</strong>, CNH nº <strong>{{CLIENTE_CNH}}</strong>, residente em <strong>{{CLIENTE_ENDERECO}}</strong>, telefone <strong>{{CLIENTE_TELEFONE}}</strong>, e-mail <strong>{{CLIENTE_EMAIL}}</strong>, doravante denominado <strong>LOCATÁRIO</strong>, têm entre si justo e acertado o presente Contrato de Locação de Veículo, que se regerá pelas cláusulas e condições seguintes:
        </p>
        
        <p class="titulo-clausula">CLÁUSULA PRIMEIRA - DO OBJETO</p>
        <p class="texto-clausula">
            A LOCADORA cede em locação ao LOCATÁRIO, que aceita, o veículo de propriedade da LOCADORA, especificado a seguir:
        </p>
        
        <div class="dados-box">
            <h4>DADOS DO VEÍCULO</h4>
            <p><strong>Marca/Modelo:</strong> {{VEICULO_MARCA}} {{VEICULO_MODELO}}</p>
            <p><strong>Ano:</strong> {{VEICULO_ANO}}</p>
            <p><strong>Placa:</strong> {{VEICULO_PLACA}}</p>
            <p><strong>Chassi:</strong> {{VEICULO_CHASSI}}</p>
            <p><strong>Cor:</strong> {{VEICULO_COR}}</p>
            <p><strong>Quilometragem Inicial:</strong> {{VEICULO_KM_ATUAL}} km</p>
            <p><strong>Combustível:</strong> {{VEICULO_COMBUSTIVEL}}</p>
        </div>
        
        <p class="titulo-clausula">CLÁUSULA SEGUNDA - DO PRAZO DA LOCAÇÃO</p>
        <p class="texto-clausula">
            O prazo de locação é de <strong>{{DIARIAS}} dias</strong>, iniciando-se em <strong>{{DATA_INICIO}}</strong> e terminando em <strong>{{DATA_FIM}}</strong>, podendo ser prorrogado mediante acordo entre as partes, com reajuste do valor conforme tabela vigente.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA TERCEIRA - DO VALOR E FORMA DE PAGAMENTO</p>
        <p class="texto-clausula">
            3.1. O valor da locação é de <strong>{{VALOR_DIARIA}}</strong> por dia, totalizando <strong>{{VALOR_TOTAL}}</strong> semanal, a ser pago de forma <strong>{{FORMA_PAGAMENTO}}</strong>.
        </p>
        <p class="texto-clausula">
            3.2. O pagamento deverá ser realizado até a data de vencimento indicada na fatura, sendo o primeiro pagamento em {{DATA_PAGAMENTO}}.
        </p>
        <p class="texto-clausula">
            3.3. O atraso no pagamento acarretará multa de <strong>{{MULTA_ATRASO}}</strong> sobre o valor devido, além da suspensão da utilização do veículo.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA QUARTA - DA CAUÇÃO</p>
        <p class="texto-clausula">
            4.1. O LOCATÁRIO deverá pagar à LOCADORA, no ato da assinatura deste contrato, o valor de <strong>{{VALOR_CALCAO}}</strong> a título de <strong>{{TIPO_CALCAO}}</strong>.
        </p>
        <p class="texto-clausula">
            4.2. A caução será restituída ao LOCATÁRIO no prazo de até 30 (trinta) dias após a devolução do veículo, desde que não haja débitos pendentes ou danos ao veículo.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA QUINTA - DO USO DO VEÍCULO</p>
        <p class="texto-clausula">
            5.1. O veículo destina-se exclusivamente ao uso particular do LOCATÁRIO, sendo vedada sua utilização para:
        </p>
        <p class="texto-clausula" style="margin-left: 30px;">
            a) Transporte de passageiros mediante pagamento (táxi, aplicativos);
            b) Participação em competições automobilísticas;
            c) Transporte de mercadorias em volume ou peso superior à capacidade do veículo;
            d) Reboque de outros veículos;
            e) Condução por pessoa não autorizada no contrato.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA SEXTA - DA QUILOMETRAGEM</p>
        <p class="texto-clausula">
            6.1. O veículo será locado com quilometragem <strong>{{KM_LIVRE_OU_LIMITADO}}</strong>.
        </p>
        <p class="texto-clausula">
            6.2. O limite de quilometragem é de <strong>{{LIMITE_KM}}</strong>.
        </p>
        <p class="texto-clausula">
            6.3. O valor do quilômetro excedente é de <strong>{{VALOR_KM_EXTRA}}</strong>.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA SÉTIMA - DO COMBUSTÍVEL</p>
        <p class="texto-clausula">
            7.1. O veículo será entregue ao LOCATÁRIO com o tanque de combustível no nível de <strong>{{NIVEL_COMBUSTIVEL}}</strong>.
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
                <span><strong>{{AVARIAS_EXISTENTES}}</strong></span>
            </div>
            <div class="checklist-item">
                <span>Acessórios:</span>
                <span><strong>{{ACESSORIOS}}</strong></span>
            </div>
            <div class="checklist-item">
                <span>Status de Limpeza:</span>
                <span><strong>{{STATUS_LIMPEZA}}</strong></span>
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
            13.1. As partes elegem o Foro da Comarca de {{CLIENTE_CIDADE}} para dirimir quaisquer dúvidas ou controvérsias oriundas do presente contrato.
        </p>
        
        <p class="texto-clausula" style="margin-top: 30px;">
            E, por estarem assim justos e contratados, firmam o presente instrumento em 02 (duas) vias de igual teor e forma, na presença das testemunhas abaixo.
        </p>
        
        <p style="text-align: center; margin-top: 30px;">
            <strong>{{CLIENTE_CIDADE}}</strong>, <strong>{{DATA_ASSINATURA}}</strong>.
        </p>
        
        <div class="assinaturas">
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    <strong>{{LOCADORA_NOME}}</strong><br>
                    CNPJ: {{LOCADORA_CNPJ}}<br>
                    {{LOCADORA_RESPONSAVEL}}
                </div>
            </div>
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    <strong>{{CLIENTE_NOME}}</strong><br>
                    CPF: {{CLIENTE_CPF_CNPJ}}<br>
                    Assinatura do Locatário
                </div>
            </div>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc;">
            <p style="font-size: 10pt; text-align: center; color: #666;">
                Contrato nº {{NUMERO_CONTRATO}} - Sistema Master Car<br>
                Documento gerado em <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>
CONTRATO;

// Substitui variáveis
$modeloContrato = str_replace(array_keys($vars), array_values($vars), $modeloContrato);

// Exibe o contrato
echo $modeloContrato;
