<?php
/**
 * Master Car - Cadastro de Cliente
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

protegerAdmin();

$erros = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validação
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf_cnpj = limparCpfCnpj($_POST['cpf_cnpj'] ?? '');
    $telefone = limparCpfCnpj($_POST['telefone'] ?? '');
    $celular = limparCpfCnpj($_POST['celular'] ?? '');
    
    if (empty($nome)) {
        $erros[] = 'O nome é obrigatório.';
    }
    
    if (empty($cpf_cnpj)) {
        $erros[] = 'O CPF/CNPJ é obrigatório.';
    } elseif (strlen($cpf_cnpj) != 11 && strlen($cpf_cnpj) != 14) {
        $erros[] = 'CPF/CNPJ inválido.';
    }
    
    // Verifica se CPF/CNPJ já existe
    $existente = DB()->fetch("SELECT id FROM clientes WHERE cpf_cnpj = ?", [$cpf_cnpj]);
    if ($existente) {
        $erros[] = 'Já existe um cliente cadastrado com este CPF/CNPJ.';
    }
    
    if (empty($erros)) {
        try {
            $data = [
                'tipo_pessoa' => strlen($cpf_cnpj) == 11 ? 'F' : 'J',
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'celular' => $celular,
                'cpf_cnpj' => $cpf_cnpj,
                'rg_ie' => $_POST['rg_ie'] ?? '',
                'data_nascimento' => $_POST['data_nascimento'] ?: null,
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
                'dias_tolerancia' => $_POST['dias_tolerancia'] ?: DIAS_TOLERANCIA_PADRAO,
                'observacoes' => $_POST['observacoes'] ?? ''
            ];
            
            // Gera senha aleatória se informado email
            if (!empty($email)) {
                $senhaTemp = substr(md5(uniqid()), 0, 8);
                $data['senha'] = hashSenha($senhaTemp);
            }
            
            $clienteId = DB()->insert('clientes', $data);
            
            // Registra log
            registrarLog(null, null, $clienteId, 'sistema', 'Cliente cadastrado');
            
            mostrarAlerta('Cliente cadastrado com sucesso!', 'success');
            redirecionar('/admin/clientes/');
            
        } catch (Exception $e) {
            $erros[] = 'Erro ao cadastrar cliente: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Cliente - <?php echo SITE_NAME; ?></title>
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
                    <h1 class="page-title">Novo Cliente</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/admin/">Home</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <a href="<?php echo BASE_URL; ?>/admin/clientes/">Clientes</a>
                        <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        <span>Novo</span>
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
                                <label class="form-label required">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo $_POST['nome'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">CPF/CNPJ</label>
                                <input type="text" name="cpf_cnpj" class="form-control" data-mask="cpf_cnpj" value="<?php echo $_POST['cpf_cnpj'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">RG/IE</label>
                                <input type="text" name="rg_ie" class="form-control" value="<?php echo $_POST['rg_ie'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Data de Nascimento</label>
                                <input type="date" name="data_nascimento" class="form-control" value="<?php echo $_POST['data_nascimento'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dias de Tolerância</label>
                                <input type="number" name="dias_tolerancia" class="form-control" value="<?php echo $_POST['dias_tolerancia'] ?? DIAS_TOLERANCIA_PADRAO; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-address-card"></i> Contato</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $_POST['email'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" data-mask="telefone" value="<?php echo $_POST['telefone'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Celular</label>
                                <input type="text" name="celular" class="form-control" data-mask="telefone" value="<?php echo $_POST['celular'] ?? ''; ?>">
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
                                <input type="text" name="cep" class="form-control" data-mask="cep" data-cep value="<?php echo $_POST['cep'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label">Endereço</label>
                                <input type="text" name="endereco" class="form-control" value="<?php echo $_POST['endereco'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" value="<?php echo $_POST['numero'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Complemento</label>
                                <input type="text" name="complemento" class="form-control" value="<?php echo $_POST['complemento'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" value="<?php echo $_POST['bairro'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="cidade" class="form-control" value="<?php echo $_POST['cidade'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="width: 80px;">
                                <label class="form-label">UF</label>
                                <select name="estado" class="form-control">
                                    <option value="">--</option>
                                    <?php
                                    $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                    foreach ($estados as $uf) {
                                        $selected = ($_POST['estado'] ?? '') == $uf ? 'selected' : '';
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
                        <h3 class="card-title"><i class="fas fa-id-card"></i> CNH</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Número da CNH</label>
                                <input type="text" name="cnh_numero" class="form-control" value="<?php echo $_POST['cnh_numero'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Validade</label>
                                <input type="date" name="cnh_validade" class="form-control" value="<?php echo $_POST['cnh_validade'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="width: 100px;">
                                <label class="form-label">Categoria</label>
                                <input type="text" name="cnh_categoria" class="form-control" maxlength="5" value="<?php echo $_POST['cnh_categoria'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note"></i> Observações</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <textarea name="observacoes" class="form-control" rows="3"><?php echo $_POST['observacoes'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="<?php echo BASE_URL; ?>/admin/clientes/" class="btn btn-light">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
