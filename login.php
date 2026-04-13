<?php
/**
 * TI Stock - Login
 * Autenticação de usuários com controle de sessão.
 */

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/init.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$erro = '';

// Processamento do formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha o e-mail e a senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Formato de e-mail inválido.';
    } elseif (loginUsuario($pdo, $email, $senha)) {
        registrarLog($pdo, 'login', "Login realizado com sucesso. E-mail: {$email}");
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    } else {
        $erro = 'E-mail ou senha incorretos. Verifique os dados e tente novamente.';
        registrarLog($pdo, 'login_falhou', "Tentativa de login com e-mail: " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8'), null, $email);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; }
        .login-card { border: none; border-radius: 16px; }
        .login-logo i { font-size: 3rem; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-9 col-md-7 col-lg-5 col-xl-4">

            <!-- Card de login -->
            <div class="card login-card shadow-lg">
                <div class="card-body p-5">

                    <!-- Logotipo -->
                    <div class="text-center mb-4 login-logo">
                        <i class="fas fa-server text-primary"></i>
                        <h4 class="mt-2 fw-bold"><?= APP_NAME ?></h4>
                        <p class="text-muted small"><?= APP_SUBTITLE ?></p>
                    </div>

                    <!-- Mensagem de erro -->
                    <?php if ($erro): ?>
                    <div class="alert alert-danger alert-sm py-2" role="alert">
                        <i class="fas fa-times-circle me-2"></i><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>

                    <!-- Formulário -->
                    <form method="POST" action="" novalidate>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="seu@email.com" required autofocus autocomplete="email">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label fw-semibold">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha"
                                       placeholder="••••••••" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleSenha" title="Mostrar/ocultar senha">
                                    <i class="fas fa-eye" id="iconeOlho"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </form>

                </div>
            </div>

            <!-- Sistema -->
            <p class="text-center text-white-50 small mt-3">
                <?= APP_NAME ?> &copy; <?= date('Y') ?> &mdash; Setor de TI Hospitalar
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Alternar visibilidade da senha
    document.getElementById('toggleSenha').addEventListener('click', function () {
        const campo = document.getElementById('senha');
        const icone = document.getElementById('iconeOlho');
        if (campo.type === 'password') {
            campo.type = 'text';
            icone.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            campo.type = 'password';
            icone.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
</script>
</body>
</html>
