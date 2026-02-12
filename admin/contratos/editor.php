<?php
/**
 * Master Car - Editor de Modelo de Contrato
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Busca ou cria o modelo de contrato
$modelo = DB()->fetch("SELECT * FROM configuracoes WHERE chave = 'modelo_contrato'");

// Modelo padrão
$modeloPadrao = <<<'HTML'
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
        .assinaturas {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        .linha-assinatura {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="contrato-container">
        <div class="header">
            <h1>CONTRATO DE LOCAÇÃO DE VEÍCULO</h1>
            <p><strong>{{LOCADORA_NOME}}</strong> - CNPJ: {{LOCADORA_CNPJ}}</p>
            <p>{{LOCADORA_ENDERECO}} - Tel: {{LOCADORA_TELEFONE}}</p>
        </div>
        
        <p class="texto-clausula">
            Pelo presente instrumento particular, de um lado <strong>{{LOCADORA_NOME}}</strong>, inscrita no CNPJ sob nº <strong>{{LOCADORA_CNPJ}}</strong>, com sede em <strong>{{LOCADORA_ENDERECO}}</strong>, neste ato representada por <strong>{{LOCADORA_RESPONSAVEL}}</strong>, doravante denominada <strong>LOCADORA</strong>, e de outro lado <strong>{{CLIENTE_NOME}}</strong>, inscrito no CPF/CNPJ sob nº <strong>{{CLIENTE_CPF_CNPJ}}</strong>, portador do RG nº <strong>{{CLIENTE_RG}}</strong>, CNH nº <strong>{{CLIENTE_CNH}}</strong>, residente em <strong>{{CLIENTE_ENDERECO}}</strong>, telefone <strong>{{CLIENTE_TELEFONE}}</strong>, e-mail <strong>{{CLIENTE_EMAIL}}</strong>, doravante denominado <strong>LOCATÁRIO</strong>, têm entre si justo e acertado o presente Contrato de Locação de Veículo.
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
        </div>
        
        <p class="titulo-clausula">CLÁUSULA SEGUNDA - DO PRAZO DA LOCAÇÃO</p>
        <p class="texto-clausula">
            O prazo de locação é de <strong>{{DIARIAS}} dias</strong>, iniciando-se em <strong>{{DATA_INICIO}}</strong> e terminando em <strong>{{DATA_FIM}}</strong>, podendo ser prorrogado mediante acordo entre as partes.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA TERCEIRA - DO VALOR E FORMA DE PAGAMENTO</p>
        <p class="texto-clausula">
            3.1. O valor da locação é de <strong>{{VALOR_DIARIA}}</strong> por dia, totalizando <strong>{{VALOR_TOTAL}}</strong> semanal, a ser pago de forma <strong>{{FORMA_PAGAMENTO}}</strong>.
        </p>
        <p class="texto-clausula">
            3.2. O pagamento deverá ser realizado até a data de vencimento indicada na fatura.
        </p>
        <p class="texto-clausula">
            3.3. O atraso no pagamento acarretará multa de <strong>{{MULTA_ATRASO}}</strong> sobre o valor devido.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA QUARTA - DA CAUÇÃO</p>
        <p class="texto-clausula">
            4.1. O LOCATÁRIO deverá pagar à LOCADORA, no ato da assinatura deste contrato, o valor de <strong>{{VALOR_CALCAO}}</strong> a título de <strong>{{TIPO_CALCAO}}</strong>.
        </p>
        <p class="texto-clausula">
            4.2. A caução será restituída ao LOCATÁRIO no prazo de até 30 (trinta) dias após a devolução do veículo.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA QUINTA - DO USO DO VEÍCULO</p>
        <p class="texto-clausula">
            5.1. O veículo destina-se exclusivamente ao uso particular do LOCATÁRIO, sendo vedada sua utilização para transporte de passageiros mediante pagamento, competições automobilísticas, transporte de mercadorias em excesso, reboque de veículos ou condução por pessoa não autorizada.
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
            7.2. O LOCATÁRIO se obriga a devolver o veículo com o mesmo nível de combustível da entrega.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA OITAVA - DAS AVARIAS E DANOS</p>
        <p class="texto-clausula">
            8.1. O LOCATÁRIO declara ter recebido o veículo nas seguintes condições: Avarias Existentes: <strong>{{AVARIAS_EXISTENTES}}</strong>, Acessórios: <strong>{{ACESSORIOS}}</strong>, Status de Limpeza: <strong>{{STATUS_LIMPEZA}}</strong>.
        </p>
        <p class="texto-clausula">
            8.2. O LOCATÁRIO é responsável por quaisquer danos causados ao veículo durante o período de locação.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA NONA - DO SEGURO</p>
        <p class="texto-clausula">
            9.1. O veículo está segurado contra danos de terceiros (RCF-V) conforme apólice da seguradora.
        </p>
        <p class="texto-clausula">
            9.2. A franquia do seguro, quando aplicável, será de responsabilidade do LOCATÁRIO.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA DÉCIMA - DA MANUTENÇÃO</p>
        <p class="texto-clausula">
            10.1. A LOCADORA se responsabiliza pela manutenção preventiva do veículo.
        </p>
        <p class="texto-clausula">
            10.2. O LOCATÁRIO deverá comunicar imediatamente qualquer defeito ou anomalia no funcionamento do veículo.
        </p>
        
        <p class="titulo-clausula">CLÁUSULA DÉCIMA PRIMEIRA - DA DEVOLUÇÃO</p>
        <p class="texto-clausula">
            11.1. O LOCATÁRIO se obriga a devolver o veículo na data de término da locação, no mesmo estado em que o recebeu.
        </p>
        <p class="texto-clausula">
            11.2. A não devolução do veículo na data combinada acarretará multa diária equivalente a 3 (três) vezes o valor da diária.
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
        
        <p class="texto-clausula" style="margin-top: 30px; text-align: center;">
            E, por estarem assim justos e contratados, firmam o presente instrumento em 02 (duas) vias de igual teor e forma.
        </p>
        
        <p style="text-align: center; margin-top: 30px;">
            <strong>{{CLIENTE_CIDADE}}</strong>, <strong>{{DATA_ASSINATURA}}</strong>.
        </p>
        
        <div class="assinaturas">
            <div class="linha-assinatura">
                <strong>{{LOCADORA_NOME}}</strong><br>
                CNPJ: {{LOCADORA_CNPJ}}<br>
                {{LOCADORA_RESPONSAVEL}}
            </div>
            <div class="linha-assinatura">
                <strong>{{CLIENTE_NOME}}</strong><br>
                CPF: {{CLIENTE_CPF_CNPJ}}<br>
                Assinatura do Locatário
            </div>
        </div>
        
        <div style="margin-top: 40px; text-align: center; font-size: 10pt; color: #666;">
            Contrato nº {{NUMERO_CONTRATO}} - Sistema Master Car
        </div>
    </div>
</body>
</html>
HTML;

// Salvar modelo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modelo'])) {
    $novoModelo = $_POST['modelo'];
    
    // Verifica se já existe
    $existe = DB()->fetch("SELECT id FROM configuracoes WHERE chave = 'modelo_contrato'");
    if ($existe) {
        DB()->query("UPDATE configuracoes SET valor = ? WHERE chave = 'modelo_contrato'", [$novoModelo]);
    } else {
        DB()->query("INSERT INTO configuracoes (chave, valor, descricao) VALUES ('modelo_contrato', ?, 'Modelo de Contrato')", [$novoModelo]);
    }
    
    mostrarAlerta('Modelo de contrato salvo com sucesso!', 'success');
    redirecionar('/admin/contratos/editor.php');
}

$modeloAtual = $modelo['valor'] ?? $modeloPadrao;

// Lista de variáveis disponíveis
$variaveis = [
    'Locadora' => ['{{LOCADORA_NOME}}', '{{LOCADORA_CNPJ}}', '{{LOCADORA_ENDERECO}}', '{{LOCADORA_TELEFONE}}', '{{LOCADORA_EMAIL}}', '{{LOCADORA_RESPONSAVEL}}'],
    'Cliente' => ['{{CLIENTE_NOME}}', '{{CLIENTE_CPF_CNPJ}}', '{{CLIENTE_RG}}', '{{CLIENTE_CNH}}', '{{CLIENTE_ENDERECO}}', '{{CLIENTE_TELEFONE}}', '{{CLIENTE_EMAIL}}', '{{CLIENTE_DATA_NASCIMENTO}}', '{{CLIENTE_CIDADE}}'],
    'Veículo' => ['{{VEICULO_MARCA}}', '{{VEICULO_MODELO}}', '{{VEICULO_ANO}}', '{{VEICULO_PLACA}}', '{{VEICULO_CHASSI}}', '{{VEICULO_COR}}', '{{VEICULO_KM_ATUAL}}', '{{VEICULO_COMBUSTIVEL}}'],
    'Locação' => ['{{DATA_INICIO}}', '{{DATA_FIM}}', '{{DIARIAS}}', '{{VALOR_DIARIA}}', '{{VALOR_TOTAL}}', '{{KM_LIVRE_OU_LIMITADO}}', '{{LIMITE_KM}}', '{{VALOR_KM_EXTRA}}'],
    'Pagamento' => ['{{FORMA_PAGAMENTO}}', '{{VALOR_CALCAO}}', '{{TIPO_CALCAO}}', '{{DATA_PAGAMENTO}}', '{{MULTA_ATRASO}}'],
    'Condições' => ['{{NIVEL_COMBUSTIVEL}}', '{{AVARIAS_EXISTENTES}}', '{{ACESSORIOS}}', '{{STATUS_LIMPEZA}}'],
    'Outros' => ['{{DATA_ASSINATURA}}', '{{NUMERO_CONTRATO}}']
];

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Contrato - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .editor-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        .variaveis-panel {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            height: fit-content;
        }
        .variaveis-panel h3 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #333;
        }
        .variavel-grupo {
            margin-bottom: 15px;
        }
        .variavel-grupo h4 {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .variavel-item {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-family: monospace;
            margin: 2px;
            cursor: pointer;
        }
        .variavel-item:hover {
            background: #3730a3;
            color: #fff;
        }
        .editor-area {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
        }
        .editor-textarea {
            width: 100%;
            min-height: 600px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        .botoes-acao {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }
        .preview-btn {
            background: #6b7280;
            color: white;
        }
        .preview-btn:hover {
            background: #4b5563;
        }
        .dica-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .dica-box h4 {
            color: #92400e;
            margin-bottom: 10px;
        }
        .dica-box ul {
            margin: 0;
            padding-left: 20px;
        }
        .dica-box li {
            margin-bottom: 5px;
            color: #78350f;
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
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Editor de Modelo de Contrato</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/contratos/">Contratos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Editor</span>
                    </div>
                </div>
            </div>
            
            <div class="dica-box">
                <h4><i class="fas fa-lightbulb"></i> Como usar o Editor</h4>
                <ul>
                    <li>Use as <strong>variáveis</strong> no lado esquerdo - elas serão substituídas automaticamente pelos dados reais</li>
                    <li>Clique em uma variável para copiá-la para a área de transferência</li>
                    <li>Você pode editar todo o HTML do contrato: texto, estilos, cláusulas</li>
                    <li>Use o botão <strong>Visualizar</strong> para ver como ficará o contrato</li>
                    <li>O contrato usa HTML + CSS - você pode personalizar completamente o layout</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="editor-container">
                    <!-- Painel de Variáveis -->
                    <div class="variaveis-panel">
                        <h3><i class="fas fa-code"></i> Variáveis Disponíveis</h3>
                        <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Clique para copiar</p>
                        
                        <?php foreach ($variaveis as $grupo => $vars): ?>
                            <div class="variavel-grupo">
                                <h4><?php echo $grupo; ?></h4>
                                <?php foreach ($vars as $var): ?>
                                    <span class="variavel-item" onclick="copiarVariavel('<?php echo $var; ?>')" title="Clique para copiar">
                                        <?php echo $var; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Área do Editor -->
                    <div class="editor-area">
                        <label class="form-label" style="margin-bottom: 10px; display: block;">
                            <i class="fas fa-edit"></i> HTML do Contrato
                        </label>
                        <textarea name="modelo" class="editor-textarea" id="editorModelo" placeholder="Cole ou digite o modelo do contrato em HTML..."><?php echo htmlspecialchars($modeloAtual); ?></textarea>
                        
                        <div class="botoes-acao">
                            <a href="<?php echo BASE_URL; ?>/admin/contratos/modelo_preview.php" target="_blank" class="btn preview-btn" onclick="return previewContrato()">
                                <i class="fas fa-eye"></i> Visualizar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Modelo
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function copiarVariavel(variavel) {
            navigator.clipboard.writeText(variavel).then(function() {
                // Feedback visual
                var items = document.querySelectorAll('.variavel-item');
                items.forEach(function(item) {
                    if (item.textContent.trim() === variavel) {
                        var originalBg = item.style.background;
                        item.style.background = '#10b981';
                        item.style.color = '#fff';
                        setTimeout(function() {
                            item.style.background = '';
                            item.style.color = '';
                        }, 500);
                    }
                });
            });
        }
        
        function previewContrato() {
            var modelo = document.getElementById('editorModelo').value;
            // Salva em localStorage para preview
            localStorage.setItem('modelo_contrato_preview', modelo);
            return true;
        }
    </script>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
