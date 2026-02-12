<?php
/**
 * Master Car - Perfil do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

protegerCliente();

$cliente = clienteAtual();
$erros = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    
    if (!empty($novaSenha)) {
        if (empty($senhaAtual)) {
            $erros[] = 'Informe a senha atual.';
        } elseif (!password_verify($senhaAtual, $cliente['senha'])) {
            $erros[] = 'Senha atual incorreta.';
        } elseif ($novaSenha != $confirmarSenha) {
            $erros[] = 'As senhas não conferem.';
        } elseif (strlen($novaSenha) < 6) {
            $erros[] = 'A nova senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                DB()->update('clientes', [
                    'senha' => password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12])
                ], 'id = :id', ['id' => $cliente['id']]);
                
                $sucesso = true;
                mostrarAlerta('Senha alterada com sucesso!', 'success');
                redirecionar('/cliente/perfil.php');
            } catch (Exception $e) {
                $erros[] = 'Erro ao alterar senha: ' . $e->getMessage();
            }
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
    <title>Meu Perfil - <?php echo SITE_NAME; ?></title>
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
        }
        .cliente-nav a:hover {
            background: var(--primary);
            color: white;
        }
        .info-item {
            padding: 15px;
            background: var(--light);
            border-radius: var(--border-radius);
            margin-bottom: 10px;
        }
        .info-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            font-size: 16px;
        }
    </style>
</head>
<body style="background: #f1f5f9;">
    <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
        
        <div class="cliente-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-user-cog"></i> Meu Perfil</h2>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>/cliente/" style="color: white; opacity: 0.9;">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
        
        <div class="cliente-nav">
            <a href="<?php echo BASE_URL; ?>/cliente/"><i class="fas fa-home"></i> Início</a>
            <a href="<?php echo BASE_URL; ?>/cliente/faturas.php"><i class="fas fa-receipt"></i> Minhas Faturas</a>
            <a href="<?php echo BASE_URL; ?>/cliente/contratos.php"><i class="fas fa-file-contract"></i> Meus Contratos</a>
            <a href="<?php echo BASE_URL; ?>/cliente/documentos.php"><i class="fas fa-folder-open"></i> Documentos</a>
        </div>
        
        <?php if ($alerta): ?>
            <div class="alert alert-<?php echo $alerta['tipo']; ?>">
                <i class="fas fa-<?php echo $alerta['tipo'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                <span><?php echo $alerta['mensagem']; ?></span>
            </div>
        <?php endif; ?>
        
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
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Informações Pessoais -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Informações Pessoais</h3>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value"><?php echo $cliente['nome']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">CPF/CNPJ</div>
                        <div class="info-value"><?php echo formatarCpfCnpj($cliente['cpf_cnpj']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">E-mail</div>
                        <div class="info-value"><?php echo $cliente['email'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Telefone</div>
                        <div class="info-value"><?php echo $cliente['telefone'] ? formatarTelefone($cliente['telefone']) : 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Celular</div>
                        <div class="info-value"><?php echo $cliente['celular'] ? formatarTelefone($cliente['celular']) : 'Não informado'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Alterar Senha -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lock"></i> Alterar Senha</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Senha Atual</label>
                            <input type="password" name="senha_atual" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" name="nova_senha" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmar Nova Senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Alterar Senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Endereço -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Endereço</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="info-item">
                        <div class="info-label">CEP</div>
                        <div class="info-value"><?php echo $cliente['cep'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Endereço</div>
                        <div class="info-value"><?php echo $cliente['endereco'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Número</div>
                        <div class="info-value"><?php echo $cliente['numero'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Complemento</div>
                        <div class="info-value"><?php echo $cliente['complemento'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Bairro</div>
                        <div class="info-value"><?php echo $cliente['bairro'] ?: 'Não informado'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cidade/UF</div>
                        <div class="info-value"><?php echo ($cliente['cidade'] && $cliente['estado']) ? $cliente['cidade'] . '/' . $cliente['estado'] : 'Não informado'; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
