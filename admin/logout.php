<?php
/**
 * Master Car - Logout Administrativo
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

logout();
redirecionar('/admin/login.php');
