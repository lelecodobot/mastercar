<?php
/**
 * Master Car - CRON de Cobrança Semanal
 * 
 * Este script deve ser executado diariamente para:
 * 1. Gerar cobranças semanais
 * 2. Processar faturas vencidas
 * 3. Aplicar multas e juros
 * 4. Bloquear clientes inadimplentes
 * 
 * Execução via CRON (diariamente às 06:00):
 * 0 6 * * * /usr/bin/php /caminho/para/cron/cobranca_semanal.php
 */

// Configurações
define('MASTER_CAR', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Caminhos
$cronPath = dirname(__FILE__);
$rootPath = dirname($cronPath);

// Includes
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once $rootPath . '/includes/auth.php';
require_once $rootPath . '/includes/cobranca.php';

// Log de início
logCron("========================================");
logCron("INÍCIO - Cobrança Semanal");
logCron("Data/Hora: " . date('d/m/Y H:i:s'));
logCron("========================================");

$resultados = [];

// =====================================================
// 1. GERAR COBRANÇAS SEMANAIS
// =====================================================
logCron("\n[1/4] Gerando cobranças semanais...");

try {
    $resultado = gerarCobrancasSemanais(date('Y-m-d'));
    
    if ($resultado['sucesso']) {
        $msg = "Cobranças geradas: " . $resultado['cobrancas_geradas'];
        logCron("✓ " . $msg);
        $resultados['cobrancas_geradas'] = $resultado['cobrancas_geradas'];
        
        if (!empty($resultado['erros'])) {
            logCron("⚠ Erros encontrados:");
            foreach ($resultado['erros'] as $erro) {
                logCron("  - " . $erro);
            }
        }
    } else {
        logCron("✗ Erro: " . $resultado['mensagem']);
    }
    
} catch (Exception $e) {
    logCron("✗ Erro crítico: " . $e->getMessage());
    error_log("[CRON] Erro ao gerar cobranças: " . $e->getMessage());
}

// =====================================================
// 2. PROCESSAR FATURAS VENCIDAS
// =====================================================
logCron("\n[2/4] Processando faturas vencidas...");

try {
    $resultado = processarFaturasVencidas();
    
    if ($resultado['sucesso']) {
        $msg = "Faturas processadas: " . $resultado['processadas'];
        logCron("✓ " . $msg);
        $resultados['faturas_vencidas'] = $resultado['processadas'];
    } else {
        logCron("✗ Erro: " . $resultado['mensagem']);
    }
    
} catch (Exception $e) {
    logCron("✗ Erro crítico: " . $e->getMessage());
    error_log("[CRON] Erro ao processar faturas vencidas: " . $e->getMessage());
}

// =====================================================
// 3. VERIFICAR BLOQUEIOS
// =====================================================
logCron("\n[3/4] Verificando bloqueios...");

try {
    $bloqueios = verificarBloqueios();
    logCron("✓ Clientes bloqueados: " . $bloqueios['bloqueados']);
    logCron("✓ Veículos bloqueados: " . $bloqueios['veiculos_bloqueados']);
    $resultados['clientes_bloqueados'] = $bloqueios['bloqueados'];
    $resultados['veiculos_bloqueados'] = $bloqueios['veiculos_bloqueados'];
    
} catch (Exception $e) {
    logCron("✗ Erro crítico: " . $e->getMessage());
    error_log("[CRON] Erro ao verificar bloqueios: " . $e->getMessage());
}

// =====================================================
// 4. ENVIAR NOTIFICAÇÕES
// =====================================================
logCron("\n[4/4] Enviando notificações...");

try {
    $notificacoes = enviarNotificacoesCobranca();
    logCron("✓ Notificações enviadas: " . $notificacoes['enviadas']);
    $resultados['notificacoes'] = $notificacoes['enviadas'];
    
} catch (Exception $e) {
    logCron("✗ Erro crítico: " . $e->getMessage());
    error_log("[CRON] Erro ao enviar notificações: " . $e->getMessage());
}

// =====================================================
// RESUMO
// =====================================================
logCron("\n========================================");
logCron("RESUMO:");
logCron("- Cobranças geradas: " . ($resultados['cobrancas_geradas'] ?? 0));
logCron("- Faturas vencidas: " . ($resultados['faturas_vencidas'] ?? 0));
logCron("- Clientes bloqueados: " . ($resultados['clientes_bloqueados'] ?? 0));
logCron("- Veículos bloqueados: " . ($resultados['veiculos_bloqueados'] ?? 0));
logCron("- Notificações enviadas: " . ($resultados['notificacoes'] ?? 0));
logCron("========================================");
logCron("FIM - " . date('d/m/Y H:i:s'));
logCron("\n");

// Retorna resultado em JSON se chamado via HTTP
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => true,
        'data_execucao' => date('Y-m-d H:i:s'),
        'resultados' => $resultados
    ]);
}

