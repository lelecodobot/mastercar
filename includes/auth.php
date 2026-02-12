<?php
/**
 * Master Car - Funções de Autenticação
 */

require_once __DIR__ . '/database.php';

session_start();

/**
 * Verifica se usuário está logado
 */
function estaLogado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica se cliente está logado
 */
function clienteLogado() {
    return isset($_SESSION['cliente_id']) && !empty($_SESSION['cliente_id']);
}

/**
 * Obtém dados do usuário logado
 */
function usuarioAtual() {
    if (!estaLogado()) return null;
    
    return [
        'id' => $_SESSION['usuario_id'],
        'nome' => $_SESSION['usuario_nome'],
        'email' => $_SESSION['usuario_email'],
        'tipo' => $_SESSION['usuario_tipo']
    ];
}

/**
 * Obtém dados do cliente logado
 */
function clienteAtual() {
    if (!clienteLogado()) return null;
    
    $cliente = DB()->fetch(
        "SELECT * FROM clientes WHERE id = ?",
        [$_SESSION['cliente_id']]
    );
    
    return $cliente;
}

/**
 * Realiza login de administrador
 */
function loginAdmin($email, $senha) {
    $usuario = DB()->fetch(
        "SELECT * FROM usuarios WHERE email = ? AND ativo = 1",
        [$email]
    );
    
    if (!$usuario) {
        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
    }
    
    if (!password_verify($senha, $usuario['senha'])) {
        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
    }
    
    // Registra sessão
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['login_time'] = time();
    
    // Atualiza último acesso
    DB()->update('usuarios', 
        ['ultimo_acesso' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => $usuario['id']]
    );
    
    // Registra log
    registrarLog(null, null, null, 'login', 'Login realizado com sucesso', null, 'admin');
    
    return ['sucesso' => true, 'mensagem' => 'Login realizado com sucesso!'];
}

/**
 * Realiza login de cliente
 */
function loginCliente($email, $senha) {
    $cliente = DB()->fetch(
        "SELECT * FROM clientes WHERE email = ? AND status != 'cancelado'",
        [$email]
    );
    
    if (!$cliente) {
        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
    }
    
    if (empty($cliente['senha']) || !password_verify($senha, $cliente['senha'])) {
        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
    }
    
    // Verifica se está bloqueado
    if ($cliente['status'] == 'bloqueado') {
        if ($cliente['bloqueado_ate'] && strtotime($cliente['bloqueado_ate']) > time()) {
            return ['sucesso' => false, 'mensagem' => 'Sua conta está bloqueada até ' . formatarData($cliente['bloqueado_ate']) . '.'];
        }
    }
    
    // Registra sessão
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome'];
    $_SESSION['cliente_email'] = $cliente['email'];
    $_SESSION['login_time'] = time();
    
    // Registra log
    registrarLog(null, null, $cliente['id'], 'login', 'Login realizado com sucesso', null, 'cliente');
    
    return ['sucesso' => true, 'mensagem' => 'Login realizado com sucesso!'];
}

/**
 * Realiza logout
 */
function logout() {
    // Registra log antes de destruir sessão
    if (estaLogado()) {
        registrarLog(null, null, null, 'logout', 'Logout realizado', null, 'admin');
    } elseif (clienteLogado()) {
        registrarLog(null, null, $_SESSION['cliente_id'], 'logout', 'Logout realizado', null, 'cliente');
    }
    
    // Limpa todas as variáveis de sessão
    $_SESSION = [];
    
    // Destrói o cookie de sessão
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destrói a sessão
    session_destroy();
}

/**
 * Verifica permissão de acesso
 */
function verificarPermissao($tiposPermitidos = []) {
    if (!estaLogado()) {
        redirecionar('/admin/login.php');
    }
    
    if (!empty($tiposPermitidos) && !in_array($_SESSION['usuario_tipo'], $tiposPermitidos)) {
        mostrarAlerta('Você não tem permissão para acessar esta página.', 'danger');
        redirecionar('/admin/');
    }
}

/**
 * Verifica se cliente tem acesso
 */
function verificarAcessoCliente() {
    if (!clienteLogado()) {
        redirecionar('/cliente/login.php');
    }
    
    $cliente = clienteAtual();
    if ($cliente['status'] == 'bloqueado') {
        if ($cliente['bloqueado_ate'] && strtotime($cliente['bloqueado_ate']) > time()) {
            logout();
            mostrarAlerta('Sua conta está bloqueada.', 'danger');
            redirecionar('/cliente/login.php');
        }
    }
}

/**
 * Protege página de admin
 */
function protegerAdmin($tipos = []) {
    verificarPermissao($tipos);
}

/**
 * Protege página de cliente
 */
function protegerCliente() {
    verificarAcessoCliente();
}

/**
 * Gera hash de senha segura
 */
function hashSenha($senha) {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica força da senha
 */
function verificarForcaSenha($senha) {
    $forca = 0;
    $erros = [];
    
    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    } else {
        $forca++;
    }
    
    if (!preg_match('/[A-Z]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos uma letra maiúscula.';
    } else {
        $forca++;
    }
    
    if (!preg_match('/[a-z]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos uma letra minúscula.';
    } else {
        $forca++;
    }
    
    if (!preg_match('/[0-9]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos um número.';
    } else {
        $forca++;
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos um caractere especial.';
    } else {
        $forca++;
    }
    
    return [
        'valido' => empty($erros),
        'forca' => $forca,
        'erros' => $erros
    ];
}

/**
 * Registra log do sistema
 */
function registrarLog($faturaId = null, $contratoId = null, $clienteId = null, $tipo = 'sistema', $descricao = '', $dadosJson = null, $usuarioTipo = 'sistema') {
    try {
        $data = [
            'fatura_id' => $faturaId,
            'contrato_id' => $contratoId,
            'cliente_id' => $clienteId,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'dados_json' => $dadosJson ? json_encode($dadosJson) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'usuario_id' => estaLogado() ? $_SESSION['usuario_id'] : null,
            'usuario_tipo' => $usuarioTipo
        ];
        
        DB()->insert('cobranca_logs', $data);
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Renova sessão se necessário
 */
function renovarSessao() {
    if (isset($_SESSION['login_time'])) {
        // Renova a cada 30 minutos de atividade
        if (time() - $_SESSION['login_time'] > 1800) {
            $_SESSION['login_time'] = time();
        }
    }
}

// Renova sessão automaticamente
renovarSessao();
