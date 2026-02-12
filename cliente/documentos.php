<?php
/**
 * Master Car - Documentos (Área do Cliente)
 * Inclui: Documentos do Veículo + Documentos Pessoais
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();

// Processar upload de documento pessoal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['documento'])) {
    $tipo = $_POST['tipo_documento'] ?? 'outro';
    $descricao = $_POST['descricao'] ?? '';
    
    $tiposPermitidos = ['cnh', 'rg', 'cpf', 'comprovante_residencia', 'contrato_social', 'outro'];
    if (!in_array($tipo, $tiposPermitidos)) {
        definirAlerta('danger', 'Tipo de documento inválido.');
        header('Location: ' . BASE_URL . '/cliente/documentos.php?aba=pessoais');
        exit;
    }
    
    $arquivo = $_FILES['documento'];
    $extensoesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $extensoesPermitidas)) {
        definirAlerta('danger', 'Formato de arquivo não permitido. Use PDF, JPG ou PNG.');
        header('Location: ' . BASE_URL . '/cliente/documentos.php?aba=pessoais');
        exit;
    }
    
    if ($arquivo['size'] > 10 * 1024 * 1024) {
        definirAlerta('danger', 'Arquivo muito grande. Máximo 10MB.');
        header('Location: ' . BASE_URL . '/cliente/documentos.php?aba=pessoais');
        exit;
    }
    
    // Criar pasta se não existir
    $pasta = '../uploads/clientes/' . $cliente['id'] . '/documentos/';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
    }
    
    $nomeArquivo = $tipo . '_' . time() . '.' . $ext;
    $caminho = $pasta . $nomeArquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        $caminhoDb = 'uploads/clientes/' . $cliente['id'] . '/documentos/' . $nomeArquivo;
        
        DB()->insert('clientes_documentos', [
            'cliente_id' => $cliente['id'],
            'tipo' => $tipo,
            'arquivo' => $caminhoDb,
            'descricao' => $descricao,
            'status' => 'pendente'
        ]);
        
        definirAlerta('success', 'Documento enviado com sucesso! Aguardando aprovação.');
    } else {
        definirAlerta('danger', 'Erro ao enviar documento. Tente novamente.');
    }
    
    header('Location: ' . BASE_URL . '/cliente/documentos.php?aba=pessoais');
    exit;
}

// Processar exclusão de documento pessoal
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $docId = (int)$_GET['excluir'];
    $documento = DB()->fetch("SELECT * FROM clientes_documentos WHERE id = ? AND cliente_id = ?", [$docId, $cliente['id']]);
    
    if ($documento) {
        // Excluir arquivo físico
        $caminhoArquivo = '../' . $documento['arquivo'];
        if (file_exists($caminhoArquivo)) {
            unlink($caminhoArquivo);
        }
        
        // Excluir do banco
        DB()->delete('clientes_documentos', 'id = :id', ['id' => $docId]);
        definirAlerta('success', 'Documento excluído com sucesso!');
    } else {
        definirAlerta('danger', 'Documento não encontrado.');
    }
    
    header('Location: ' . BASE_URL . '/cliente/documentos.php?aba=pessoais');
    exit;
}

// Aba ativa
$abaAtiva = $_GET['aba'] ?? 'veiculo';

// Buscar contratos do cliente
$contratos = DB()->fetchAll("
    SELECT cs.*, v.id as veiculo_id, v.placa, v.marca, v.modelo, v.ano_modelo, v.cor
    FROM contratos_semanal cs
    JOIN veiculos v ON cs.veiculo_id = v.id
    WHERE cs.cliente_id = ? AND cs.status = 'ativo'
", [$cliente['id']]);

// Se não tiver contratos ativos
if (empty($contratos)) {
    $semContrato = true;
    $documentos = [];
    $fotos = [];
} else {
    $semContrato = false;
    $contratoAtual = $contratos[0];
    $veiculoId = $contratoAtual['veiculo_id'];
    
    // Buscar documentos do veículo
    $documentos = DB()->fetchAll("
        SELECT * FROM veiculos_documentos 
        WHERE veiculo_id = ? 
        ORDER BY 
            CASE tipo
                WHEN 'crv' THEN 1
                WHEN 'crvl' THEN 2
                WHEN 'seguro' THEN 3
                WHEN 'licenciamento' THEN 4
                WHEN 'ipva' THEN 5
                ELSE 6
            END,
            created_at DESC
    ", [$veiculoId]);
    
    // Buscar fotos do veículo
    $fotos = DB()->fetchAll("
        SELECT * FROM veiculos_fotos 
        WHERE veiculo_id = ? 
        ORDER BY created_at DESC
    ", [$veiculoId]);
}

// Buscar documentos pessoais do cliente
$documentosPessoais = DB()->fetchAll("
    SELECT * FROM clientes_documentos 
    WHERE cliente_id = ? 
    ORDER BY 
        CASE tipo
            WHEN 'cnh' THEN 1
            WHEN 'rg' THEN 2
            WHEN 'cpf' THEN 3
            WHEN 'comprovante_residencia' THEN 4
            WHEN 'contrato_social' THEN 5
            ELSE 6
        END,
        created_at DESC
", [$cliente['id']]);

$tiposDocumento = [
    'crv' => ['nome' => 'CRV - Certificado de Registro de Veículo', 'icone' => 'fa-file-contract', 'cor' => '#1e40af'],
    'crvl' => ['nome' => 'CRLV - Certificado de Registro e Licenciamento', 'icone' => 'fa-id-card', 'cor' => '#166534'],
    'seguro' => ['nome' => 'Apólice de Seguro', 'icone' => 'fa-shield-alt', 'cor' => '#92400e'],
    'ipva' => ['nome' => 'Comprovante de IPVA', 'icone' => 'fa-receipt', 'cor' => '#991b1b'],
    'dpvat' => ['nome' => 'Comprovante de DPVAT', 'icone' => 'fa-file-alt', 'cor' => '#7c3aed'],
    'licenciamento' => ['nome' => 'Licenciamento', 'icone' => 'fa-stamp', 'cor' => '#047857'],
    'multa' => ['nome' => 'Multa/Infração', 'icone' => 'fa-exclamation-triangle', 'cor' => '#be123c'],
    'manutencao' => ['nome' => 'Ordem de Manutenção', 'icone' => 'fa-wrench', 'cor' => '#0369a1'],
    'vistoria' => ['nome' => 'Laudo de Vistoria', 'icone' => 'fa-clipboard-check', 'cor' => '#6d28d9'],
    'outro' => ['nome' => 'Outro Documento', 'icone' => 'fa-file', 'cor' => '#4b5563']
];

$tiposDocumentoPessoal = [
    'cnh' => ['nome' => 'CNH - Carteira Nacional de Habilitação', 'icone' => 'fa-id-card', 'cor' => '#1e40af'],
    'rg' => ['nome' => 'RG - Registro Geral', 'icone' => 'fa-address-card', 'cor' => '#166534'],
    'cpf' => ['nome' => 'CPF - Cadastro de Pessoa Física', 'icone' => 'fa-file-alt', 'cor' => '#92400e'],
    'comprovante_residencia' => ['nome' => 'Comprovante de Residência', 'icone' => 'fa-home', 'cor' => '#047857'],
    'contrato_social' => ['nome' => 'Contrato Social', 'icone' => 'fa-building', 'cor' => '#7c3aed'],
    'outro' => ['nome' => 'Outro Documento', 'icone' => 'fa-file', 'cor' => '#4b5563']
];

$statusClasses = [
    'pendente' => 'warning',
    'aprovado' => 'success',
    'rejeitado' => 'danger'
];

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - <?php echo SITE_NAME; ?></title>
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
            transition: all 0.2s;
        }
        .cliente-nav a:hover, .cliente-nav a.active {
            background: var(--primary);
            color: white;
        }
        .cliente-nav a i {
            margin-right: 8px;
        }
        
        /* Abas */
        .abas-container {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .abas-nav {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .aba-link {
            padding: 15px 25px;
            color: #6b7280;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .aba-link:hover {
            color: var(--primary);
            background: #f3f4f6;
        }
        .aba-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: white;
        }
        .aba-content {
            display: none;
            padding: 25px;
        }
        .aba-content.active {
            display: block;
        }
        
        /* Documentos */
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
            text-align: center;
        }
        .doc-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .doc-icon {
            font-size: 48px;
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
        .doc-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .doc-status.pendente { background: #fef3c7; color: #92400e; }
        .doc-status.aprovado { background: #d1fae5; color: #065f46; }
        .doc-status.rejeitado { background: #fee2e2; color: #991b1b; }
        
        /* Upload */
        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.2s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }
        .upload-area i {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 15px;
        }
        
        /* Fotos */
        .fotos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .foto-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .foto-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
        }
        .foto-data {
            padding: 10px;
            background: #f8f9fa;
            font-size: 11px;
            color: #666;
        }
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        /* Modal */
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
<body style="background: #f1f5f9;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        
        <!-- Header -->
        <div class="cliente-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-folder-open"></i> Documentos</h2>
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
            <a href="<?php echo BASE_URL; ?>/cliente/documentos.php" class="active"><i class="fas fa-folder-open"></i> Documentos</a>
            <a href="<?php echo BASE_URL; ?>/cliente/perfil.php"><i class="fas fa-user-cog"></i> Meu Perfil</a>
        </div>
        
        <?php if ($alerta): ?>
            <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                <span><?php echo $alerta['mensagem']; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Abas -->
        <div class="abas-container">
            <div class="abas-nav">
                <a href="?aba=veiculo" class="aba-link <?php echo $abaAtiva == 'veiculo' ? 'active' : ''; ?>">
                    <i class="fas fa-car"></i> Documentos do Veículo
                </a>
                <a href="?aba=pessoais" class="aba-link <?php echo $abaAtiva == 'pessoais' ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i> Documentos Pessoais
                    <?php if (count($documentosPessoais) > 0): ?>
                        <span class="badge badge-primary"><?php echo count($documentosPessoais); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Aba: Documentos do Veículo -->
            <div class="aba-content <?php echo $abaAtiva == 'veiculo' ? 'active' : ''; ?>">
                <?php if ($semContrato): ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <i class="fas fa-car" style="font-size: 64px; margin-bottom: 20px; color: #ddd;"></i>
                        <h3>Nenhum veículo locado</h3>
                        <p>Você não possui contratos ativos no momento.</p>
                    </div>
                <?php else: ?>
                    
                    <!-- Info do Veículo -->
                    <div class="info-box">
                        <h4><i class="fas fa-car"></i> Veículo em Locação</h4>
                        <p><strong><?php echo htmlspecialchars($contratoAtual['marca'] . ' ' . $contratoAtual['modelo'] . ' ' . $contratoAtual['ano_modelo']); ?></strong></p>
                        <p>Placa: <?php echo htmlspecialchars($contratoAtual['placa']); ?> | Cor: <?php echo htmlspecialchars($contratoAtual['cor']); ?></p>
                        <p style="font-size: 12px; color: #666;">Contrato: <?php echo htmlspecialchars($contratoAtual['numero_contrato']); ?></p>
                    </div>
                    
                    <!-- Documentos do Veículo -->
                    <h3 style="margin-bottom: 20px;"><i class="fas fa-folder-open"></i> Documentos do Veículo</h3>
                    <?php if (!empty($documentos)): ?>
                        <div class="doc-grid">
                            <?php foreach ($documentos as $doc): 
                                $ext = pathinfo($doc['arquivo'], PATHINFO_EXTENSION);
                                $tipoInfo = $tiposDocumento[$doc['tipo']] ?? $tiposDocumento['outro'];
                            ?>
                                <div class="doc-card">
                                    <div class="doc-icon" style="color: <?php echo $tipoInfo['cor']; ?>">
                                        <i class="fas <?php echo $tipoInfo['icone']; ?>"></i>
                                    </div>
                                    <div class="doc-tipo"><?php echo $tipoInfo['nome']; ?></div>
                                    <?php if ($doc['descricao']): ?>
                                        <div class="doc-nome"><?php echo htmlspecialchars($doc['descricao']); ?></div>
                                    <?php endif; ?>
                                    <div class="doc-data">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo formatarData($doc['created_at']); ?>
                                    </div>
                                    <a href="<?php echo BASE_URL . '/' . $doc['arquivo']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-primary"
                                       style="width: 100%;">
                                        <i class="fas fa-eye"></i> Visualizar Documento
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                            <h4>Nenhum documento disponível</h4>
                            <p>Os documentos do veículo serão disponibilizados pela locadora.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Fotos do Veículo -->
                    <?php if (!empty($fotos)): ?>
                    <h3 style="margin: 30px 0 20px;"><i class="fas fa-images"></i> Fotos do Veículo</h3>
                    <div class="fotos-grid">
                        <?php foreach ($fotos as $foto): ?>
                            <div class="foto-card">
                                <img src="<?php echo BASE_URL . '/' . $foto['arquivo']; ?>" 
                                     alt="Foto do veículo"
                                     onclick="abrirModal('<?php echo BASE_URL . '/' . $foto['arquivo']; ?>')">
                                <div class="foto-data">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php echo formatarData($foto['created_at']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
            
            <!-- Aba: Documentos Pessoais -->
            <div class="aba-content <?php echo $abaAtiva == 'pessoais' ? 'active' : ''; ?>">
                
                <!-- Formulário de Upload -->
                <div style="background: #f9fafb; border-radius: 8px; padding: 25px; margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px;"><i class="fas fa-cloud-upload-alt"></i> Enviar Novo Documento</h3>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label class="form-label">Tipo de Documento *</label>
                                <select name="tipo_documento" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="cnh">CNH - Carteira Nacional de Habilitação</option>
                                    <option value="rg">RG - Registro Geral</option>
                                    <option value="cpf">CPF - Cadastro de Pessoa Física</option>
                                    <option value="comprovante_residencia">Comprovante de Residência</option>
                                    <option value="contrato_social">Contrato Social (PJ)</option>
                                    <option value="outro">Outro Documento</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Descrição (opcional)</label>
                                <input type="text" name="descricao" class="form-control" placeholder="Ex: CNH frente e verso">
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label class="form-label">Arquivo *</label>
                            <input type="file" name="documento" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            <small style="color: #6b7280;">Formatos aceitos: PDF, JPG, PNG. Tamanho máximo: 10MB</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Enviar Documento
                        </button>
                    </form>
                </div>
                
                <!-- Lista de Documentos Pessoais -->
                <h3 style="margin-bottom: 20px;"><i class="fas fa-folder"></i> Meus Documentos Enviados</h3>
                
                <?php if (!empty($documentosPessoais)): ?>
                    <div class="doc-grid">
                        <?php foreach ($documentosPessoais as $doc): 
                            $tipoInfo = $tiposDocumentoPessoal[$doc['tipo']] ?? $tiposDocumentoPessoal['outro'];
                            $statusClass = $statusClasses[$doc['status']] ?? 'warning';
                        ?>
                            <div class="doc-card">
                                <div class="doc-icon" style="color: <?php echo $tipoInfo['cor']; ?>">
                                    <i class="fas <?php echo $tipoInfo['icone']; ?>"></i>
                                </div>
                                <div class="doc-tipo"><?php echo $tipoInfo['nome']; ?></div>
                                <div class="doc-status <?php echo $statusClass; ?>">
                                    <i class="fas fa-<?php echo $doc['status'] == 'aprovado' ? 'check-circle' : ($doc['status'] == 'rejeitado' ? 'times-circle' : 'clock'); ?>"></i>
                                    <?php echo ucfirst($doc['status']); ?>
                                </div>
                                <?php if ($doc['descricao']): ?>
                                    <div class="doc-nome"><?php echo htmlspecialchars($doc['descricao']); ?></div>
                                <?php endif; ?>
                                <?php if ($doc['observacao_admin']): ?>
                                    <div style="background: #fee2e2; padding: 8px; border-radius: 4px; font-size: 12px; color: #991b1b; margin-bottom: 10px;">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($doc['observacao_admin']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="doc-data">
                                    <i class="fas fa-calendar-alt"></i> 
                                    Enviado em: <?php echo formatarData($doc['created_at']); ?>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="<?php echo BASE_URL . '/' . $doc['arquivo']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-primary"
                                       style="flex: 1;">
                                        <i class="fas fa-eye"></i> Visualizar
                                    </a>
                                    <?php if ($doc['status'] != 'aprovado'): ?>
                                        <a href="?aba=pessoais&excluir=<?php echo $doc['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Tem certeza que deseja excluir este documento?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <i class="fas fa-folder-open" style="font-size: 64px; margin-bottom: 20px; color: #ddd;"></i>
                        <h3>Nenhum documento enviado</h3>
                        <p>Você ainda não enviou nenhum documento pessoal.</p>
                        <p style="font-size: 14px; color: #9ca3af;">Use o formulário acima para enviar seus documentos.</p>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <!-- Modal para visualização de fotos -->
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
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });
    </script>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
