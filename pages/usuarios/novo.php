<?php
/**
 * TI Stock - Cadastrar Novo Usuário
 * Requer nível administrador.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Novo Usuário';
$activePage = 'usuarios';
$erros      = [];
$dados      = ['nome' => '', 'email' => '', 'nivel' => 'consultor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome'  => trim($_POST['nome']  ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'nivel' => $_POST['nivel']      ?? 'consultor',
    ];
    $senha   = $_POST['senha']   ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if (empty($dados['nome']))                              $erros[] = 'Nome obrigatório.';
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (!in_array($dados['nivel'], ['administrador','tecnico','consultor'], true))
                                                            $erros[] = 'Nível de acesso inválido.';
    if (strlen($senha) < 8)                                 $erros[] = 'Senha deve ter ao menos 8 caracteres.';
    if ($senha !== $confirma)                               $erros[] = 'As senhas não coincidem.';

    if (empty($erros)) {
        // Verifica se o e-mail já existe
        $stmtChk = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtChk->execute([$dados['email']]);
        if ($stmtChk->fetchColumn()) {
            $erros[] = 'Já existe um usuário com este e-mail.';
        } else {
            $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare(
                "INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, ?)"
            )->execute([$dados['nome'], $dados['email'], $hash, $dados['nivel']]);

            setFlash('success', 'Usuário criado com sucesso!');
            header('Location: ' . BASE_URL . '/pages/usuarios/listar.php');
            exit;
        }
    }
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>Novo Usuário</h4>
    <a href="<?= BASE_URL ?>/pages/usuarios/listar.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3"><?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:600px;">
    <div class="card-body p-4">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
                <input type="text" name="nome" class="form-control" required
                       value="<?= htmlspecialchars($dados['nome'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($dados['email'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nível de Acesso <span class="text-danger">*</span></label>
                <select name="nivel" class="form-select" required>
                    <option value="consultor"     <?= $dados['nivel'] === 'consultor'     ? 'selected' : '' ?>>Consultor (somente leitura)</option>
                    <option value="tecnico"       <?= $dados['nivel'] === 'tecnico'       ? 'selected' : '' ?>>Técnico (entradas e saídas)</option>
                    <option value="administrador" <?= $dados['nivel'] === 'administrador' ? 'selected' : '' ?>>Administrador (acesso total)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Senha <span class="text-danger">*</span></label>
                <input type="password" name="senha" class="form-control" required placeholder="Mínimo 8 caracteres">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmar senha <span class="text-danger">*</span></label>
                <input type="password" name="confirma" class="form-control" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Criar Usuário</button>
                <a href="<?= BASE_URL ?>/pages/usuarios/listar.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
