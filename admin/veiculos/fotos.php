<?php
/**
 * Master Car - Fotos do Veículo
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

$sucesso = '';
$erro = '';

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
                $ext = strtolower(pathinfo($arquivos['name'][$i], PATHINFO_EXTENSION));
                $permitidos = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($ext, $permitidos)) {
                    $nomeArquivo = uniqid('veiculo_') . '.' . $ext;
                    $destino = $uploadDir . $nomeArquivo;
                    
                    if (move_uploaded_file($arquivos['tmp_name'][$i], $destino)) {
                        DB()->insert('veiculos_fotos', [
                            'veiculo_id' => $id,
                            'arquivo' => 'uploads/veiculos/' . $id . '/' . $nomeArquivo,
                            'descricao' => $_POST['descricao'] ?? '',
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        $uploaded++;
                    }
                }
            }
        }
        
        if ($uploaded > 0) {
            $sucesso = "$uploaded foto(s) enviada(s) com sucesso!";
            // Recarrega fotos
            $fotos = DB()->fetchAll("SELECT * FROM veiculos_fotos WHERE veiculo_id = ? ORDER BY created_at DESC", [$id]);
        } else {
            $erro = 'Nenhuma foto foi enviada. Verifique o formato (JPG, PNG, GIF).';
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
            // Recarrega fotos
            $fotos = DB()->fetchAll("SELECT * FROM veiculos_fotos WHERE veiculo_id = ? ORDER BY created_at DESC", [$id]);
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
    <title>Fotos do Veículo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .galeria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .foto-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .foto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .foto-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
        }
        .foto-card .foto-info {
            padding: 10px;
            background: #f8f9fa;
        }
        .foto-card .foto-data {
            font-size: 11px;
            color: #666;
        }
        .foto-card .foto-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }
        .upload-area {
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f0f7ff;
            margin-bottom: 30px;
        }
        .upload-area:hover {
            background: #e0efff;
        }
        .upload-area i {
            font-size: 48px;
            color: #2563eb;
            margin-bottom: 15px;
        }
        .cliente-envio-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
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
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $sucesso; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $erro; ?></span>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Fotos do Veículo</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/veiculos/">Veículos</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span><?php echo $veiculo['marca'] . ' ' . $veiculo['modelo']; ?> - <?php echo $veiculo['placa']; ?></span>
                    </div>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/veiculos/ver.php?id=<?php echo $id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
            
            <!-- Upload de Fotos -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cloud-upload-alt"></i> Enviar Novas Fotos</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="upload_foto">
                        <div class="upload-area" onclick="document.getElementById('inputFotos').click()">
                            <i class="fas fa-camera"></i>
                            <h3>Clique para selecionar fotos</h3>
                            <p style="color: #666;">Ou arraste e solte imagens aqui</p>
                            <p style="font-size: 12px; color: #999;">Formatos aceitos: JPG, PNG, GIF (máx. 5MB cada)</p>
                        </div>
                        <input type="file" id="inputFotos" name="fotos[]" multiple accept="image/*" style="display: none;" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
            
            <!-- Galeria de Fotos -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-images"></i> Galeria de Fotos (<?php echo count($fotos); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($fotos)): ?>
                        <div class="galeria-grid">
                            <?php foreach ($fotos as $foto): ?>
                                <div class="foto-card">
                                    <img src="<?php echo BASE_URL . '/' . $foto['arquivo']; ?>" 
                                         alt="Foto do veículo"
                                         onclick="abrirModal('<?php echo BASE_URL . '/' . $foto['arquivo']; ?>')">
                                    <div class="foto-info">
                                        <div class="foto-data">
                                            <i class="fas fa-calendar-alt"></i> 
                                            <?php echo formatarData($foto['created_at']); ?>
                                        </div>
                                        <div class="foto-actions">
                                            <a href="<?php echo BASE_URL . '/' . $foto['arquivo']; ?>" 
                                               download 
                                               class="btn btn-sm btn-light"
                                               title="Baixar">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Tem certeza que deseja excluir esta foto?')">
                                                <input type="hidden" name="acao" value="excluir_foto">
                                                <input type="hidden" name="foto_id" value="<?php echo $foto['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px; color: #666;">
                            <i class="fas fa-image" style="font-size: 64px; margin-bottom: 20px; color: #ddd;"></i>
                            <h3>Nenhuma foto cadastrada</h3>
                            <p>Envie fotos do veículo usando a área acima</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Enviar para Clientes -->
            <?php if (!empty($contratosAtivos) && !empty($fotos)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane"></i> Enviar Fotos para Clientes</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 15px;">
                        <?php foreach ($contratosAtivos as $contrato): ?>
                            <div class="cliente-envio-card">
                                <div>
                                    <strong><i class="fas fa-user"></i> <?php echo $contrato['cliente_nome']; ?></strong><br>
                                    <small style="color: #666;">
                                        <i class="fas fa-file-contract"></i> Contrato: <?php echo $contrato['numero_contrato']; ?> | 
                                        <i class="fas fa-calendar"></i> <?php echo formatarData($contrato['data_inicio']); ?>
                                    </small>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <?php if ($contrato['email']): ?>
                                        <a href="mailto:<?php echo $contrato['email']; ?>?subject=Fotos do Veículo <?php echo $veiculo['placa']; ?>&body=Olá <?php echo $contrato['cliente_nome']; ?>,%0D%0A%0D%0ASeguem as fotos do veículo <?php echo $veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - Placa: ' . $veiculo['placa']; ?>.%0D%0A%0D%0AVocê pode visualizar todas as fotos em: <?php echo BASE_URL; ?>/admin/veiculos/fotos.php?id=<?php echo $id; ?>%0D%0A%0D%0AAtenciosamente,%0D%0A<?php echo SITE_NAME; ?>" 
                                           class="btn btn-primary" target="_blank">
                                            <i class="fas fa-envelope"></i> Email
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($contrato['celular'] || $contrato['telefone']): 
                                        $whatsapp = $contrato['celular'] ?: $contrato['telefone'];
                                        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
                                        $mensagem = urlencode("Olá " . $contrato['cliente_nome'] . "! 🚗\n\nSeguem as fotos do veículo " . $veiculo['marca'] . " " . $veiculo['modelo'] . " - Placa: " . $veiculo['placa'] . ".\n\nAcesse aqui: " . BASE_URL . "/admin/veiculos/fotos.php?id=" . $id);
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
    
    <!-- Modal para visualização -->
    <div id="modalFoto" class="modal" onclick="fecharModal()">
        <span class="modal-close">&times;</span>
        <img id="modalImg" src="" alt="Foto ampliada">
    </div>
    
    <script>
        function abrirModal(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('modalFoto').classList.add('active');
        }
        
        function fecharModal() {
            document.getElementById('modalFoto').classList.remove('active');
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });
    </script>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
