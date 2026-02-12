<?php
/**
 * Master Car - Logs do Sistema
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

// Apenas master pode ver logs
$usuario = usuarioAtual();
if ($usuario['tipo'] != 'master') {
    mostrarAlerta('Apenas usuários master podem acessar os logs.', 'danger');
    redirecionar('/admin/');
}

// Filtros
$filtroAcao = $_GET['acao'] ?? '';
$filtroTabela = $_GET['tabela'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// Buscar logs
$sql = "SELECT l.*, u.nome as usuario_nome
        FROM cobranca_logs l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        WHERE DATE(l.created_at) BETWEEN ? AND ?";
$params = [$dataInicio, $dataFim];

if ($filtroAcao) {
    $sql .= " AND l.tipo = ?";
    $params[] = $filtroAcao;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 500";

$logs = DB()->fetchAll($sql, $params);

// Tipos únicos para filtro
$acoes = DB()->fetchAll("SELECT DISTINCT tipo as acao FROM cobranca_logs ORDER BY tipo");

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .log-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .log-item:hover {
            background: #f8f9fa;
        }
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .log-acao {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .acao-create { background: #dcfce7; color: #166534; }
        .acao-update { background: #dbeafe; color: #1e40af; }
        .acao-delete { background: #fee2e2; color: #991b1b; }
        .acao-baixa { background: #ecfdf5; color: #047857; }
        .acao-cancelamento { background: #fef3c7; color: #92400e; }
        .acao-default { background: #f3f4f6; color: #4b5563; }
        .log-data {
            font-size: 12px;
            color: #666;
        }
        .log-detalhes {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 12px;
        }
        .log-detalhes pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
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
                    <h1 class="page-title">Logs do Sistema</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Logs</span>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div>
                            <label class="form-label">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?php echo $dataInicio; ?>">
                        </div>
                        <div>
                            <label class="form-label">Data Fim</label>
                            <input type="date" name="data_fim" class="form-control" value="<?php echo $dataFim; ?>">
                        </div>
                        <div style="width: 180px;">
                            <label class="form-label">Ação</label>
                            <select name="acao" class="form-control">
                                <option value="">Todas</option>
                                <?php foreach ($acoes as $a): ?>
                                    <option value="<?php echo $a['acao']; ?>" <?php echo $filtroAcao == $a['acao'] ? 'selected' : ''; ?>><?php echo $a['acao']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="width: 180px;">
                            <label class="form-label">Tabela</label>
                            <select name="tabela" class="form-control">
                                <option value="">Todas</option>
                                <?php foreach ($tabelas as $t): ?>
                                    <option value="<?php echo $t['tabela']; ?>" <?php echo $filtroTabela == $t['tabela'] ? 'selected' : ''; ?>><?php echo $t['tabela']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Logs -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Registros de Atividade</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($logs)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-clipboard-list" style="font-size: 48px; color: var(--gray-light); margin-bottom: 15px;"></i>
                            <p>Nenhum log encontrado.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $acaoClass = 'acao-default';
                            if ($log['tipo'] == 'create' || $log['tipo'] == 'insert') $acaoClass = 'acao-create';
                            elseif ($log['tipo'] == 'update' || $log['tipo'] == 'baixa') $acaoClass = 'acao-update';
                            elseif ($log['tipo'] == 'delete') $acaoClass = 'acao-delete';
                            elseif ($log['tipo'] == 'cancelamento') $acaoClass = 'acao-cancelamento';
                            elseif ($log['tipo'] == 'baixa') $acaoClass = 'acao-baixa';
                        ?>
                            <div class="log-item">
                                <div class="log-header">
                                    <div>
                                        <span class="log-acao <?php echo $acaoClass; ?>"><?php echo strtoupper($log['tipo']); ?></span>
                                        <?php if ($log['fatura_id']): ?>
                                            <span style="color: #666; font-size: 12px; margin-left: 10px;">
                                                <i class="fas fa-file-invoice"></i> Fatura #<?php echo $log['fatura_id']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($log['contrato_id']): ?>
                                            <span style="color: #666; font-size: 12px; margin-left: 10px;">
                                                <i class="fas fa-file-contract"></i> Contrato #<?php echo $log['contrato_id']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="log-data">
                                        <i class="fas fa-calendar"></i> <?php echo formatarData($log['created_at']); ?>
                                        <i class="fas fa-clock" style="margin-left: 10px;"></i> <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <strong>Usuário:</strong> 
                                    <?php echo $log['usuario_nome'] ?? 'Sistema'; ?> 
                                    (<?php echo $log['usuario_tipo']; ?>)
                                    <span style="color: #666; margin-left: 15px;">
                                        <i class="fas fa-network-wired"></i> <?php echo $log['ip_address']; ?>
                                    </span>
                                </div>
                                <?php if ($log['descricao']): ?>
                                    <div style="margin-top: 8px; color: #444;">
                                        <strong>Descrição:</strong> <?php echo $log['descricao']; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($log['dados_json']): ?>
                                    <div class="log-detalhes">
                                        <strong style="color: #666;">Dados:</strong>
                                        <pre><?php echo $log['dados_json']; ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
