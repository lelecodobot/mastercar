<?php
/**
 * Master Car - Contratos do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();

// Contratos do cliente
$contratos = DB()->fetchAll("
    SELECT cs.*, v.placa, v.marca, v.modelo, v.cor, v.ano_modelo
    FROM contratos_semanal cs
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.cliente_id = ?
    ORDER BY cs.created_at DESC
", [$cliente['id']]);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Contratos - <?php echo SITE_NAME; ?></title>
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
        .contrato-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        .contrato-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }
        .veiculo-info {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .veiculo-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body style="background: #f1f5f9;">
    <div style="max-width: 1000px; margin: 0 auto; padding: 20px;">
        
        <div class="cliente-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-file-contract"></i> Meus Contratos</h2>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/cliente/" style="color: white; opacity: 0.9;">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
        
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
        
        <?php if (empty($contratos)): ?>
            <div class="card" style="text-align: center; padding: 50px;">
                <i class="fas fa-file-contract" style="font-size: 64px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3>Nenhum contrato encontrado</h3>
                <p style="color: var(--gray);">Você ainda não possui contratos ativos.</p>
            </div>
        <?php else: ?>
            <?php foreach ($contratos as $ctr): 
                $diasProxima = diasEntre(date('Y-m-d'), $ctr['data_proxima_cobranca']);
            ?>
                <div class="contrato-card">
                    <div class="contrato-header">
                        <div class="veiculo-info">
                            <div class="veiculo-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0;"><?php echo $ctr['marca'] . ' ' . $ctr['modelo']; ?></h3>
                                <p style="margin: 5px 0 0; color: var(--gray);">
                                    <?php echo $ctr['placa']; ?> | 
                                    <?php echo $ctr['ano_modelo']; ?> | 
                                    <?php echo $ctr['cor']; ?>
                                </p>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-<?php echo $ctr['status'] == 'ativo' ? 'success' : ($ctr['status'] == 'suspenso' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($ctr['status']); ?>
                            </span>
                            <div style="margin-top: 10px;">
                                <a href="<?php echo BASE_URL; ?>/cliente/contrato_ver.php?id=<?php echo $ctr['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Ver Contrato
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div>
                            <div style="color: var(--gray); font-size: 12px; margin-bottom: 5px;">CONTRATO</div>
                            <div style="font-weight: 600;"><?php echo $ctr['numero_contrato']; ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px; margin-bottom: 5px;">DATA DE INÍCIO</div>
                            <div style="font-weight: 600;"><?php echo formatarData($ctr['data_inicio']); ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px; margin-bottom: 5px;">VALOR SEMANAL</div>
                            <div style="font-weight: 600; color: var(--primary);"><?php echo formatarMoeda($ctr['valor_semanal']); ?></div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="color: var(--gray); font-size: 12px; margin-bottom: 5px;">PRÓXIMA COBRANÇA</div>
                                <div style="font-weight: 600;">
                                    <?php echo formatarData($ctr['data_proxima_cobranca']); ?>
                                    <?php if ($diasProxima <= 3 && $diasProxima >= 0): ?>
                                        <span class="badge badge-warning">Em <?php echo $diasProxima; ?> dias</span>
                                    <?php elseif ($diasProxima < 0): ?>
                                        <span class="badge badge-danger">Atrasada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: var(--gray); font-size: 12px; margin-bottom: 5px;">SEMANAS PAGAS</div>
                                <div style="font-weight: 600;"><?php echo $ctr['semanas_pagas']; ?> / <?php echo $ctr['total_semanas']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
