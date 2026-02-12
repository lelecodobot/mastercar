<?php
/**
 * Master Car - Editar Veículo
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

// Buscar fotos do veículo
$fotos = DB()->fetchAll("SELECT * FROM veiculos_fotos WHERE veiculo_id = ? ORDER BY created_at DESC", [$id]);

// Buscar contratos ativos para envio
$contratosAtivos = DB()->fetchAll("
    SELECT cs.*, c.nome as cliente_nome, c.email, c.telefone, c.celular
    FROM contratos_semanal cs
    JOIN clientes c ON cs.cliente_id = c.id
    WHERE cs.veiculo_id = ? AND cs.status = 'ativo'
", [$id]);

$erros = [];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Upload de fotos
    if ($acao == 'upload_foto' && !empty($_FILES['fotos'])) {
        $uploadDir = __DIR__ . '/../../uploads/veiculos/' . $id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $arquivos = $_FILES['fotos'];
        $total = count($arquivos['name']);
        $uploaded = 0;
        
        for ($i = 0; $i < $total; $i++) {
            if ($arquivos['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($arquivos['name'][$i], PATHINFO_EXTENSION);
                $nomeArquivo = uniqid('veiculo_') . '.' . $ext;
                $destino = $uploadDir . $nomeArquivo;
                
                if (move_uploaded_file($arquivos['tmp_name'][$i], $destino)) {
                    DB()->insert('veiculos_fotos', [
                        'veiculo_id' => $id,
                        'arquivo' => 'uploads/veiculos/' . $id . '/' . $nomeArquivo,
                        'descricao' => $_POST['descricao'][$i] ?? '',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $uploaded++;
                }
            }
        }
        
        if ($uploaded > 0) {
            $sucesso = "$uploaded foto(s) enviada(s) com sucesso!";
        }
    }
    
    // Excluir foto
    if ($acao == 'excluir_foto' && $_POST['foto_id']) {
        $foto = DB()->fetch("SELECT * FROM veiculos_fotos WHERE id = ? AND veiculo_id = ?", [$_POST['foto_id'], $id]);
        if ($foto) {
            $caminho = __DIR__ . '/../../' . $foto['arquivo'];
            if (file_exists($caminho)) {
                unlink($caminho);
            }
            DB()->delete('veiculos_fotos', 'id = ?', [$_POST['foto_id']]);
            $sucesso = 'Foto excluída com sucesso!';
        }
    }
    
    // Atualizar dados do veículo
    if ($acao == 'atualizar') {
        try {
            $data = [
                'marca' => $_POST['marca'] ?? $veiculo['marca'],
                'modelo' => $_POST['modelo'] ?? $veiculo['modelo'],
                'ano_fabricacao' => $_POST['ano_fabricacao'] ?: null,
                'ano_modelo' => $_POST['ano_modelo'] ?: null,
                'cor' => $_POST['cor'] ?? '',
                'chassi' => $_POST['chassi'] ?? '',
                'renavam' => $_POST['renavam'] ?? '',
                'categoria' => $_POST['categoria'] ?? $veiculo['categoria'],
                'km_atual' => $_POST['km_atual'] ?: 0,
                'valor_semanal' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_semanal'] ?? '0'),
                'valor_diaria' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_diaria'] ?? '0'),
                'valor_mensal' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_mensal'] ?? '0'),
                'status' => $_POST['status'] ?? $veiculo['status'],
                'seguradora' => $_POST['seguradora'] ?? '',
                'apolice' => $_POST['apolice'] ?? '',
                'vencimento_seguro' => $_POST['vencimento_seguro'] ?: null,
                'observacoes' => $_POST['observacoes'] ?? ''
            ];
            
            DB()->update('veiculos', $data, 'id = :id', ['id' => $id]);
            
            mostrarAlerta('Veículo atualizado com sucesso!', 'success');
            redirecionar('/admin/veiculos/editar.php?id=' . $id);
            
        } catch (Exception $e) {
            $erros[] = 'Erro ao atualizar veículo: ' . $e->getMessage();
        }
    }
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Veículo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Editar Veículo</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/veiculos/">Veículos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Editar</span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo $erro; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-car"></i> Dados do Veículo <?php echo $veiculo['placa']; ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" value="<?php echo $veiculo['marca']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" value="<?php echo $veiculo['modelo']; ?>">
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Ano Fab.</label>
                                <input type="number" name="ano_fabricacao" class="form-control" value="<?php echo $veiculo['ano_fabricacao']; ?>">
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Ano Mod.</label>
                                <input type="number" name="ano_modelo" class="form-control" value="<?php echo $veiculo['ano_modelo']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">Cor</label>
                                <input type="text" name="cor" class="form-control" value="<?php echo $veiculo['cor']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-control">
                                    <?php foreach ($CATEGORIAS_VEICULO as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $veiculo['categoria'] == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">KM Atual</label>
                                <input type="number" name="km_atual" class="form-control" value="<?php echo $veiculo['km_atual']; ?>">
                            </div>
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="disponivel" <?php echo $veiculo['status'] == 'disponivel' ? 'selected' : ''; ?>>Disponível</option>
                                    <option value="alugado" <?php echo $veiculo['status'] == 'alugado' ? 'selected' : ''; ?>>Alugado</option>
                                    <option value="manutencao" <?php echo $veiculo['status'] == 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
                                    <option value="bloqueado" <?php echo $veiculo['status'] == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Valores</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Valor Semanal</label>
                                <input type="text" name="valor_semanal" class="form-control" data-mask="moeda" value="<?php echo formatarMoeda($veiculo['valor_semanal']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor Diária</label>
                                <input type="text" name="valor_diaria" class="form-control" data-mask="moeda" value="<?php echo $veiculo['valor_diaria'] ? formatarMoeda($veiculo['valor_diaria']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor Mensal</label>
                                <input type="text" name="valor_mensal" class="form-control" data-mask="moeda" value="<?php echo $veiculo['valor_mensal'] ? formatarMoeda($veiculo['valor_mensal']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="acao" value="atualizar">
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/ver.php?id=<?php echo $id; ?>" class="btn btn-light">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
            
            <!-- Seção de Fotos do Veículo -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-camera"></i> Fotos do Veículo</h3>
                </div>
                <div class="card-body">
                    <!-- Upload de Fotos -->
                    <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 30px;">
                        <input type="hidden" name="acao" value="upload_foto">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Selecionar Fotos</label>
                                <input type="file" name="fotos[]" class="form-control" multiple accept="image/*" required>
                                <small style="color: var(--gray);">Pode selecionar múltiplas fotos (JPG, PNG)</small>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-success" style="width: 100%;">
                                    <i class="fas fa-upload"></i> Enviar Fotos
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Galeria de Fotos -->
                    <?php if (!empty($fotos)): ?>
                        <h4 style="margin-bottom: 15px; font-size: 14px; color: var(--gray);">Fotos Cadastradas (<?php echo count($fotos); ?>)</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                            <?php foreach ($fotos as $foto): ?>
                                <div style="position: relative; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                                    <img src="<?php echo BASE_URL . '/' . $foto['arquivo']; ?>" 
                                         style="width: 100%; height: 120px; object-fit: cover; cursor: pointer;"
                                         onclick="window.open('<?php echo BASE_URL . '/' . $foto['arquivo']; ?>', '_blank')">
                                    <div style="padding: 8px; background: #f8f9fa;">
                                        <small style="font-size: 10px; color: var(--gray);">
                                            <?php echo formatarData($foto['created_at']); ?>
                                        </small>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Excluir esta foto?')">
                                            <input type="hidden" name="acao" value="excluir_foto">
                                            <input type="hidden" name="foto_id" value="<?php echo $foto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" style="float: right; padding: 2px 6px; font-size: 10px;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>Nenhuma foto cadastrada</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Seção de Envio para Cliente -->
            <?php if (!empty($contratosAtivos) && !empty($fotos)): ?>
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane"></i> Enviar Fotos para Cliente</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 15px;">
                        <?php foreach ($contratosAtivos as $contrato): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <div>
                                    <strong><?php echo $contrato['cliente_nome']; ?></strong><br>
                                    <small style="color: var(--gray);">
                                        Contrato: <?php echo $contrato['numero_contrato']; ?> | 
                                        <?php echo formatarData($contrato['data_inicio']); ?>
                                    </small>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <?php if ($contrato['email']): ?>
                                        <a href="mailto:<?php echo $contrato['email']; ?>?subject=Fotos do Veículo <?php echo $veiculo['placa']; ?>&body=Olá <?php echo $contrato['cliente_nome']; ?>,%0D%0A%0D%0ASeguem as fotos do veículo <?php echo $veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - Placa: ' . $veiculo['placa']; ?>.%0D%0A%0D%0AAtenciosamente,%0D%0A<?php echo SITE_NAME; ?>" 
                                           class="btn btn-primary" target="_blank">
                                            <i class="fas fa-envelope"></i> Email
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($contrato['celular'] || $contrato['telefone']): 
                                        $whatsapp = $contrato['celular'] ?: $contrato['telefone'];
                                        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
                                        $mensagem = urlencode("Olá " . $contrato['cliente_nome'] . "! Seguem as fotos do veículo " . $veiculo['marca'] . " " . $veiculo['modelo'] . " - Placa: " . $veiculo['placa'] . ". Acesse: " . BASE_URL . "/admin/veiculos/editar.php?id=" . $id);
                                    ?>
                                        <a href="https://wa.me/55<?php echo $whatsapp; ?>?text=<?php echo $mensagem; ?>" 
                                           class="btn btn-success" target="_blank" style="background: #25d366; border-color: #25d366;">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
