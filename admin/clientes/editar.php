<?php
/**
 * Master Car - Editar Cliente
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

$erros = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($nome)) {
        $erros[] = 'O nome é obrigatório.';
    }
    
    if (empty($erros)) {
        try {
            $data = [
                'nome' => $nome,
                'email' => $email,
                'telefone' => limparCpfCnpj($_POST['telefone'] ?? ''),
                'celular' => limparCpfCnpj($_POST['celular'] ?? ''),
                'rg_ie' => $_POST['rg_ie'] ?? '',
                'cep' => limparCpfCnpj($_POST['cep'] ?? ''),
                'endereco' => $_POST['endereco'] ?? '',
                'numero' => $_POST['numero'] ?? '',
                'complemento' => $_POST['complemento'] ?? '',
                'bairro' => $_POST['bairro'] ?? '',
                'cidade' => $_POST['cidade'] ?? '',
                'estado' => $_POST['estado'] ?? '',
                'cnh_numero' => $_POST['cnh_numero'] ?? '',
                'cnh_validade' => $_POST['cnh_validade'] ?: null,
                'cnh_categoria' => $_POST['cnh_categoria'] ?? '',
                'dias_tolerancia' => $_POST['dias_tolerancia'] ?? DIAS_TOLERANCIA_PADRAO,
                'status' => $_POST['status'] ?? $cliente['status'],
                'observacoes' => $_POST['observacoes'] ?? ''
            ];
            
            // Atualiza senha se informada
            if (!empty($_POST['nova_senha'])) {
                $data['senha'] = password_hash($_POST['nova_senha'], PASSWORD_BCRYPT, ['cost' => 12]);
            }
            
            DB()->update('clientes', $data, 'id = :id', ['id' => $id]);
            
            mostrarAlerta('Cliente atualizado com sucesso!', 'success');
            redirecionar('/admin/clientes/ver.php?id=' . $id);
            
        } catch (Exception $e) {
            $erros[] = 'Erro ao atualizar cliente: ' . $e->getMessage();
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
    <title>Editar Cliente - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Editar Cliente</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/clientes/">Clientes</a>
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
                        <h3 class="card-title"><i class="fas fa-user"></i> Dados Pessoais</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo $cliente['nome']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="ativo" <?php echo $cliente['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="bloqueado" <?php echo $cliente['status'] == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                                    <option value="inadimplente" <?php echo $cliente['status'] == 'inadimplente' ? 'selected' : ''; ?>>Inadimplente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $cliente['email']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" data-mask="telefone" value="<?php echo $cliente['telefone']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Celular</label>
                                <input type="text" name="celular" class="form-control" data-mask="telefone" value="<?php echo $cliente['celular']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="width: 150px;">
                                <label class="form-label">CEP</label>
                                <input type="text" name="cep" class="form-control" data-mask="cep" data-cep value="<?php echo $cliente['cep']; ?>">
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Endereço</label>
                                <input type="text" name="endereco" class="form-control" value="<?php echo $cliente['endereco']; ?>">
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" value="<?php echo $cliente['numero']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Complemento</label>
                                <input type="text" name="complemento" class="form-control" value="<?php echo $cliente['complemento']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" value="<?php echo $cliente['bairro']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="cidade" class="form-control" value="<?php echo $cliente['cidade']; ?>">
                            </div>
                            <div class="form-group" style="width: 80px;">
                                <label class="form-label">UF</label>
                                <select name="estado" class="form-control">
                                    <?php
                                    $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                    foreach ($estados as $uf) {
                                        $selected = $cliente['estado'] == $uf ? 'selected' : '';
                                        echo "<option value='{$uf}' {$selected}>{$uf}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lock"></i> Nova Senha (opcional)</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" name="nova_senha" class="form-control" minlength="6">
                            <div class="form-hint">Deixe em branco para manter a senha atual</div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="<?php echo BASE_URL; ?>/admin/clientes/ver.php?id=<?php echo $id; ?>" class="btn btn-light">
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
