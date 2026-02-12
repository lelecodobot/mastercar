<?php
/**
 * Master Car - Dashboard do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();

// Contratos do cliente
$contratos = DB()->fetchAll("
    SELECT cs.*, v.placa, v.marca, v.modelo, v.cor
    FROM contratos_semanal cs
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.cliente_id = ?
    ORDER BY cs.created_at DESC
", [$cliente['id']]);

// Faturas pendentes
$faturasPendentes = DB()->fetchAll("
    SELECT f.*
    FROM faturas_semanal f
    WHERE f.cliente_id = ?
    AND f.status IN ('pendente', 'vencido')
    ORDER BY f.data_vencimento ASC
", [$cliente['id']]);

// Total devido
$totalDevido = array_sum(array_column($faturasPendentes, 'valor_total'));

// Notificações
$notificacoes = DB()->fetchAll("
    SELECT * FROM notificacoes 
    WHERE cliente_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$cliente['id']]);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cliente-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
        }
        .cliente-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .cliente-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .cliente-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .cliente-nav a {
            padding: 12px 20px;
            background: white;
            border-radius: var(--border-radius);
            color: var(--dark);
            font-weight: 500;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }
        .cliente-nav a:hover {
            background: var(--primary);
            color: white;
        }
        .cliente-nav a i {
            margin-right: 8px;
        }
    </style>
</head>
<body style="background: #f1f5f9;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        
        <!-- Header -->
        <div class="cliente-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-user-circle"></i> Olá, <?php echo explode(' ', $cliente['nome'])[0]; ?>!</h2>
                    <p>Bem-vindo à sua área exclusiva</p>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/cliente/logout.php" style="color: white; opacity: 0.9;">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Navegação -->
        <div class="cliente-nav">
            <a href="<?php echo BASE_URL; ?>/cliente/"><i class="fas fa-home"></i> Início</a>
            <a href="<?php echo BASE_URL; ?>/cliente/faturas.php"><i class="fas fa-receipt"></i> Minhas Faturas</a>
            <a href="<?php echo BASE_URL; ?>/cliente/contratos.php"><i class="fas fa-file-contract"></i> Meus Contratos</a>
            <a href="<?php echo BASE_URL; ?>/cliente/documentos.php"><i class="fas fa-folder-open"></i> Documentos</a>
            <a href="<?php echo BASE_URL; ?>/cliente/perfil.php"><i class="fas fa-user-cog"></i> Meu Perfil</a>
        </div>
        
        <?php if ($alerta): ?>
            <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                <span><?php echo $alerta['mensagem']; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Status da Conta -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($contratos); ?></div>
                    <div class="stat-label">Contratos</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($faturasPendentes); ?></div>
                    <div class="stat-label">Faturas Pendentes</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo formatarMoeda($totalDevido); ?></div>
                    <div class="stat-label">Total Devido</div>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <!-- Faturas Pendentes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-receipt"></i> Faturas Pendentes</h3>
                    <a href="<?php echo BASE_URL; ?>/cliente/faturas.php" class="btn btn-sm btn-light">Ver Todas</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($faturasPendentes)): ?>
                        <div style="padding: 30px; text-align: center; color: var(--gray);">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px; color: var(--success);"></i>
                            <p>Você não tem faturas pendentes!</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faturasPendentes as $fat): 
                                    $diasAtraso = 0;
                                    if ($fat['data_vencimento'] < date('Y-m-d')) {
                                        $diasAtraso = diasEntre($fat['data_vencimento'], date('Y-m-d'));
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $fat['numero_fatura']; ?></strong>
                                            <br><small>Semana <?php echo $fat['semana_referencia']; ?></small>
                                        </td>
                                        <td><?php echo formatarMoeda($fat['valor_total']); ?></td>
                                        <td>
                                            <?php echo formatarData($fat['data_vencimento']); ?>
                                            <?php if ($diasAtraso > 0): ?>
                                                <br><span class="badge badge-danger"><?php echo $diasAtraso; ?> dias atraso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/cliente/fatura.php?id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Notificações -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bell"></i> Notificações</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($notificacoes)): ?>
                        <div style="padding: 30px; text-align: center; color: var(--gray);">
                            <i class="fas fa-bell-slash" style="font-size: 36px; margin-bottom: 10px;"></i>
                            <p style="font-size: 13px;">Nenhuma notificação</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($notificacoes as $notif): ?>
                                <div style="padding: 15px; border-bottom: 1px solid var(--gray-light); <?php echo $notif['lida'] ? '' : 'background: #eff6ff;'; ?>">
                                    <div style="font-size: 13px; font-weight: 600;"><?php echo $notif['titulo']; ?></div>
                                    <div style="font-size: 12px; color: var(--secondary); margin-top: 3px;">
                                        <?php echo substr($notif['mensagem'], 0, 60) . '...'; ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--gray); margin-top: 5px;">
                                        <?php echo formatarDataHora($notif['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Contratos -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-contract"></i> Meus Contratos</h3>
                <a href="<?php echo BASE_URL; ?>/cliente/contratos.php" class="btn btn-sm btn-light">Ver Todos</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($contratos)): ?>
                    <div style="padding: 30px; text-align: center; color: var(--gray);">
                        <i class="fas fa-file-contract" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>Você não tem contratos ativos.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Veículo</th>
                                <th>Valor Semanal</th>
                                <th>Próx. Cobrança</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contratos as $ctr): 
                                $diasProxima = diasEntre(date('Y-m-d'), $ctr['data_proxima_cobranca']);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $ctr['numero_contrato']; ?></strong>
                                        <br><small>Início: <?php echo formatarData($ctr['data_inicio']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $ctr['placa']; ?></strong>
                                        <br><small><?php echo $ctr['marca'] . ' ' . $ctr['modelo']; ?></small>
                                    </td>
                                    <td><?php echo formatarMoeda($ctr['valor_semanal']); ?></td>
                                    <td>
                                        <?php echo formatarData($ctr['data_proxima_cobranca']); ?>
                                        <?php if ($diasProxima <= 3): ?>
                                            <br><span class="badge badge-warning">Em <?php echo $diasProxima; ?> dias</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = [
                                            'ativo' => 'success',
                                            'suspenso' => 'warning',
                                            'encerrado' => 'secondary',
                                            'cancelado' => 'danger'
                                        ][$ctr['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst($ctr['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
