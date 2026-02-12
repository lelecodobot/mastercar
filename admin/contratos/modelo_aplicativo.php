<?php
/**
 * Master Car - Modelo de Contrato para Aplicativos (Uber, 99, etc.)
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$contratoId = $_GET['id'] ?? 0;

// Busca contrato com todos os dados necessários
$contrato = DB()->fetch("
    SELECT cs.*, 
           c.nome as CLIENTE_NOME, c.cpf_cnpj as CLIENTE_CPF_CNPJ, c.rg_ie as CLIENTE_RG,
           c.cnh_numero as CLIENTE_CNH, c.endereco as CLIENTE_ENDERECO, c.numero as CLIENTE_NUMERO,
           c.bairro as CLIENTE_BAIRRO, c.cidade as CLIENTE_CIDADE, c.estado as CLIENTE_ESTADO,
           c.cep as CLIENTE_CEP, c.telefone as CLIENTE_TELEFONE, c.email as CLIENTE_EMAIL,
           c.data_nascimento as CLIENTE_DATA_NASCIMENTO,
           v.marca as VEICULO_MARCA, v.modelo as VEICULO_MODELO, v.ano_fabricacao as VEICULO_ANO_FAB,
           v.ano_modelo as VEICULO_ANO, v.placa as VEICULO_PLACA, v.renavam as VEICULO_RENAVAM,
           v.chassi as VEICULO_CHASSI, v.cor as VEICULO_COR, v.combustivel as VEICULO_COMBUSTIVEL
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.id = ?
", [$contratoId]);

if (!$contrato) {
    echo '<div style="padding: 20px; text-align: center;"><h2>Contrato não encontrado.</h2></div>';
    exit;
}

// Configurações da locadora
$config = [];
$configs = DB()->fetchAll("SELECT chave, valor FROM configuracoes");
foreach ($configs as $c) {
    $config[$c['chave']] = $c['valor'];
}

// Locador (dados da empresa ou configurável)
$locadorNome = $config['locador_nome'] ?? $config['nome_empresa'] ?? SITE_NAME;
$locadorCpf = $config['locador_cpf'] ?? $config['cnpj_empresa'] ?? '';
$locadorRg = $config['locador_rg'] ?? '';
$locadorCnh = $config['locador_cnh'] ?? '';
$locadorEndereco = $config['locador_endereco'] ?? $config['endereco_empresa'] ?? '';

// Variáveis do contrato
$vars = [
    // Locador
    '{{LOCADOR_NOME}}' => $locadorNome,
    '{{LOCADOR_CPF}}' => formatarCpfCnpj($locadorCpf),
    '{{LOCADOR_RG}}' => $locadorRg,
    '{{LOCADOR_CNH}}' => $locadorCnh,
    '{{LOCADOR_ENDERECO}}' => $locadorEndereco,
    
    // Locatário (Cliente)
    '{{CLIENTE_NOME}}' => $contrato['CLIENTE_NOME'] ?? '',
    '{{CLIENTE_CPF}}' => formatarCpfCnpj($contrato['CLIENTE_CPF_CNPJ'] ?? ''),
    '{{CLIENTE_RG}}' => $contrato['CLIENTE_RG'] ?? '',
    '{{CLIENTE_CNH}}' => $contrato['CLIENTE_CNH'] ?? '',
    '{{CLIENTE_ENDERECO}}' => ($contrato['CLIENTE_ENDERECO'] ?? '') . ', ' . ($contrato['CLIENTE_NUMERO'] ?? '') . ' - ' . ($contrato['CLIENTE_BAIRRO'] ?? '') . ', CEP ' . ($contrato['CLIENTE_CEP'] ?? '') . ' - ' . ($contrato['CLIENTE_CIDADE'] ?? '') . '/' . ($contrato['CLIENTE_ESTADO'] ?? ''),
    '{{CLIENTE_CIDADE}}' => $contrato['CLIENTE_CIDADE'] ?? '',
    '{{CLIENTE_ESTADO}}' => $contrato['CLIENTE_ESTADO'] ?? '',
    
    // Veículo
    '{{VEICULO_MARCA}}' => $contrato['VEICULO_MARCA'] ?? '',
    '{{VEICULO_MODELO}}' => $contrato['VEICULO_MODELO'] ?? '',
    '{{VEICULO_ANO_FAB}}' => $contrato['VEICULO_ANO_FAB'] ?? '',
    '{{VEICULO_ANO}}' => $contrato['VEICULO_ANO'] ?? '',
    '{{VEICULO_COR}}' => $contrato['VEICULO_COR'] ?? '',
    '{{VEICULO_PLACA}}' => $contrato['VEICULO_PLACA'] ?? '',
    '{{VEICULO_RENAVAM}}' => $contrato['VEICULO_RENAVAM'] ?? '',
    '{{VEICULO_CHASSI}}' => $contrato['VEICULO_CHASSI'] ?? '',
    '{{VEICULO_COMBUSTIVEL}}' => $contrato['VEICULO_COMBUSTIVEL'] ?? 'Álcool/Gasolina',
    
    // Locação
    '{{DATA_INICIO}}' => formatarData($contrato['data_inicio'] ?? ''),
    '{{DATA_FIM}}' => $contrato['data_fim'] ? formatarData($contrato['data_fim']) : 'indeterminado',
    '{{VALOR_SEMANAL}}' => formatarMoeda($contrato['valor_semanal'] ?? 0),
    '{{VALOR_CAUCAO}}' => formatarMoeda($contrato['valor_caucao'] ?? 1500),
    '{{VALOR_TOTAL_ENTRADA}}' => formatarMoeda(($contrato['valor_caucao'] ?? 1500) + ($contrato['valor_semanal'] ?? 0)),
    '{{KM_LIMITE}}' => number_format($contrato['km_limite_semanal'] ?? 1250, 0, ',', '.'),
    '{{VALOR_KM_EXTRA}}' => formatarMoeda($contrato['valor_km_extra'] ?? 0.50),
    
    // Outros
    '{{DATA_ASSINATURA}}' => date('d/m/Y'),
    '{{NUMERO_CONTRATO}}' => $contrato['numero_contrato'] ?? ''
];

// Modelo do contrato para aplicativos
$modeloContrato = <<<'CONTRATO'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Locação - {{NUMERO_CONTRATO}}</title>
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
            font-size: 16pt;
            text-transform: uppercase;
            margin-bottom: 10px;
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
            .print-btn { display: none; }
            body { padding: 20px; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>
    
    <div class="contrato-container">
        <div class="header">
            <h1>CONTRATO DE LOCAÇÃO DE VEÍCULO PARA USO EM APLICATIVOS DE TRANSPORTE</h1>
        </div>
        
        <p class="titulo-clausula">IDENTIFICAÇÃO DAS PARTES</p>
        
        <p class="texto-clausula">
            <strong>LOCADOR:</strong> {{LOCADOR_NOME}}, CPF: {{LOCADOR_CPF}}, RG: {{LOCADOR_RG}}, CNH: {{LOCADOR_CNH}}, Endereço: {{LOCADOR_ENDERECO}}.
        </p>
        
        <p class="texto-clausula">
            <strong>LOCATÁRIO:</strong> {{CLIENTE_NOME}}, CPF: {{CLIENTE_CPF}}, RG: {{CLIENTE_RG}}, CNH: {{CLIENTE_CNH}} (Cat. AD), Endereço: {{CLIENTE_ENDERECO}}.
        </p>
        
        <p class="texto-clausula">
            As partes acima identificadas têm, entre si, justo e acertado o presente Contrato de Locação de Veículo, que se regerá pelas cláusulas e condições a seguir.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 1ª – DO OBJETO, PRAZO E USO</p>
        
        <p class="texto-clausula">
            <strong>1.1.</strong> O presente contrato tem por objeto a locação do veículo:
        </p>
        
        <div class="dados-box">
            <h4>DADOS DO VEÍCULO</h4>
            <p><strong>Marca/Modelo:</strong> {{VEICULO_MARCA}}/{{VEICULO_MODELO}}</p>
            <p><strong>Ano Fabricação:</strong> {{VEICULO_ANO_FAB}}; <strong>Ano Modelo:</strong> {{VEICULO_ANO}}</p>
            <p><strong>Cor:</strong> {{VEICULO_COR}}</p>
            <p><strong>Placa:</strong> {{VEICULO_PLACA}}</p>
            <p><strong>Renavam:</strong> {{VEICULO_RENAVAM}}</p>
            <p><strong>Chassi:</strong> {{VEICULO_CHASSI}}</p>
            <p><strong>Categoria:</strong> Particular</p>
            <p><strong>Combustível:</strong> {{VEICULO_COMBUSTIVEL}}</p>
        </div>
        
        <p class="texto-clausula">
            <strong>1.2.</strong> O prazo da locação inicia-se em {{DATA_INICIO}}, podendo ser renovado mediante acordo entre as partes.
        </p>
        
        <p class="texto-clausula">
            <strong>1.3.</strong> O veículo será utilizado exclusivamente para transporte privado por aplicativos (Uber, 99 e similares), sendo vedado o uso por terceiros.
        </p>
        
        <p class="texto-clausula">
            <strong>1.4.</strong> O LOCATÁRIO declara possuir CNH válida há mais de 2 (dois) anos e atender às exigências das plataformas.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 2ª – DO VALOR, PAGAMENTO, QUILOMETRAGEM E CAUÇÃO</p>
        
        <p class="texto-clausula">
            <strong>2.1.</strong> O valor da locação é de {{VALOR_SEMANAL}} por semana, com pagamento semanal via PIX/transferência bancária.
        </p>
        
        <p class="texto-clausula">
            <strong>2.2.</strong> Limite de quilometragem: {{KM_LIMITE}} km por semana. Excedente: {{VALOR_KM_EXTRA}} por km.
        </p>
        
        <p class="texto-clausula">
            <strong>2.3.</strong> Caução: {{VALOR_CAUCAO}}, a ser paga na assinatura do contrato. Além disso, 01 (um) aluguel semanal adiantado no valor de {{VALOR_SEMANAL}}, totalizando {{VALOR_TOTAL_ENTRADA}} na entrada. A caução será devolvida em até 20 (vinte) dias após a devolução do veículo, descontadas multas e avarias, se houver.
        </p>
        
        <p class="texto-clausula">
            <strong>2.4.</strong> Em caso de atraso, incidirá multa de 10% e juros de 1% ao mês.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 3ª – DA MANUTENÇÃO, COMBUSTÍVEL E CONSERVAÇÃO</p>
        
        <p class="texto-clausula">
            <strong>3.1.</strong> As manutenções pesadas (motor, câmbio, embreagem, suspensão, elétrica de maior complexidade e quaisquer reparos estruturais) são de responsabilidade exclusiva do LOCADOR, salvo quando decorrentes de mau uso, negligência ou culpa do LOCATÁRIO.
        </p>
        
        <p class="texto-clausula">
            <strong>3.2.</strong> As manutenções rotineiras, tais como troca de óleo e filtros, serão custeadas em regime de rateio de 50% (cinquenta por cento) pelo LOCADOR e 50% (cinquenta por cento) pelo LOCATÁRIO, mediante apresentação de comprovantes.
        </p>
        
        <p class="texto-clausula">
            <strong>3.3.</strong> O LOCATÁRIO compromete-se a realizar as revisões periódicas conforme manual do fabricante e orientações do LOCADOR, comunicando previamente qualquer necessidade de manutenção.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 4ª – DAS MULTAS, IMPOSTOS E ENCARGOS</p>
        
        <p class="texto-clausula">
            <strong>4.1.</strong> Multas de trânsito e pontuação na CNH são de responsabilidade do LOCATÁRIO.
        </p>
        
        <p class="texto-clausula">
            <strong>4.2.</strong> IPVA, licenciamento e seguro do veículo são de responsabilidade do LOCADOR.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 5ª – DO SEGURO E SINISTROS</p>
        
        <p class="texto-clausula">
            <strong>5.1.</strong> O veículo possui seguro/proteção veicular contratado pelo LOCADOR.
        </p>
        
        <p class="texto-clausula">
            <strong>5.2.</strong> Em caso de sinistro, o LOCATÁRIO deverá comunicar imediatamente o LOCADOR e apresentar boletim de ocorrência em até 48 horas.
        </p>
        
        <p class="texto-clausula">
            <strong>5.3.</strong> Franquia: R$ 6.000,00 (ou 15% da FIPE), quando o sinistro decorrer de culpa do LOCATÁRIO.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 6ª – DA DEVOLUÇÃO DO VEÍCULO</p>
        
        <p class="texto-clausula">
            <strong>6.1.</strong> O veículo deverá ser devolvido nas mesmas condições em que foi entregue, conforme vistoria inicial.
        </p>
        
        <p class="texto-clausula">
            <strong>6.2.</strong> Avarias deverão ser quitadas antes da devolução.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 7ª – DA RESCISÃO E PENALIDADES</p>
        
        <p class="texto-clausula">
            <strong>7.1.</strong> A rescisão antecipada deverá ser comunicada com antecedência mínima de 30 (trinta) dias.
        </p>
        
        <p class="texto-clausula">
            <strong>7.2.</strong> O descumprimento contratual ensejará multa equivalente a 02 semanas de locação.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 8ª – DO RASTREADOR OBRIGATÓRIO</p>
        
        <p class="texto-clausula">
            <strong>8.1.</strong> O veículo deverá permanecer com rastreador ativo durante toda a vigência do contrato.
        </p>
        
        <p class="texto-clausula">
            <strong>8.2.</strong> É vedada a retirada, bloqueio de sinal, desligamento ou qualquer interferência no rastreador, sob pena de rescisão imediata do contrato, perda integral da caução e aplicação de multa equivalente a 02 semanas de locação, sem prejuízo das medidas cíveis e criminais cabíveis.
        </p>
        
        <p class="texto-clausula">
            <strong>8.3.</strong> Em caso de falha técnica no rastreador, o LOCATÁRIO deverá comunicar o LOCADOR imediatamente.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 9ª – DA PERDA DA CAUÇÃO POR ABANDONO OU DESAPARECIMENTO DO VEÍCULO</p>
        
        <p class="texto-clausula">
            <strong>9.1.</strong> Considera-se abandono a interrupção do uso do veículo sem comunicação prévia, a não devolução no prazo ajustado, ou a recusa injustificada em apresentar o veículo para vistoria.
        </p>
        
        <p class="texto-clausula">
            <strong>9.2.</strong> Em caso de abandono, o LOCATÁRIO perderá integralmente a caução, sem prejuízo da cobrança de valores devidos, multas, diárias em atraso, despesas de localização/guincho e reparos necessários.
        </p>
        
        <p class="texto-clausula">
            <strong>9.3.</strong> Persistindo o não comparecimento ou havendo indícios de ocultação do veículo, o LOCADOR poderá adotar medidas judiciais e extrajudiciais para recuperação do bem.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 10ª – DO TERMO DE RESPONSABILIDADE POR MULTAS E PONTOS NA CNH</p>
        
        <p class="texto-clausula">
            <strong>10.1.</strong> O LOCATÁRIO é o único responsável por todas as infrações de trânsito cometidas durante a vigência do contrato, obrigando-se a efetuar o pagamento integral das multas e a assumir a pontuação correspondente em sua CNH.
        </p>
        
        <p class="texto-clausula">
            <strong>10.2.</strong> O LOCATÁRIO compromete-se a fornecer os dados e assinar os formulários necessários para a transferência de pontuação no prazo legal.
        </p>
        
        <p class="texto-clausula">
            <strong>10.3.</strong> Caso o LOCADOR seja compelido a arcar com multas ou encargos por omissão do LOCATÁRIO, este deverá reembolsar integralmente os valores, acrescidos de multa de 10% e juros de 1% ao mês.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA 11ª – DO FORO</p>
        
        <p class="texto-clausula">
            <strong>11.1.</strong> Fica eleito o Foro da Comarca de {{CLIENTE_CIDADE}} – {{CLIENTE_ESTADO}} para dirimir quaisquer controvérsias oriundas deste contrato.
        </p>
        
        <p class="texto-clausula" style="margin-top: 30px;">
            E, por estarem justos e contratados, firmam o presente instrumento em duas vias de igual teor.
        </p>
        
        <p style="text-align: center; margin-top: 30px;">
            <strong>{{CLIENTE_CIDADE}}, {{DATA_ASSINATURA}}</strong>
        </p>
        
        <div class="assinaturas">
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    <strong>{{LOCADOR_NOME}}</strong><br>
                    CPF: {{LOCADOR_CPF}}<br>
                    LOCADOR
                </div>
            </div>
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    <strong>{{CLIENTE_NOME}}</strong><br>
                    CPF: {{CLIENTE_CPF}}<br>
                    LOCATÁRIO
                </div>
            </div>
        </div>
        
        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px;">
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    _________________________________<br>
                    Testemunha 1:<br>
                    CPF:
                </div>
            </div>
            <div class="assinatura-box">
                <div class="linha-assinatura">
                    _________________________________<br>
                    Testemunha 2:<br>
                    CPF:
                </div>
            </div>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc;">
            <p style="font-size: 10pt; text-align: center; color: #666;">
                Contrato nº {{NUMERO_CONTRATO}} - Sistema Master Car<br>
                Documento gerado em {{DATA_ASSINATURA}}
            </p>
        </div>
    </div>
</body>
</html>
CONTRATO;

// Substitui variáveis
$modeloContrato = str_replace(array_keys($vars), array_values($vars), $modeloContrato);

echo $modeloContrato;
