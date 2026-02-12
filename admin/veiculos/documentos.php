<?php
/**
 * Master Car - Documentos do Veículo
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

// Buscar documentos do veículo
$documentos = DB()->fetchAll("SELECT * FROM veiculos_documentos WHERE veiculo_id = ? ORDER BY created_at DESC", [$id]);

$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Upload de documentos
    if ($acao == 'upload_doc' && !empty($_FILES['documento'])) {
        $uploadDir = __DIR__ . '/../../uploads/veiculos_docs/' . $id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $arquivo = $_FILES['documento'];
        
        if ($arquivo['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            $permitidos = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            
            if (in_array($ext, $permitidos)) {
                $nomeOriginal = pathinfo($arquivo['name'], PATHINFO_FILENAME);
                $nomeArquivo = uniqid('doc_') . '.' . $ext;
                $destino = $uploadDir . $nomeArquivo;
                
                if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    DB()->insert('veiculos_documentos', [
                        'veiculo_id' => $id,
                        'arquivo' => 'uploads/veiculos_docs/' . $id . '/' . $nomeArquivo,
                        'nome_original' => $arquivo['name'],
                        'tipo' => $_POST['tipo'] ?? 'outro',
                        'descricao' => $_POST['descricao'] ?? '',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $sucesso = 'Documento enviado com sucesso!';
                    // Recarrega documentos
                    $documentos = DB()->fetchAll("SELECT * FROM veiculos_documentos WHERE veiculo_id = ? ORDER BY created_at DESC", [$id]);
                } else {
                    $erro = 'Erro ao mover o arquivo. Verifique as permissões da pasta.';
                }
            } else {
                $erro = 'Formato não permitido. Use: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX.';
            }
        } else {
            $erro = 'Erro no upload do arquivo. Código: ' . $arquivo['error'];
        }
    }
    
    // Excluir documento
    if ($acao == 'excluir_doc' && $_POST['doc_id']) {
        $doc = DB()->fetch("SELECT * FROM veiculos_documentos WHERE id = ? AND veiculo_id = ?", [$_POST['doc_id'], $id]);
        if ($doc) {
            $caminho = __DIR__ . '/../../' . $doc['arquivo'];
            if (file_exists($caminho)) {
                unlink($caminho);
            }
            DB()->delete('veiculos_documentos', 'id = ?', [$_POST['doc_id']]);
            $sucesso = 'Documento excluído com sucesso!';
            // Recarrega documentos
            $documentos = DB()->fetchAll("SELECT * FROM veiculos_documentos WHERE veiculo_id = ? ORDER BY created_at DESC", [$id]);
        }
    }
}

$tiposDocumento = [
    'crv' => 'CRV (Certificado de Registro de Veículo)',
    'crvl' => 'CRLV (Certificado de Registro e Licenciamento)',
    'seguro' => 'Apólice de Seguro',
    'ipva' => 'Comprovante de IPVA',
    'dpvat' => 'Comprovante de DPVAT',
    'licenciamento' => 'Licenciamento',
    'multa' => 'Multa/Infração',
    'manutencao' => 'Ordem de Manutenção',
    'vistoria' => 'Laudo de Vistoria',
    'outro' => 'Outro Documento'
];

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos do Veículo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .doc-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            transition: box-shadow 0.2s;
        }
        .doc-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .doc-icon {
            font-size: 48px;
            color: #2563eb;
            margin-bottom: 15px;
        }
        .doc-tipo {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        .doc-nome {
            font-weight: 600;
            margin-bottom: 10px;
            word-break: break-word;
        }
        .doc-data {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        .doc-actions {
            display: flex;
            gap: 10px;
        }
        .upload-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .tipo-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .tipo-crv { background: #dbeafe; color: #1e40af; }
        .tipo-crvl { background: #dcfce7; color: #166534; }
        .tipo-seguro { background: #fef3c7; color: #92400e; }
        .tipo-ipva { background: #fee2e2; color: #991b1b; }
        .tipo-dpvat { background: #f3e8ff; color: #7c3aed; }
        .tipo-licenciamento { background: #ecfdf5; color: #047857; }
        .tipo-multa { background: #fff1f2; color: #be123c; }
        .tipo-manutencao { background: #f0f9ff; color: #0369a1; }
        .tipo-vistoria { background: #f5f3ff; color: #6d28d9; }
        .tipo-outro { background: #f3f4f6; color: #4b5563; }
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
                    <h1 class="page-title">Documentos do Veículo</h1>
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
            
            <!-- Upload de Documentos -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cloud-upload-alt"></i> Enviar Novo Documento</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="acao" value="upload_doc">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Arquivo</label>
                                <input type="file" name="documento" class="form-control" required 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                <small style="color: var(--gray);">Formatos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (máx. 10MB)</small>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label">Tipo de Documento</label>
                                <select name="tipo" class="form-control" required>
                                    <?php foreach ($tiposDocumento as $key => $label): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 15px;">
                            <label class="form-label">Descrição (opcional)</label>
                            <input type="text" name="descricao" class="form-control" 
                                   placeholder="Ex: IPVA 2024, Seguro Anual, etc.">
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Enviar Documento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Documentos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder-open"></i> Documentos Cadastrados (<?php echo count($documentos); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($documentos)): ?>
                        <div class="doc-grid">
                            <?php foreach ($documentos as $doc): 
                                $ext = pathinfo($doc['arquivo'], PATHINFO_EXTENSION);
                                $iconClass = [
                                    'pdf' => 'fa-file-pdf',
                                    'jpg' => 'fa-file-image',
                                    'jpeg' => 'fa-file-image',
                                    'png' => 'fa-file-image',
                                    'doc' => 'fa-file-word',
                                    'docx' => 'fa-file-word',
                                    'xls' => 'fa-file-excel',
                                    'xlsx' => 'fa-file-excel'
                                ][$ext] ?? 'fa-file';
                                
                                $tipoClass = 'tipo-' . $doc['tipo'];
                            ?>
                                <div class="doc-card">
                                    <div style="text-align: center;">
                                        <i class="fas <?php echo $iconClass; ?> doc-icon"></i>
                                    </div>
                                    <div class="doc-tipo">
                                        <span class="tipo-badge <?php echo $tipoClass; ?>">
                                            <?php echo $tiposDocumento[$doc['tipo']] ?? 'Documento'; ?>
                                        </span>
                                    </div>
                                    <div class="doc-nome"><?php echo htmlspecialchars($doc['nome_original']); ?></div>
                                    <?php if ($doc['descricao']): ?>
                                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                                            <?php echo htmlspecialchars($doc['descricao']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="doc-data">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo formatarData($doc['created_at']); ?>
                                    </div>
                                    <div class="doc-actions">
                                        <a href="<?php echo BASE_URL . '/' . $doc['arquivo']; ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-primary"
                                           style="flex: 1;">
                                            <i class="fas fa-eye"></i> Visualizar
                                        </a>
                                        <a href="<?php echo BASE_URL . '/' . $doc['arquivo']; ?>" 
                                           download 
                                           class="btn btn-sm btn-light"
                                           title="Baixar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form method="POST" action="" style="display: inline;" 
                                              onsubmit="return confirm('Tem certeza que deseja excluir este documento?')">
                                            <input type="hidden" name="acao" value="excluir_doc">
                                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px; color: #666;">
                            <i class="fas fa-folder-open" style="font-size: 64px; margin-bottom: 20px; color: #ddd;"></i>
                            <h3>Nenhum documento cadastrado</h3>
                            <p>Envie documentos do veículo usando o formulário acima</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
