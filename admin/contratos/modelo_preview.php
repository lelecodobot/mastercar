<?php
/**
 * Master Car - Preview do Modelo de Contrato
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Busca contrato de exemplo
$contratoExemplo = DB()->fetch("
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
    ORDER BY cs.id DESC
    LIMIT 1
");

// Se não tiver contrato, usa dados fictícios
if (!$contratoExemplo) {
    $contratoExemplo = [
        'numero_contrato' => 'CTR-2024000001',
        'CLIENTE_NOME' => 'João da Silva',
        'CLIENTE_CPF_CNPJ' => '12345678901',
        'CLIENTE_RG' => '123456789',
        'CLIENTE_CNH' => '12345678900',
        'CLIENTE_ENDERECO' => 'Rua das Flores',
        'CLIENTE_NUMERO' => '123',
        'CLIENTE_BAIRRO' => 'Centro',
        'CLIENTE_CIDADE' => 'São Paulo',
        'CLIENTE_ESTADO' => 'SP',
        'CLIENTE_CEP' => '01000-000',
        'CLIENTE_TELEFONE' => '(11) 98765-4321',
        'CLIENTE_EMAIL' => 'joao@email.com',
        'CLIENTE_DATA_NASCIMENTO' => '1990-01-01',
        'VEICULO_MARCA' => 'Volkswagen',
        'VEICULO_MODELO' => 'Gol',
        'VEICULO_ANO' => '2022',
        'VEICULO_PLACA' => 'ABC1D23',
        'VEICULO_CHASSI' => '9BWZZZ377VT004251',
        'VEICULO_COR' => 'Prata',
        'VEICULO_KM_ATUAL' => 15000,
        'data_inicio' => date('Y-m-d'),
        'data_fim' => null,
        'valor_semanal' => 350.00,
        'valor_caucao' => 1000.00,
        'data_proxima_cobranca' => date('Y-m-d', strtotime('+7 days'))
    ];
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
    '{{LOCADORA_CNPJ}}' => formatarCpfCnpj($config['cnpj_empresa'] ?? '00.000.000/0000-00'),
    '{{LOCADORA_ENDERECO}}' => $config['endereco_empresa'] ?? '',
    '{{LOCADORA_TELEFONE}}' => formatarTelefone($config['telefone_empresa'] ?? ''),
    '{{LOCADORA_EMAIL}}' => $config['email_empresa'] ?? '',
    '{{LOCADORA_RESPONSAVEL}}' => $config['responsavel_empresa'] ?? 'Administrador',
    
    '{{CLIENTE_NOME}}' => $contratoExemplo['CLIENTE_NOME'] ?? '',
    '{{CLIENTE_CPF_CNPJ}}' => formatarCpfCnpj($contratoExemplo['CLIENTE_CPF_CNPJ'] ?? ''),
    '{{CLIENTE_RG}}' => $contratoExemplo['CLIENTE_RG'] ?? '',
    '{{CLIENTE_CNH}}' => $contratoExemplo['CLIENTE_CNH'] ?? '',
    '{{CLIENTE_ENDERECO}}' => ($contratoExemplo['CLIENTE_ENDERECO'] ?? '') . ', ' . ($contratoExemplo['CLIENTE_NUMERO'] ?? '') . ' - ' . ($contratoExemplo['CLIENTE_BAIRRO'] ?? '') . ', ' . ($contratoExemplo['CLIENTE_CIDADE'] ?? '') . '/' . ($contratoExemplo['CLIENTE_ESTADO'] ?? '') . ' - CEP: ' . ($contratoExemplo['CLIENTE_CEP'] ?? ''),
    '{{CLIENTE_CIDADE}}' => $contratoExemplo['CLIENTE_CIDADE'] ?? '',
    '{{CLIENTE_TELEFONE}}' => formatarTelefone($contratoExemplo['CLIENTE_TELEFONE'] ?? ''),
    '{{CLIENTE_EMAIL}}' => $contratoExemplo['CLIENTE_EMAIL'] ?? '',
    '{{CLIENTE_DATA_NASCIMENTO}}' => formatarData($contratoExemplo['CLIENTE_DATA_NASCIMENTO'] ?? ''),
    
    '{{VEICULO_MARCA}}' => $contratoExemplo['VEICULO_MARCA'] ?? '',
    '{{VEICULO_MODELO}}' => $contratoExemplo['VEICULO_MODELO'] ?? '',
    '{{VEICULO_ANO}}' => $contratoExemplo['VEICULO_ANO'] ?? '',
    '{{VEICULO_PLACA}}' => $contratoExemplo['VEICULO_PLACA'] ?? '',
    '{{VEICULO_CHASSI}}' => $contratoExemplo['VEICULO_CHASSI'] ?? '',
    '{{VEICULO_COR}}' => $contratoExemplo['VEICULO_COR'] ?? '',
    '{{VEICULO_KM_ATUAL}}' => number_format($contratoExemplo['VEICULO_KM_ATUAL'] ?? 0, 0, ',', '.'),
    '{{VEICULO_COMBUSTIVEL}}' => 'Gasolina',
    
    '{{DATA_INICIO}}' => formatarData($contratoExemplo['data_inicio'] ?? ''),
    '{{DATA_FIM}}' => $contratoExemplo['data_fim'] ? formatarData($contratoExemplo['data_fim']) : 'Indeterminado',
    '{{DIARIAS}}' => '7',
    '{{VALOR_DIARIA}}' => formatarMoeda(($contratoExemplo['valor_semanal'] ?? 0) / 7),
    '{{VALOR_TOTAL}}' => formatarMoeda($contratoExemplo['valor_semanal'] ?? 0),
    '{{KM_LIVRE_OU_LIMITADO}}' => 'Livre',
    '{{LIMITE_KM}}' => 'Ilimitado',
    '{{VALOR_KM_EXTRA}}' => 'Não aplicável',
    
    '{{FORMA_PAGAMENTO}}' => 'Semanal',
    '{{VALOR_CALCAO}}' => formatarMoeda($contratoExemplo['valor_caucao'] ?? 0),
    '{{TIPO_CALCAO}}' => 'Caução',
    '{{DATA_PAGAMENTO}}' => formatarData($contratoExemplo['data_proxima_cobranca'] ?? ''),
    '{{MULTA_ATRASO}}' => '2% + 0,33% ao dia',
    
    '{{NIVEL_COMBUSTIVEL}}' => '1/2 tanque',
    '{{AVARIAS_EXISTENTES}}' => 'Nenhuma',
    '{{ACESSORIOS}}' => 'Chave reserva, manual, estepe, macaco',
    '{{STATUS_LIMPEZA}}' => 'Limpo',
    
    '{{DATA_ASSINATURA}}' => date('d/m/Y'),
    '{{NUMERO_CONTRATO}}' => $contratoExemplo['numero_contrato'] ?? 'CTR-0000000000'
];

// Busca modelo salvo ou usa preview do localStorage
$modelo = DB()->fetch("SELECT valor FROM configuracoes WHERE chave = 'modelo_contrato'");
$modeloContrato = $modelo['valor'] ?? '';

// Substitui variáveis
$contratoFinal = str_replace(array_keys($vars), array_values($vars), $modeloContrato);

// Se não tiver modelo, mostra mensagem
if (empty($modeloContrato)) {
    echo '<div style="padding: 50px; text-align: center; font-family: Arial;">
        <h2>Nenhum modelo de contrato configurado</h2>
        <p>Vá em <a href="' . BASE_URL . '/admin/contratos/editor.php">Editor de Contrato</a> para criar um modelo.</p>
    </div>';
    exit;
}

echo $contratoFinal;