/**
 * Função de log
 */
function logCron($mensagem) {
    $logFile = ROOT_PATH . '/logs/cron.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $linha = "[" . date('Y-m-d H:i:s') . "] " . $mensagem . PHP_EOL;
    file_put_contents($logFile, $linha, FILE_APPEND);
    
    // Também exibe no console se for CLI
    if (php_sapi_name() === 'cli') {
        echo $mensagem . PHP_EOL;
    }
}

/**
 * Verifica e executa bloqueios
 */
function verificarBloqueios() {
    $hoje = date('Y-m-d');
    $bloqueados = 0;
    $veiculosBloqueados = 0;
    
    // Busca faturas que atingiram data de bloqueio
    $faturas = DB()->fetchAll("
        SELECT f.*, c.nome as cliente_nome
        FROM faturas_semanal f
        JOIN clientes c ON f.cliente_id = c.id
        WHERE f.status IN ('pendente', 'vencido')
        AND f.data_bloqueio <= ?
        AND c.status != 'bloqueado'
    ", [$hoje]);
    
    foreach ($faturas as $fatura) {
        if (bloquearClientePorFatura($fatura)) {
            $bloqueados++;
            
            // Conta veículos bloqueados
            $contrato = DB()->fetch("SELECT veiculo_id FROM contratos_semanal WHERE id = ?", [$fatura['contrato_id']]);
            if ($contrato) {
                $veiculosBloqueados++;
            }
        }
    }
    
    return [
        'bloqueados' => $bloqueados,
        'veiculos_bloqueados' => $veiculosBloqueados
    ];
}

/**
 * Envia notificações de cobrança
 */
function enviarNotificacoesCobranca() {
    $enviadas = 0;
    
    // Busca faturas pendentes que vencem em 3 dias
    $dataLimite = date('Y-m-d', strtotime('+3 days'));
    
    $faturas = DB()->fetchAll("
        SELECT f.*, c.nome, c.email
        FROM faturas_semanal f
        JOIN clientes c ON f.cliente_id = c.id
        WHERE f.status = 'pendente'
        AND f.data_vencimento <= ?
        AND f.data_vencimento >= CURDATE()
    ", [$dataLimite]);
    
    foreach ($faturas as $fatura) {
        // Cria notificação no sistema
        criarNotificacao(
            $fatura['cliente_id'],
            null,
            'Cobrança Próxima do Vencimento',
            "Sua fatura #{$fatura['numero_fatura']} vence em " . formatarData($fatura['data_vencimento']) . ". Valor: " . formatarMoeda($fatura['valor_total']),
            'cobranca',
            '/cliente/faturas.php?id=' . $fatura['id']
        );
        
        $enviadas++;
        
        // Aqui você pode adicionar envio de e-mail/WhatsApp
        // enviarEmailCobranca($fatura);
        // enviarWhatsAppCobranca($fatura);
    }
    
    return ['enviadas' => $enviadas];
}
