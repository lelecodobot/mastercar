<?php
/**
 * Master Car - Documentos Pessoais do Cliente (Admin)
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$clienteId = $_GET['cliente_id'] ?? 0;

if (!$clienteId) {
    definirAlerta('danger', 'Cliente não informado.');
    header('Location: ' . BASE_URL . '/admin/clientes/');
    exit;
}

$cliente = DB()->fetch("SELECT * FROM clientes WHERE id = ?", [$clienteId]);

if (!$cliente) {
    definirAlerta('danger', 'Cliente não encontrado.');
    header('Location: ' . BASE_URL . '/admin/clientes/');
    exit;
}

// Processar aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $docId = $_POST['documento_id'] ?? 0;
    $acao = $_POST['acao'] ?? '';
    $observacao = $_POST['observacao'] ?? '';
    
    if ($docId && in_array($acao, ['aprovar', 'rejeitar'])) {
        $status = $acao == 'aprovar' ? 'aprovado' : 'rejeitado';
        
        DB()->update('clientes_documentos', [
            'status' => $status,
            'observacao_admin' => $observacao
        ], 'id = :id', ['id' => $docId]);
        
        $msg = $acao == 'aprovar' ? 'Documento aprovado com sucesso!' : 'Documento rejeitado.';
        definirAlerta('success', $msg);
        
        header('Location: ' . BASE_URL . '/admin/clientes/documentos.php?cliente_id=' . $clienteId);
        exit;
    }
}

// Buscar documentos do cliente
$documentos = DB()->fetchAll("
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
", [$clienteId]);

$tiposDocumento = [
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
    <title>Documentos do Cliente - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .doc-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            transition: box-shadow 0.2s;
        }
        .doc-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .doc-preview {
            height: 200px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .doc-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .doc-preview i {
            font-size: 64px;
            color: #9ca3af;
        }
        .doc-info {
            padding: 15px;
        }
        .doc-tipo {
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .doc-nome {
            font-weight: 600;
            margin-bottom: 10px;
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
        .doc-data {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 15px;
        }
        .doc-actions {
            display: flex;
            gap: 8px;
        }
        .observacao-box {
            background: #fee2e2;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #991b1b;
            margin-top: 10px;
        }
        .cliente-info {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .cliente-info h3 {
            margin: 0 0 10px 0;
        }
        .filtros-status {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filtro-btn {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            background: #f3f4f6;
            color: #6b7280;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filtro-btn:hover, .filtro-btn.active {
            background: var(--primary);
            color: white;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.5);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            z-index: 10;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-body img {
            max-width: 100%;
            max-height: 80vh;
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
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">Documentos do Cliente</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/clientes/">Clientes</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Documentos</span>
                    </div>
                </div>
            </div>
            
            <!-- Info do Cliente -->
            <div class="cliente-info">
                <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($cliente['nome']); ?></h3>
                <p><i class="fas fa-id-card"></i> <?php echo formatarCpfCnpj($cliente['cpf_cnpj']); ?></p>
                <?php if ($cliente['email']): ?>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($cliente['email']); ?></p>
                <?php endif; ?>
                <?php if ($cliente['telefone']): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Resumo -->
            <div class="card" style="margin-bottom: 25px;">
                <div class="card-body">
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: var(--primary);">
                                <?php echo count($documentos); ?>
                            </div>
                            <div style="font-size: 12px; color: #6b7280;">Total de Documentos</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">
                                <?php echo count(array_filter($documentos, fn($d) => $d['status'] == 'pendente')); ?>
                            </div>
                            <div style="font-size: 12px; color: #6b7280;">Pendentes</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #10b981;">
                                <?php echo count(array_filter($documentos, fn($d) => $d['status'] == 'aprovado')); ?>
                            </div>
                            <div style="font-size: 12px; color: #6b7280;">Aprovados</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #ef4444;">
                                <?php echo count(array_filter($documentos, fn($d) => $d['status'] == 'rejeitado')); ?>
                            </div>
                            <div style="font-size: 12px; color: #6b7280;">Rejeitados</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Documentos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder-open"></i> Documentos Enviados</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($documentos)): ?>
                        <div style="text-align: center; padding: 60px; color: #666;">
                            <i class="fas fa-folder-open" style="font-size: 64px; margin-bottom: 20px; color: #ddd;"></i>
                            <h3>Nenhum documento enviado</h3>
                            <p>O cliente ainda não enviou nenhum documento pessoal.</p>
                        </div>
                    <?php else: ?>
                        <div class="doc-grid">
                            <?php foreach ($documentos as $doc): 
                                $tipoInfo = $tiposDocumento[$doc['tipo']] ?? $tiposDocumento['outro'];
                                $statusClass = $statusClasses[$doc['status']] ?? 'warning';
                                $ext = strtolower(pathinfo($doc['arquivo'], PATHINFO_EXTENSION));
                                $isImagem = in_array($ext, ['jpg', 'jpeg', 'png']);
                            ?>
                                <div class="doc-card" data-status="<?php echo $doc['status']; ?>">
                                    <div class="doc-preview">
                                        <?php if ($isImagem): ?>
                                            <img src="<?php echo BASE_URL . '/' . $doc['arquivo']; ?>" 
                                                 alt="Documento"
                                                 onclick="abrirModal('<?php echo BASE_URL . '/' . $doc['arquivo']; ?>')"
                                                 style="cursor: pointer;">
                                        <?php else: ?>
                                            <i class="fas fa-file-pdf"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="doc-info">
                                        <div class="doc-tipo"><?php echo $tipoInfo['nome']; ?></div>
                                        <div class="doc-status <?php echo $statusClass; ?>">
                                            <i class="fas fa-<?php echo $doc['status'] == 'aprovado' ? 'check-circle' : ($doc['status'] == 'rejeitado' ? 'times-circle' : 'clock'); ?>"></i>
                                            <?php echo ucfirst($doc['status']); ?>
                                        </div>
                                        <?php if ($doc['descricao']): ?>
                                            <div class="doc-nome"><?php echo htmlspecialchars($doc['descricao']); ?></div>
                                        <?php endif; ?>
                                        <div class="doc-data">
                                            <i class="fas fa-calendar-alt"></i> 
                                            Enviado em: <?php echo formatarData($doc['created_at']); ?>
                                        </div>
                                        
                                        <?php if ($doc['observacao_admin']): ?>
                                            <div class="observacao-box">
                                                <i class="fas fa-comment"></i> 
                                                <?php echo htmlspecialchars($doc['observacao_admin']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="doc-actions">
                                            <a href="<?php echo BASE_URL . '/' . $doc['arquivo']; ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Visualizar
                                            </a>
                                            
                                            <?php if ($doc['status'] == 'pendente'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success"
                                                        onclick="abrirModalAcao(<?php echo $doc['id']; ?>, 'aprovar')">
                                                    <i class="fas fa-check"></i> Aprovar
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="abrirModalAcao(<?php echo $doc['id']; ?>, 'rejeitar')">
                                                    <i class="fas fa-times"></i> Rejeitar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Ação (Aprovar/Rejeitar) -->
    <div id="modalAcao" class="modal-overlay">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-close" onclick="fecharModalAcao()">&times;</div>
            <div class="modal-body">
                <h3 id="modalTitulo" style="margin-bottom: 20px;"></h3>
                <form method="POST" action="">
                    <input type="hidden" name="documento_id" id="docId">
                    <input type="hidden" name="acao" id="acaoTipo">
                    
                    <div style="margin-bottom: 20px;">
                        <label class="form-label">Observação (opcional)</label>
                        <textarea name="observacao" class="form-control" rows="3" 
                                  placeholder="Adicione uma observação sobre esta ação..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn btn-light" onclick="fecharModalAcao()">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnConfirmar">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Visualização -->
    <div id="modalVisualizar" class="modal-overlay" onclick="fecharModalVisualizar()">
        <span class="modal-close">&times;</span>
        <img id="imgVisualizar" src="" alt="Documento">
    </div>
    
    <script>
        function abrirModalAcao(docId, acao) {
            document.getElementById('docId').value = docId;
            document.getElementById('acaoTipo').value = acao;
            
            const titulo = acao == 'aprovar' ? '<i class="fas fa-check-circle" style="color: #10b981;"></i> Aprovar Documento' 
                                             : '<i class="fas fa-times-circle" style="color: #ef4444;"></i> Rejeitar Documento';
            document.getElementById('modalTitulo').innerHTML = titulo;
            
            const btnClass = acao == 'aprovar' ? 'btn-success' : 'btn-danger';
            const btnText = acao == 'aprovar' ? '<i class="fas fa-check"></i> Aprovar' : '<i class="fas fa-times"></i> Rejeitar';
            const btnConfirmar = document.getElementById('btnConfirmar');
            btnConfirmar.className = 'btn ' + btnClass;
            btnConfirmar.innerHTML = btnText;
            
            document.getElementById('modalAcao').classList.add('active');
        }
        
        function fecharModalAcao() {
            document.getElementById('modalAcao').classList.remove('active');
        }
        
        function abrirModal(src) {
            document.getElementById('imgVisualizar').src = src;
            document.getElementById('modalVisualizar').classList.add('active');
        }
        
        function fecharModalVisualizar() {
            document.getElementById('modalVisualizar').classList.remove('active');
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalAcao();
                fecharModalVisualizar();
            }
        });
    </script>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
