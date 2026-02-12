<?php
/**
 * Master Car - Editar Contrato
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$id = $_GET['id'] ?? 0;

$contrato = DB()->fetch("SELECT * FROM contratos_semanal WHERE id = ?", [$id]);
if (!$contrato) {
    mostrarAlerta('Contrato não encontrado.', 'danger');
    redirecionar('/admin/contratos/');
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $valorSemanal = str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_semanal'] ?? '0');
    
    try {
        DB()->update('contratos_semanal', [
            'valor_semanal' => $valorSemanal,
            'valor_caucao' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_caucao'] ?? '0'),
            'valor_multa_diaria' => str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor_multa_diaria'] ?? '0'),
            'dias_tolerancia' => $_POST['dias_tolerancia'] ?? DIAS_TOLERANCIA_PADRAO,
            'status' => $_POST['status'] ?? $contrato['status'],
            'observacoes' => $_POST['observacoes'] ?? ''
        ], 'id = :id', ['id' => $id]);
        
        mostrarAlerta('Contrato atualizado com sucesso!', 'success');
        redirecionar('/admin/contratos/ver.php?id=' . $id);
        
    } catch (Exception $e) {
        $erros[] = 'Erro ao atualizar contrato: ' . $e->getMessage();
    }
}

$alerta = obterAlerta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contrato - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Editar Contrato</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/contratos/">Contratos</a>
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
                        <h3 class="card-title"><i class="fas fa-file-contract"></i> Dados do Contrato <?php echo $contrato['numero_contrato']; ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Valor Semanal</label>
                                <input type="text" name="valor_semanal" class="form-control" data-mask="moeda" 
                                    value="<?php echo formatarMoeda($contrato['valor_semanal']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Valor Caução</label>
                                <input type="text" name="valor_caucao" class="form-control" data-mask="moeda" 
                                    value="<?php echo formatarMoeda($contrato['valor_caucao']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Multa Diária</label>
                                <input type="text" name="valor_multa_diaria" class="form-control" data-mask="moeda" 
                                    value="<?php echo formatarMoeda($contrato['valor_multa_diaria']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Dias de Tolerância</label>
                                <input type="number" name="dias_tolerancia" class="form-control" 
                                    value="<?php echo $contrato['dias_tolerancia']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="ativo" <?php echo $contrato['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="suspenso" <?php echo $contrato['status'] == 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                                    <option value="encerrado" <?php echo $contrato['status'] == 'encerrado' ? 'selected' : ''; ?>>Encerrado</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3"><?php echo $contrato['observacoes']; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="<?php echo BASE_URL; ?>/admin/contratos/ver.php?id=<?php echo $id; ?>" class="btn btn-light">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
