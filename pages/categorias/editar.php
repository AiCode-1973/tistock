<?php
/**
 * TI Stock - Editar Categoria
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Editar Categoria';
$activePage = 'categorias';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    setFlash('danger', 'Categoria não encontrada.');
    header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']      ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($nome))           $erros[] = 'O nome da categoria é obrigatório.';
    if (mb_strlen($nome) > 100) $erros[] = 'O nome deve ter no máximo 100 caracteres.';

    if (empty($erros)) {
        // Verifica duplicata (exceto o próprio registro)
        $stmt2 = $pdo->prepare("SELECT id FROM categorias WHERE nome = ? AND id != ? LIMIT 1");
        $stmt2->execute([$nome, $id]);
        if ($stmt2->fetch()) {
            $erros[] = 'Já existe outra categoria com este nome.';
        }
    }

    if (empty($erros)) {
        $pdo->prepare("UPDATE categorias SET nome = ?, descricao = ? WHERE id = ?")
            ->execute([$nome, $descricao ?: null, $id]);

        setFlash('success', "Categoria \"$nome\" atualizada com sucesso!");
        header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
        exit;
    }

    // Reaproveita os valores postados em caso de erro
    $categoria['nome']      = $nome;
    $categoria['descricao'] = $descricao;
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/pages/categorias/listar.php" class="btn btn-outline-secondary btn-sm me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2 text-primary"></i>Editar Categoria</h4>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($erros as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="" novalidate>

                    <div class="mb-3">
                        <label for="nome" class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control"
                               maxlength="100" required autofocus
                               value="<?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-4">
                        <label for="descricao" class="form-label fw-semibold">Descrição</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="3"
                                  placeholder="Descreva brevemente esta categoria (opcional)"
                        ><?= htmlspecialchars($categoria['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="<?= BASE_URL ?>/pages/categorias/listar.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
