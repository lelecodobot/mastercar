<?php
/**
 * Master Car - Sistema de Gestão de Locadora
 * Arquivo de Configuração
 */

// Prevenir acesso direto
if (!defined('MASTER_CAR')) {
    define('MASTER_CAR', true);
}

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Davi@2015');
define('DB_NAME', 'mastercar');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURAÇÕES DO SISTEMA
// =====================================================
// Detecta automaticamente a URL base (funciona local e remoto)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$dirName = dirname($scriptName);
$basePath = ($dirName == '/' || $dirName == '\\') ? '' : $dirName;
$basePath = str_replace('\\', '/', $basePath);

// Remove subdiretórios de admin/cliente para pegar a raiz
if (strpos($basePath, '/admin') !== false) {
    $basePath = substr($basePath, 0, strpos($basePath, '/admin'));
}
if (strpos($basePath, '/cliente') !== false) {
    $basePath = substr($basePath, 0, strpos($basePath, '/cliente'));
}
if (strpos($basePath, '/install') !== false) {
    $basePath = substr($basePath, 0, strpos($basePath, '/install'));
}
if (strpos($basePath, '/cron') !== false) {
    $basePath = substr($basePath, 0, strpos($basePath, '/cron'));
}

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('SITE_NAME', 'Master Car');
define('SITE_VERSION', '1.0.0');

// Caminhos
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('CRON_PATH', ROOT_PATH . '/cron');

// URLs
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// =====================================================
// CONFIGURAÇÕES DE SESSÃO
// =====================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 0 para localhost

// =====================================================
// CONFIGURAÇÕES DE TIMEZONE
// =====================================================
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

// =====================================================
// CONFIGURAÇÕES DE EXIBIÇÃO DE ERROS
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log');

// =====================================================
// CONFIGURAÇÕES DE PAGAMENTO
// =====================================================
// Asaas
define('ASAAS_SANDBOX_URL', 'https://sandbox.asaas.com/api/v3');
define('ASAAS_PROD_URL', 'https://api.asaas.com/v3');

// Mercado Pago
define('MP_SANDBOX_URL', 'https://api.mercadopago.com');
define('MP_PROD_URL', 'https://api.mercadopago.com');

// =====================================================
// CONFIGURAÇÕES DE COBRANÇA
// =====================================================
define('DIAS_TOLERANCIA_PADRAO', 3);
define('MULTA_ATRASO_PADRAO', 2.00); // Percentual
define('JUROS_DIA_ATRASO_PADRAO', 0.33); // Percentual
define('DIAS_BLOQUEIO_PADRAO', 7); // Dias após vencimento para bloqueio

// =====================================================
// STATUS DO SISTEMA
// =====================================================
$STATUS_FATURA = [
    'pendente' => ['label' => 'Pendente', 'class' => 'warning'],
    'pago' => ['label' => 'Pago', 'class' => 'success'],
    'vencido' => ['label' => 'Vencido', 'class' => 'danger'],
    'bloqueado' => ['label' => 'Bloqueado', 'class' => 'dark'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'secondary']
];

$STATUS_CONTRATO = [
    'ativo' => ['label' => 'Ativo', 'class' => 'success'],
    'suspenso' => ['label' => 'Suspenso', 'class' => 'warning'],
    'encerrado' => ['label' => 'Encerrado', 'class' => 'info'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'danger']
];

$STATUS_CLIENTE = [
    'ativo' => ['label' => 'Ativo', 'class' => 'success'],
    'bloqueado' => ['label' => 'Bloqueado', 'class' => 'danger'],
    'inadimplente' => ['label' => 'Inadimplente', 'class' => 'warning'],
    'cancelado' => ['label' => 'Cancelado', 'class' => 'secondary']
];

$STATUS_VEICULO = [
    'disponivel' => ['label' => 'Disponível', 'class' => 'success'],
    'alugado' => ['label' => 'Alugado', 'class' => 'warning'],
    'manutencao' => ['label' => 'Manutenção', 'class' => 'info'],
    'bloqueado' => ['label' => 'Bloqueado', 'class' => 'danger'],
    'inativo' => ['label' => 'Inativo', 'class' => 'secondary']
];

