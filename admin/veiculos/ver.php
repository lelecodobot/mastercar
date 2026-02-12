<?php
/**
 * Master Car - Visualizar Veículo
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$id = $_GET['id'] ?? 0;

$veiculo = DB()->fetch("SELECT * FROM veiculos WHERE id = ?", [$id]);
if (!$veiculo) {
    mostrarAlerta('Veículo não encontrado.', 'danger');
    redirecionar('/admin/veiculos/');
}

// Contratos do veículo
$contratos = DB()->fetchAll("
    SELECT cs.*, c.nome as cliente_nome
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    WHERE cs.veiculo_id = ?
    ORDER BY cs.created_at DESC
", [$id]);

// Manutenções
$manutencoes = DB()->fetchAll("
    SELECT * FROM manutencoes WHERE veiculo_id = ? ORDER BY data_manutencao DESC
", [$id]);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $veiculo['placa']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h1 class="page-title"><?php echo $veiculo['placa']; ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/veiculos/">Veículos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Visualizar</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Dados do Veículo -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-car"></i> Dados do Veículo</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">STATUS</div>
                            <span class="badge badge-<?php echo $veiculo['status'] == 'disponivel' ? 'success' : ($veiculo['status'] == 'alugado' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($veiculo['status']); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">MARCA/MODELO</div>
                            <div style="font-weight: 600;"><?php echo $veiculo['marca'] . ' ' . $veiculo['modelo']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">ANO</div>
                            <div><?php echo $veiculo['ano_fabricacao'] . '/' . $veiculo['ano_modelo']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">COR</div>
                            <div><?php echo $veiculo['cor']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">CATEGORIA</div>
                            <div><?php echo $CATEGORIAS_VEICULO[$veiculo['categoria']] ?? $veiculo['categoria']; ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px;">KM ATUAL</div>
                            <div style="font-weight: 600;"><?php echo number_format($veiculo['km_atual'], 0, ',', '.'); ?> km</div>
                        </div>
                    </div>
                </div>
                
                <!-- Valores -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Valores</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">VALOR SEMANAL</div>
                            <div style="font-weight: 600; color: var(--primary);"><?php echo formatarMoeda($veiculo['valor_semanal']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">VALOR DIÁRIA</div>
                            <div><?php echo $veiculo['valor_diaria'] ? formatarMoeda($veiculo['valor_diaria']) : 'Não definido'; ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px;">VALOR MENSAL</div>
                            <div><?php echo $veiculo['valor_mensal'] ? formatarMoeda($veiculo['valor_mensal']) : 'Não definido'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Contratos -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-contract"></i> Contratos (<?php echo count($contratos); ?>)</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($contratos)): ?>
                            <div style="padding: 20px; text-align: center; color: var(--gray);">
                                Nenhum contrato.
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Contrato</th>
                                        <th>Cliente</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contratos as $ctr): ?>
                                        <tr>
                                            <td><?php echo $ctr['numero_contrato']; ?></td>
                                            <td><?php echo $ctr['cliente_nome']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $ctr['status'] == 'ativo' ? 'success' : 'warning'; ?>">
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
                
                <!-- Documentação -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Documentação</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">CHASSI</div>
                            <div><?php echo $veiculo['chassi'] ?: 'Não informado'; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">RENAVAM</div>
                            <div><?php echo $veiculo['renavam'] ?: 'Não informado'; ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px;">SEGURO</div>
                            <div>
                                <?php 
                                if ($veiculo['seguradora']) {
                                    echo $veiculo['seguradora'] . ' - Apólice: ' . $veiculo['apolice'];
                                    if ($veiculo['vencimento_seguro']) {
                                        echo '<br>Vencimento: ' . formatarData($veiculo['vencimento_seguro']);
                                    }
                                } else {
                                    echo 'Não informado';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
