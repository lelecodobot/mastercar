<?php
/**
 * Master Car - Faturas do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();

// Todas as faturas do cliente
$faturas = DB()->fetchAll("
    SELECT f.*, cs.numero_contrato, v.placa
    FROM faturas_semanal f
    JOIN contratos_semanal cs ON f.contrato_id = cs.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE f.cliente_id = ?
    ORDER BY f.data_emissao DESC
", [$cliente['id']]);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Faturas - <?php echo SITE_NAME; ?></title>
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
                    <h2><i class="fas fa-receipt"></i> Minhas Faturas</h2>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/cliente/" style="color: white; opacity: 0.9;">
                        <i class="fas fa-arrow-left"></i> Voltar
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
        </div>
        
        <?php if ($alerta): ?>
            <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                <span><?php echo $alerta['mensagem']; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Lista de Faturas -->
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <?php if (empty($faturas)): ?>
                    <div style="padding: 50px; text-align: center; color: var(--gray);">
                        <i class="fas fa-receipt" style="font-size: 64px; margin-bottom: 20px;"></i>
                        <h3>Nenhuma fatura encontrada</h3>
                        <p>Você ainda não possui faturas em nosso sistema.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fatura</th>
                                    <th>Contrato</th>
                                    <th>Veículo</th>
                                    <th>Valor</th>
                                    <th>Emissão</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faturas as $fat): 
                                    $diasAtraso = 0;
                                    if (($fat['status'] == 'vencido' || ($fat['status'] == 'pendente' && $fat['data_vencimento'] < date('Y-m-d')))) {
                                        $diasAtraso = diasEntre($fat['data_vencimento'], date('Y-m-d'));
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $fat['numero_fatura']; ?></strong>
                                            <br><small style="color: var(--gray);">Semana <?php echo $fat['semana_referencia']; ?></small>
                                        </td>
                                        <td><?php echo $fat['numero_contrato']; ?></td>
                                        <td><?php echo $fat['placa']; ?></td>
                                        <td>
                                            <strong><?php echo formatarMoeda($fat['valor_total']); ?></strong>
                                            <?php if ($fat['valor_multa'] > 0 || $fat['valor_juros'] > 0): ?>
                                                <br><small style="color: var(--danger);">
                                                    Multa: <?php echo formatarMoeda($fat['valor_multa']); ?> | 
                                                    Juros: <?php echo formatarMoeda($fat['valor_juros']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatarData($fat['data_emissao']); ?></td>
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
                                            <a href="<?php echo BASE_URL; ?>/cliente/fatura.php?id=<?php echo $fat['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