$CATEGORIAS_VEICULO = [
    'economico' => 'Econômico',
    'intermediario' => 'Intermediário',
    'suv' => 'SUV',
    'luxo' => 'Luxo',
    'caminhonete' => 'Caminhonete',
    'van' => 'Van'
];

// =====================================================
// FUNÇÕES UTILITÁRIAS
// =====================================================

/**
 * Formata valor monetário
 */
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata data para exibição
 */
function formatarData($data) {
    if (empty($data)) return '-';
    return date('d/m/Y', strtotime($data));
}

/**
 * Formata data e hora
 */
function formatarDataHora($data) {
    if (empty($data)) return '-';
    return date('d/m/Y H:i', strtotime($data));
}

/**
 * Formata CPF/CNPJ
 */
function formatarCpfCnpj($valor) {
    $valor = preg_replace('/[^0-9]/', '', $valor);
    if (strlen($valor) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
    }
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
}

/**
 * Formata telefone
 */
function formatarTelefone($valor) {
    $valor = preg_replace('/[^0-9]/', '', $valor);
    if (strlen($valor) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $valor);
    }
    return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $valor);
}

/**
 * Gera string aleatória
 */
function gerarStringAleatoria($tamanho = 10) {
    return bin2hex(random_bytes($tamanho / 2));
}

/**
 * Limpa CPF/CNPJ (remove caracteres não numéricos)
 */
function limparCpfCnpj($valor) {
    return preg_replace('/[^0-9]/', '', $valor);
}

/**
 * Sanitiza input para prevenir XSS
 */
function sanitizar($valor) {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

/**
 * Calcula dias entre duas datas
 */
function diasEntre($dataInicio, $dataFim) {
    $inicio = new DateTime($dataInicio);
    $fim = new DateTime($dataFim);
    return $inicio->diff($fim)->days;
}

/**
 * Adiciona dias úteis a uma data
 */
function adicionarDiasUteis($data, $dias) {
    $data = new DateTime($data);
    $adicionados = 0;
    
    while ($adicionados < $dias) {
        $data->modify('+1 day');
        // 0 = Domingo, 6 = Sábado
        if ($data->format('w') != 0 && $data->format('w') != 6) {
            $adicionados++;
        }
    }
    
    return $data->format('Y-m-d');
}

/**
 * Gera número de contrato único
 */
function gerarNumeroContrato() {
    return 'CTR-' . date('Y') . strtoupper(gerarStringAleatoria(6));
}

/**
 * Gera número de fatura único
 */
function gerarNumeroFatura() {
    return 'FAT-' . date('Ymd') . '-' . strtoupper(gerarStringAleatoria(4));
}

/**
 * Retorna o próximo dia da semana
 */
function proximoDiaSemana($diaSemana, $dataBase = null) {
    $data = $dataBase ? new DateTime($dataBase) : new DateTime();
    $dataAtual = (int)$data->format('w');
    
    $diasParaAdicionar = ($diaSemana - $dataAtual + 7) % 7;
    if ($diasParaAdicionar == 0) {
        $diasParaAdicionar = 7;
    }
    
    $data->modify("+{$diasParaAdicionar} days");
    return $data->format('Y-m-d');
}

/**
 * Redireciona para URL
 */
function redirecionar($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

/**
 * Mostra mensagem de alerta
 */
function mostrarAlerta($mensagem, $tipo = 'info') {
    $_SESSION['alerta'] = [
        'mensagem' => $mensagem,
        'tipo' => $tipo
    ];
}

/**
 * Obtém e limpa mensagem de alerta
 */
function obterAlerta() {
    if (isset($_SESSION['alerta'])) {
        $alerta = $_SESSION['alerta'];
        unset($_SESSION['alerta']);
        return $alerta;
    }
    return null;
}

/**
 * Define mensagem de alerta (alias para mostrarAlerta)
 */
function definirAlerta($tipo, $mensagem) {
    $_SESSION['alerta'] = [
        'mensagem' => $mensagem,
        'tipo' => $tipo
    ];
}
?>
