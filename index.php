<?php
/**
 * TI Stock - Página Inicial
 * Redireciona para o dashboard se autenticado, caso contrário para o login.
 */

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/init.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
