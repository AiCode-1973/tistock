<?php
/**
 * TI Stock - Instalador
 *
 * Cria o banco de dados, as tabelas e o usuário administrador inicial.
 * REMOVA ou RESTRINJA o acesso a este arquivo após a instalação.
 */

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/config.php';

$etapa    = 1;
$sucesso  = false;
$erros    = [];
$mensagem = '';

// ----------------------------------------
// Etapa 2: Processamento da instalação
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instalar'])) {
    $nomeAdmin  = trim($_POST['nome_admin'] ?? '');
    $emailAdmin = trim($_POST['email_admin'] ?? '');
    $senhaAdmin = $_POST['senha_admin'] ?? '';
    $confirma   = $_POST['confirma_senha'] ?? '';

    // Validações
    if (empty($nomeAdmin))                              $erros[] = 'O nome do administrador é obrigatório.';
    if (!filter_var($emailAdmin, FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido.';
    if (strlen($senhaAdmin) < 8)                        $erros[] = 'A senha deve ter no mínimo 8 caracteres.';
    if ($senhaAdmin !== $confirma)                      $erros[] = 'As senhas não coincidem.';

    if (empty($erros)) {
        try {
            // Conecta ao MySQL sem selecionar banco de dados
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $pdoInstall = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Lê e executa o SQL de estrutura
            $sql = file_get_contents(ROOT_PATH . '/database.sql');

            // Executa cada instrução separadamente
            $pdoInstall->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdoInstall->exec("USE `" . DB_NAME . "`");

            // Remove comentários e divide por ponto-e-vírgula
            $sql = preg_replace('/--[^\n]*\n/', '', $sql);
            $instrucoes = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($instrucoes as $instrucao) {
                if (!empty($instrucao) &&
                    !str_starts_with(strtoupper($instrucao), 'CREATE DATABASE') &&
                    !str_starts_with(strtoupper($instrucao), 'USE ')) {
                    $pdoInstall->exec($instrucao);
                }
            }

            // Cria o usuário administrador
            $hash = password_hash($senhaAdmin, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdoInstall->prepare(
                "INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, 'administrador')"
            );
            $stmt->execute([$nomeAdmin, $emailAdmin, $hash]);

            $sucesso  = true;
            $mensagem = 'Instalação concluída com sucesso!';
            $etapa    = 3;

        } catch (PDOException $e) {
            $erros[] = 'Erro no banco de dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="text-center mb-4">
                <i class="fas fa-server text-primary" style="font-size:2.5rem;"></i>
                <h3 class="mt-2 fw-bold"><?= APP_NAME ?> — Instalação</h3>
            </div>

            <?php if ($sucesso): ?>
            <!-- Sucesso -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center">
                    <i class="fas fa-check-circle text-success" style="font-size:3rem;"></i>
                    <h4 class="mt-3 text-success">Instalação Concluída!</h4>
                    <p class="text-muted mt-2">O sistema foi instalado com sucesso. Agora você pode acessar o painel.</p>
                    <div class="alert alert-warning small text-start mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Segurança:</strong> Remova ou proteja o arquivo <code>install.php</code>
                        do servidor após a instalação para evitar acessos indevidos.
                    </div>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary mt-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Ir para o Login
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- Formulário de instalação -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-cog me-2"></i>Configuração Inicial do Sistema
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($erros)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($erros as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Verificação de pré-requisitos -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-muted mb-3">Pré-requisitos</h6>
                        <?php
                        $checks = [
                            ['PHP >= 8.0', version_compare(PHP_VERSION, '8.0.0', '>=')],
                            ['Extensão PDO',     extension_loaded('pdo')],
                            ['Extensão PDO MySQL', extension_loaded('pdo_mysql')],
                            ['Arquivo database.sql', file_exists(ROOT_PATH . '/database.sql')],
                        ];
                        foreach ($checks as [$label, $ok]):
                        ?>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fas fa-<?= $ok ? 'check-circle text-success' : 'times-circle text-danger' ?>"></i>
                            <span class="<?= $ok ? '' : 'text-danger' ?>"><?= $label ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <form method="POST">
                        <h6 class="fw-semibold text-muted mb-3">Dados do Administrador</h6>

                        <div class="mb-3">
                            <label class="form-label">Nome completo <span class="text-danger">*</span></label>
                            <input type="text" name="nome_admin" class="form-control"
                                   value="<?= htmlspecialchars($_POST['nome_admin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" name="email_admin" class="form-control"
                                   value="<?= htmlspecialchars($_POST['email_admin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Senha <span class="text-danger">*</span></label>
                            <input type="password" name="senha_admin" class="form-control"
                                   placeholder="Mínimo 8 caracteres" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirmar senha <span class="text-danger">*</span></label>
                            <input type="password" name="confirma_senha" class="form-control" required>
                        </div>

                        <button type="submit" name="instalar" class="btn btn-primary w-100">
                            <i class="fas fa-database me-2"></i>Instalar Sistema
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
