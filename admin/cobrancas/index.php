<?php
/**
 * Master Car - Listagem de Cobranças/Faturas
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/cobranca.php';

protegerAdmin();

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';

// Query base
$sql = "SELECT f.*, c.nome as cliente_nome, cs.numero_contrato, v.placa
        FROM faturas_semanal f
        JOIN clientes c ON f.cliente_id = c.id
        JOIN contratos_semanal cs ON f.contrato_id = cs.id
        JOIN veiculos v ON cs.veiculo_id = v.id
        WHERE 1=1";
$params = [];

if ($filtroStatus) {
    $sql .= " AND f.status = ?";
    $params[] = $filtroStatus;
}

if ($busca) {
    $sql .= " AND (f.numero_fatura LIKE ? OR c.nome LIKE ? OR v.placa LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY f.data_vencimento DESC, f.created_at DESC";

$faturas = DB()->fetchAll($sql, $params);

// Ações
$acao = $_GET['acao'] ?? '';
$faturaId = $_GET['id'] ?? 0;

if ($acao && $faturaId) {
    switch ($acao) {
        case 'reprocessar':
            $resultado = reprocessarCobranca($faturaId);
            mostrarAlerta($resultado['mensagem'], $resultado['sucesso'] ? 'success' : 'danger');
            redirecionar('/admin/cobrancas/');
            break;
            
        case 'baixa':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $motivo = $_POST['motivo'] ?? '';
                $formaPagamento = $_POST['forma_pagamento'] ?? 'dinheiro';
                $usuario = usuarioAtual();
                
                // Busca dados antes da alteração
                $faturaAntes = DB()->fetch("SELECT * FROM faturas_semanal WHERE id = ?", [$faturaId]);
                
                // Atualiza a fatura
                DB()->update('faturas_semanal', [
                    'status' => 'pago',
                    'data_pagamento' => date('Y-m-d'),
                    'data_baixa' => date('Y-m-d'),
                    'usuario_baixa_id' => $usuario['id'],
                    'motivo_baixa' => $motivo,
                    'tipo_baixa' => 'pagamento',
                    'forma_pagamento' => $formaPagamento,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $faturaId]);
                
                // Registra log
                registrarLog($faturaId, $faturaAntes['contrato_id'], $faturaAntes['cliente_id'], 'baixa', 
                    'Fatura baixada - Forma: ' . $formaPagamento . ($motivo ? ' - Obs: ' . $motivo : ''),
                    ['status_anterior' => $faturaAntes['status'], 'status_novo' => 'pago', 'forma_pagamento' => $formaPagamento, 'motivo' => $motivo]
                );
                
                mostrarAlerta('Fatura baixada com sucesso!', 'success');
                redirecionar('/admin/cobrancas/');
            }
            break;
            
        case 'cancelar':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $motivo = $_POST['motivo'] ?? '';
                $usuario = usuarioAtual();
                
                if (empty($motivo)) {
                    mostrarAlerta('O motivo do cancelamento é obrigatório.', 'danger');
                } else {
                    // Busca dados antes da alteração
                    $faturaAntes = DB()->fetch("SELECT * FROM faturas_semanal WHERE id = ?", [$faturaId]);
                    
                    // Atualiza a fatura
                    DB()->update('faturas_semanal', [
                        'status' => 'cancelado',
                        'data_baixa' => date('Y-m-d'),
                        'usuario_baixa_id' => $usuario['id'],
                        'motivo_baixa' => $motivo,
                        'tipo_baixa' => 'cancelamento',
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', ['id' => $faturaId]);
                    
                    // Registra log
                    registrarLog($faturaId, $faturaAntes['contrato_id'], $faturaAntes['cliente_id'], 'cancelamento', 
                        'Fatura cancelada - Motivo: ' . $motivo,
                        ['status_anterior' => $faturaAntes['status'], 'status_novo' => 'cancelado', 'motivo' => $motivo]
                    );
                    
                    mostrarAlerta('Fatura cancelada com sucesso!', 'success');
                    redirecionar('/admin/cobrancas/');
                }
            }
            break;
            
        case 'excluir':
            $usuario = usuarioAtual();
            if ($usuario['tipo'] == 'master') {
                $resultado = excluirCobranca($faturaId);
                mostrarAlerta($resultado['mensagem'], $resultado['sucesso'] ? 'success' : 'danger');
            } else {
                mostrarAlerta('Apenas usuários master podem excluir cobranças.', 'danger');
            }
            redirecionar('/admin/cobrancas/');
            break;
    }
}

$alerta = obterAlerta();

// Busca fatura para modal de baixa/cancelamento
$faturaModal = null;
if (($acao == 'baixa' || $acao == 'cancelar') && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $faturaModal = DB()->fetch("
        SELECT f.*, c.nome as cliente_nome, v.placa
        FROM faturas_semanal f
        JOIN clientes c ON f.cliente_id = c.id
        JOIN contratos_semanal cs ON f.contrato_id = cs.id
        JOIN veiculos v ON cs.veiculo_id = v.id
        WHERE f.id = ?
    ", [$faturaId]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobranças - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .info-fatura {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-fatura p {
            margin: 5px 0;
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
                    <h1 class="page-title">Cobranças</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Cobranças</span>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/boleto_avulso.php" class="btn btn-info">
                        <i class="fas fa-barcode"></i> Boleto Avulso
                    </a>
                    <a href="<?php echo BASE_URL; ?>/cron/cobranca_semanal.php" target="_blank" class="btn btn-success">
                        <i class="fas fa-play"></i> Executar CRON
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-body">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar por fatura, cliente, placa..." value="<?php echo $busca; ?>">
                        </div>
                        <div style="width: 150px;">
                            <select name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="pendente" <?php echo $filtroStatus == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="pago" <?php echo $filtroStatus == 'pago' ? 'selected' : ''; ?>>Pago</option>
                                <option value="vencido" <?php echo $filtroStatus == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                                <option value="bloqueado" <?php echo $filtroStatus == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                <option value="cancelado" <?php echo $filtroStatus == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <?php if ($busca || $filtroStatus): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/cobrancas/" class="btn btn-light">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Faturas -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th>Cliente</th>
                                    <th>Veículo</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th width="180">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($faturas)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <i class="fas fa-receipt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 15px;"></i>
                                            <p>Nenhuma cobrança encontrada.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($faturas as $fat): 
                                        $diasAtraso = 0;
                                        if ($fat['status'] == 'vencido' || ($fat['status'] == 'pendente' && $fat['data_vencimento'] < date('Y-m-d'))) {
                                            $diasAtraso = diasEntre($fat['data_vencimento'], date('Y-m-d'));
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $fat['numero_fatura']; ?></strong>
                                                <?php if ($fat['descricao']): ?>
                                                    <br><small style="color: var(--gray);"><?php echo $fat['descricao']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $fat['cliente_nome']; ?></td>
                                            <td><?php echo $fat['placa']; ?></td>
                                            <td>
                                                <?php echo formatarMoeda($fat['valor_total']); ?>
                                                <?php if ($fat['valor_multa'] > 0 || $fat['valor_juros'] > 0): ?>
                                                    <br><small style="color: var(--danger);">+ multa/juros</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo formatarData($fat['data_vencimento']); ?>
                                                <?php if ($diasAtraso > 0): ?>
                                                    <br><span class="badge badge-danger"><?php echo $diasAtraso; ?> dias atraso</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = $STATUS_FATURA[$fat['status']]['class'] ?? 'secondary';
                                                $statusLabel = $STATUS_FATURA[$fat['status']]['label'] ?? ucfirst($fat['status']);
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo $statusLabel; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/ver.php?id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-light btn-icon" title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($fat['status'] != 'pago' && $fat['status'] != 'cancelado'): ?>
                                                        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/boleto.php?id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-success btn-icon" title="Gerar Boleto" target="_blank">
                                                            <i class="fas fa-barcode"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($fat['status'] != 'pago' && $fat['status'] != 'cancelado'): ?>
                                                        <a href="?acao=baixa&id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-primary btn-icon" title="Dar Baixa">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($fat['status'] != 'pago' && $fat['status'] != 'cancelado'): ?>
                                                        <a href="?acao=cancelar&id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-danger btn-icon" title="Cancelar">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    $usuario = usuarioAtual();
                                                    if ($usuario['tipo'] == 'master'): 
                                                    ?>
                                                        <a href="?acao=excluir&id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-dark btn-icon" title="Excluir" onclick="return confirm('EXCLUIR PERMANENTEMENTE?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Baixa -->
    <?php if ($acao == 'baixa' && $faturaModal): ?>
    <div class="modal-overlay active" id="modalBaixa">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle" style="color: #10b981;"></i> Dar Baixa na Fatura</h3>
                <button class="modal-close" onclick="window.location.href='<?php echo BASE_URL; ?>/admin/cobrancas/'">&times;</button>
            </div>
            <form method="POST" action="?acao=baixa&id=<?php echo $faturaId; ?>">
                <div class="modal-body">
                    <div class="info-fatura">
                        <p><strong>Fatura:</strong> <?php echo $faturaModal['numero_fatura']; ?></p>
                        <p><strong>Cliente:</strong> <?php echo $faturaModal['cliente_nome']; ?></p>
                        <p><strong>Veículo:</strong> <?php echo $faturaModal['placa']; ?></p>
                        <p><strong>Valor:</strong> <?php echo formatarMoeda($faturaModal['valor_total']); ?></p>
                        <p><strong>Vencimento:</strong> <?php echo formatarData($faturaModal['data_vencimento']); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Forma de Pagamento *</label>
                        <select name="forma_pagamento" class="form-control" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Observação/Motivo</label>
                        <textarea name="motivo" class="form-control" rows="3" placeholder="Observações sobre o pagamento (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Confirmar Baixa
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Modal de Cancelamento -->
    <?php if ($acao == 'cancelar' && $faturaModal): ?>
    <div class="modal-overlay active" id="modalCancelar">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-ban" style="color: #dc2626;"></i> Cancelar Fatura</h3>
                <button class="modal-close" onclick="window.location.href='<?php echo BASE_URL; ?>/admin/cobrancas/'">&times;</button>
            </div>
            <form method="POST" action="?acao=cancelar&id=<?php echo $faturaId; ?>">
                <div class="modal-body">
                    <div class="info-fatura">
                        <p><strong>Fatura:</strong> <?php echo $faturaModal['numero_fatura']; ?></p>
                        <p><strong>Cliente:</strong> <?php echo $faturaModal['cliente_nome']; ?></p>
                        <p><strong>Veículo:</strong> <?php echo $faturaModal['placa']; ?></p>
                        <p><strong>Valor:</strong> <?php echo formatarMoeda($faturaModal['valor_total']); ?></p>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Atenção: Esta ação não pode ser desfeita!</span>
                    </div>
                    
                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Motivo do Cancelamento *</label>
                        <textarea name="motivo" class="form-control" rows="3" required placeholder="Informe o motivo do cancelamento (obrigatório)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo BASE_URL; ?>/admin/cobrancas/" class="btn btn-light">Voltar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
