<?php
/**
 * TI Stock - Conexão com o Banco de Dados
 *
 * Conexão via PDO com MySQL.
 * Utiliza consultas preparadas para prevenir SQL Injection.
 */

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);

} catch (PDOException $e) {
    // Registra o erro sem expor detalhes sensíveis ao usuário
    error_log('[TI Stock] Erro PDO: ' . $e->getMessage());
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    die('
    <div style="font-family:sans-serif;padding:30px;max-width:620px;margin:60px auto;
                background:#fff3cd;border:1px solid #ffc107;border-radius:8px;">
        <h3 style="color:#856404;">&#9888; Erro de Conexão com o Banco de Dados</h3>
        <p>Não foi possível conectar ao banco de dados. Verifique:</p>
        <ul>
            <li>As credenciais em <code>includes/config.php</code></li>
            <li>Se o serviço MySQL está em execução</li>
            <li>Se o banco de dados <strong>' . htmlspecialchars(DB_NAME) . '</strong> foi criado</li>
        </ul>
        <a href="' . $baseUrl . '/install.php" style="color:#0d6efd;">Acessar instalador</a>
    </div>');
}
