<?php
/**
 * Master Car - Visualizar Contrato
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$id = $_GET['id'] ?? 0;

$contrato = DB()->fetch("
    SELECT cs.*, c.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone as cliente_telefone,
           v.*, v.placa as veiculo_placa
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.id = ?
", [$id]);

if (!$contrato) {
    mostrarAlerta('Contrato não encontrado.', 'danger');
    redirecionar('/admin/contratos/');
}

// Faturas do contrato
$faturas = DB()->fetchAll("
    SELECT * FROM faturas_semanal 
    WHERE contrato_id = ? 
    ORDER BY data_emissao DESC
", [$id]);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato <?php echo $contrato['numero_contrato']; ?> - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Contrato <?php echo $contrato['numero_contrato']; ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/contratos/">Contratos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Visualizar</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/contratos/editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Dados do Contrato -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-contract"></i> Dados do Contrato</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">STATUS</div>
                            <span class="badge badge-<?php echo $contrato['status'] == 'ativo' ? 'success' : ($contrato['status'] == 'suspenso' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($contrato['status']); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">DATA DE INÍCIO</div>
                            <div style="font-weight: 600;"><?php echo formatarData($contrato['data_inicio']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">VALOR SEMANAL</div>
                            <div style="font-weight: 600; color: var(--primary);"><?php echo formatarMoeda($contrato['valor_semanal']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">PRÓXIMA COBRANÇA</div>
                            <div style="font-weight: 600;"><?php echo formatarData($contrato['data_proxima_cobranca']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">SEMANAS PAGAS</div>
                            <div style="font-weight: 600;"><?php echo $contrato['semanas_pagas']; ?> / <?php echo $contrato['total_semanas']; ?></div>
                        </div>
                        <?php if ($contrato['observacoes']): ?>
                            <div>
                                <div style="color: var(--gray); font-size: 12px;">OBSERVAÇÕES</div>
                                <div><?php echo nl2br($contrato['observacoes']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Cliente -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Cliente</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">NOME</div>
                            <div style="font-weight: 600;"><?php echo $contrato['cliente_nome']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">CPF/CNPJ</div>
                            <div><?php echo formatarCpfCnpj($contrato['cpf_cnpj']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">E-MAIL</div>
                            <div><?php echo $contrato['email']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">TELEFONE</div>
                            <div><?php echo formatarTelefone($contrato['telefone']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Veículo -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-car"></i> Veículo</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">PLACA</div>
                            <div style="font-weight: 600;"><?php echo $contrato['veiculo_placa']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">VEÍCULO</div>
                            <div style="font-weight: 600;"><?php echo $contrato['marca'] . ' ' . $contrato['modelo']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">ANO</div>
                            <div><?php echo $contrato['ano_fabricacao'] . '/' . $contrato['ano_modelo']; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">COR</div>
                            <div><?php echo $contrato['cor']; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Faturas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-receipt"></i> Faturas (<?php echo count($faturas); ?>)</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($faturas)): ?>
                            <div style="padding: 20px; text-align: center; color: var(--gray);">
                                Nenhuma fatura gerada.
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fatura</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($faturas, 0, 5) as $fat): ?>
                                        <tr>
                                            <td><?php echo $fat['numero_fatura']; ?></td>
                                            <td><?php echo formatarMoeda($fat['valor_total']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $STATUS_FATURA[$fat['status']]['class'] ?? 'secondary'; ?>">
                                                    <?php echo $STATUS_FATURA[$fat['status']]['label'] ?? $fat['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (count($faturas) > 5): ?>
                                <div style="padding: 10px; text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>/admin/faturas/?contrato=<?php echo $id; ?>">Ver todas (<?php echo count($faturas); ?>)</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
