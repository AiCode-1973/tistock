<?php
/**
 * TI Stock - Logout
 * Encerra a sessão e redireciona para o login.
 */

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/init.php';

registrarLog($pdo, 'logout', 'Sessão encerrada.');
logoutUsuario();
header('Location: ' . BASE_URL . '/login.php');
exit;
