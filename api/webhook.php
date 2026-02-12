<?php
/**
 * Master Car - Webhook para Notificações de Pagamento
 * 
 * Endpoint para receber notificações dos gateways de pagamento
 * URL: http://localhost/mastercar/api/webhook.php
 */

// Configurações
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

define('MASTER_CAR', true);

// Includes
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/gateway.php';

// Log da requisição
$input = file_get_contents('php://input');
$headers = getallheaders();

logWebhook("========================================");
logWebhook("Nova requisição webhook");
logWebhook("Data/Hora: " . date('d/m/Y H:i:s'));
logWebhook("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));
logWebhook("Headers: " . json_encode($headers));
logWebhook("Body: " . $input);

// Verifica se há dados
if (empty($input)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum dado recebido.']);
    exit;
}

// Decodifica JSON
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    logWebhook("Erro ao decodificar JSON: " . json_last_error_msg());
    echo json_encode(['sucesso' => false, 'mensagem' => 'JSON inválido.']);
    exit;
}

try {
    // Processa webhook
    $gateway = new GatewayPagamento();
    $resultado = $gateway->processarWebhook($data);
    
    if ($resultado['sucesso']) {
        logWebhook("✓ Webhook processado com sucesso");
        http_response_code(200);
    } else {
        logWebhook("✗ Erro ao processar webhook: " . $resultado['mensagem']);
        http_response_code(400);
    }
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    logWebhook("✗ Erro crítico: " . $e->getMessage());
    error_log("[WEBHOOK] Erro: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno do servidor.']);
}

logWebhook("========================================\n");

/**
 * Função de log
 */
function logWebhook($mensagem) {
    $logFile = ROOT_PATH . '/logs/webhook.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $linha = "[" . date('Y-m-d H:i:s') . "] " . $mensagem . PHP_EOL;
    file_put_contents($logFile, $linha, FILE_APPEND);
}
