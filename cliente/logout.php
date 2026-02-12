<?php
/**
 * Master Car - Logout do Cliente
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

logout();
redirecionar('/cliente/login.php');
