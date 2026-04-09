<?php
/**
 * TI Stock - Arquivo de Inicialização
 *
 * Incluído no topo de todas as páginas protegidas.
 * Define: sessão, configurações, conexão, auth e funções.
 */

// Configurações de segurança de sessão (devem ser definidas antes do session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// ROOT_PATH deve ser definido pelo arquivo que inclui este
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/functions.php';
