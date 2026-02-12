<?php
/**
 * Master Car - Visualizar Cliente
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$id = $_GET['id'] ?? 0;

$cliente = DB()->fetch("SELECT * FROM clientes WHERE id = ?", [$id]);
if (!$cliente) {
    mostrarAlerta('Cliente não encontrado.', 'danger');
    redirecionar('/admin/clientes/');
}

// Contratos do cliente
$contratos = DB()->fetchAll("
    SELECT cs.*, v.placa, v.marca, v.modelo
    FROM contratos_semanal cs
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.cliente_id = ?
    ORDER BY cs.created_at DESC
", [$id]);

// Faturas pendentes
$faturasPendentes = DB()->fetchAll("
    SELECT * FROM faturas_semanal 
    WHERE cliente_id = ? AND status IN ('pendente', 'vencido')
    ORDER BY data_vencimento ASC
", [$id]);

// Documentos
$documentos = DB()->fetchAll("SELECT * FROM documentos_cliente WHERE cliente_id = ?", [$id]);

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $cliente['nome']; ?> - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title"><?php echo $cliente['nome']; ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/clientes/">Clientes</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Visualizar</span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/clientes/editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Dados Pessoais -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Dados Pessoais</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">STATUS</div>
                            <span class="badge badge-<?php echo $cliente['status'] == 'ativo' ? 'success' : ($cliente['status'] == 'bloqueado' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst($cliente['status']); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">CPF/CNPJ</div>
                            <div style="font-weight: 600;"><?php echo formatarCpfCnpj($cliente['cpf_cnpj']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">E-MAIL</div>
                            <div><?php echo $cliente['email'] ?: 'Não informado'; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">TELEFONE</div>
                            <div><?php echo $cliente['telefone'] ? formatarTelefone($cliente['telefone']) : 'Não informado'; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">CELULAR</div>
                            <div><?php echo $cliente['celular'] ? formatarTelefone($cliente['celular']) : 'Não informado'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Endereço -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">CEP</div>
                            <div><?php echo $cliente['cep'] ?: 'Não informado'; ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">ENDEREÇO</div>
                            <div style="font-weight: 600;">
                                <?php 
                                echo $cliente['endereco'];
                                if ($cliente['numero']) echo ', ' . $cliente['numero'];
                                if ($cliente['complemento']) echo ' - ' . $cliente['complemento'];
                                ?>
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="color: var(--gray); font-size: 12px;">BAIRRO</div>
                            <div><?php echo $cliente['bairro']; ?></div>
                        </div>
                        <div>
                            <div style="color: var(--gray); font-size: 12px;">CIDADE/UF</div>
                            <div><?php echo $cliente['cidade'] . '/' . $cliente['estado']; ?></div>
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
                                        <th>Veículo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contratos as $ctr): ?>
                                        <tr>
                                            <td><?php echo $ctr['numero_contrato']; ?></td>
                                            <td><?php echo $ctr['placa']; ?></td>
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
                
                <!-- Faturas Pendentes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-receipt"></i> Faturas Pendentes (<?php echo count($faturasPendentes); ?>)</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($faturasPendentes)): ?>
                            <div style="padding: 20px; text-align: center; color: var(--gray);">
                                Cliente em dia.
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fatura</th>
                                        <th>Valor</th>
                                        <th>Vencimento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faturasPendentes as $fat): ?>
                                        <tr>
                                            <td><?php echo $fat['numero_fatura']; ?></td>
                                            <td><?php echo formatarMoeda($fat['valor_total']); ?></td>
                                            <td><?php echo formatarData($fat['data_vencimento']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
