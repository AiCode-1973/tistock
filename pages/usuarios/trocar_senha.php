<?php
/**
 * TI Stock - Trocar Senha
 * Permite que qualquer usuário autenticado altere sua própria senha.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Trocar Senha';
$activePage = '';

$erros  = [];
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual  = $_POST['senha_atual']     ?? '';
    $novaSenha   = $_POST['nova_senha']      ?? '';
    $confirmacao = $_POST['confirmacao']     ?? '';

    // Validações
    if (empty($senhaAtual))                  $erros[] = 'Informe a senha atual.';
    if (strlen($novaSenha) < 8)              $erros[] = 'A nova senha deve ter no mínimo 8 caracteres.';
    if ($novaSenha !== $confirmacao)         $erros[] = 'A confirmação não confere com a nova senha.';
    if ($novaSenha === $senhaAtual && empty($erros))
                                             $erros[] = 'A nova senha deve ser diferente da senha atual.';

    if (empty($erros)) {
        // Busca hash atual do usuário
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($senhaAtual, $usuario['senha'])) {
            $erros[] = 'Senha atual incorreta.';
        } else {
            $hash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
                ->execute([$hash, $_SESSION['usuario_id']]);

            $sucesso = true;
        }
    }
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-key me-2 text-warning"></i>Trocar Senha</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

        <?php if ($sucesso): ?>
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fs-5"></i>
            <div><strong>Senha alterada com sucesso!</strong> Use a nova senha no próximo acesso.</div>
        </div>
        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-primary">
            <i class="fas fa-home me-1"></i>Ir ao Dashboard
        </a>

        <?php else: ?>

        <?php if (!empty($erros)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($erros as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="" novalidate autocomplete="off">

                    <div class="mb-3">
                        <label for="senha_atual" class="form-label fw-semibold">Senha Atual</label>
                        <div class="input-group">
                            <input type="password" id="senha_atual" name="senha_atual"
                                   class="form-control" required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="senha_atual" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nova_senha" class="form-label fw-semibold">Nova Senha</label>
                        <div class="input-group">
                            <input type="password" id="nova_senha" name="nova_senha"
                                   class="form-control" required autocomplete="new-password"
                                   minlength="8">
                            <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="nova_senha" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Mínimo de 8 caracteres.</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmacao" class="form-label fw-semibold">Confirmar Nova Senha</label>
                        <div class="input-group">
                            <input type="password" id="confirmacao" name="confirmacao"
                                   class="form-control" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="confirmacao" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning fw-semibold">
                            <i class="fas fa-save me-1"></i>Alterar Senha
                        </button>
                        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>

                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
