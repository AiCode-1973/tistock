<?php
/**
 * TI Stock - Editar Usuário
 * Requer nível administrador.
 * Permite alterar nome, e-mail, nível e senha.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Editar Usuário';
$activePage = 'usuarios';

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

$stmtUser = $pdo->prepare("SELECT id, nome, email, nivel, ativo FROM usuarios WHERE id = ?");
$stmtUser->execute([$id]);
$usuario  = $stmtUser->fetch();

if (!$usuario) {
    setFlash('danger', 'Usuário não encontrado.');
    header('Location: ' . BASE_URL . '/pages/usuarios/listar.php');
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome']  ?? '');
    $email  = trim($_POST['email'] ?? '');
    $nivel  = $_POST['nivel']      ?? 'consultor';
    $senha  = $_POST['senha']      ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if (empty($nome))                                  $erros[] = 'Nome obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $erros[] = 'E-mail inválido.';
    if (!in_array($nivel, ['administrador','tecnico','consultor'], true))
                                                        $erros[] = 'Nível inválido.';
    if ($senha !== '' && strlen($senha) < 8)            $erros[] = 'Nova senha deve ter ao menos 8 caracteres.';
    if ($senha !== '' && $senha !== $confirma)          $erros[] = 'As senhas não coincidem.';

    // Verifica e-mail duplicado (exceto o próprio usuário)
    if (empty($erros)) {
        $stmtChk = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmtChk->execute([$email, $id]);
        if ($stmtChk->fetchColumn()) {
            $erros[] = 'Já existe outro usuário com este e-mail.';
        }
    }

    if (empty($erros)) {
        if ($senha !== '') {
            $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE usuarios SET nome=?, email=?, nivel=?, senha=? WHERE id=?")
                ->execute([$nome, $email, $nivel, $hash, $id]);
        } else {
            $pdo->prepare("UPDATE usuarios SET nome=?, email=?, nivel=? WHERE id=?")
                ->execute([$nome, $email, $nivel, $id]);
        }

        setFlash('success', 'Usuário atualizado com sucesso!');
        header('Location: ' . BASE_URL . '/pages/usuarios/listar.php');
        exit;
    }

    // Atualiza array de exibição com os dados enviados
    $usuario['nome']  = $nome;
    $usuario['email'] = $email;
    $usuario['nivel'] = $nivel;
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Editar Usuário</h4>
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
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Nome completo <span class="text-danger">*</span></label>
                <input type="text" name="nome" class="form-control" required
                       value="<?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nível de Acesso <span class="text-danger">*</span></label>
                <select name="nivel" class="form-select" required
                    <?= $usuario['id'] === (int)$_SESSION['usuario_id'] ? 'disabled' : '' ?>>
                    <option value="consultor"     <?= $usuario['nivel'] === 'consultor'     ? 'selected' : '' ?>>Consultor</option>
                    <option value="tecnico"       <?= $usuario['nivel'] === 'tecnico'       ? 'selected' : '' ?>>Técnico</option>
                    <option value="administrador" <?= $usuario['nivel'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                </select>
                <?php if ($usuario['id'] === (int)$_SESSION['usuario_id']): ?>
                <!-- Passa o valor via campo hidden pois o select está desabilitado -->
                <input type="hidden" name="nivel" value="<?= htmlspecialchars($usuario['nivel'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text text-warning">Você não pode alterar o próprio nível de acesso.</div>
                <?php endif; ?>
            </div>

            <hr>
            <p class="text-muted small">Deixe os campos de senha em branco para manter a senha atual.</p>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nova Senha</label>
                <input type="password" name="senha" class="form-control" placeholder="Mínimo 8 caracteres">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmar Nova Senha</label>
                <input type="password" name="confirma" class="form-control">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Alterações</button>
                <a href="<?= BASE_URL ?>/pages/usuarios/listar.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
