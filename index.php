<?php
/**
 * Master Car - Página Inicial
 * Redireciona para o instalador ou área administrativa
 */

// Verifica se o sistema está instalado
if (!file_exists('includes/config.php')) {
    header('Location: install/');
    exit;
}

require_once 'includes/config.php';

// Redireciona para o painel administrativo
header('Location: admin/login.php');
exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=admin/login.php">
    <title>Redirecionando...</title>
</head>
<body>
    <p>Redirecionando para o sistema...</p>
    <p>Se não for redirecionado, <a href="admin/login.php">clique aqui</a>.</p>
</body>
</html>
