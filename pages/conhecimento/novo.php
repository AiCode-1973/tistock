<?php
/**
 * TI Stock - Base de Conhecimento - Novo Artigo
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$pageTitle  = 'Novo Artigo';
$activePage = 'conhecimento_novo';

$kbCategorias = $pdo->query("SELECT id, nome FROM kb_categorias ORDER BY nome")->fetchAll();
$erros        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']       ?? '');
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $conteudo    = trim($_POST['conteudo']      ?? '');

    if (empty($titulo))                                    $erros[] = 'O título é obrigatório.';
    if (mb_strlen($titulo) > 255)                          $erros[] = 'O título deve ter no máximo 255 caracteres.';
    if (empty($conteudo) || $conteudo === '<p><br></p>')   $erros[] = 'O conteúdo é obrigatório.';

    if (empty($erros)) {
        // Gera slug único a partir do título
        $base = mb_strtolower($titulo, 'UTF-8');
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: '';
        $base = preg_replace('/[^a-z0-9]+/', '-', $base);
        $base = trim($base, '-') ?: 'artigo';

        $slug = $base;
        $i    = 2;
        while (true) {
            $chk = $pdo->prepare("SELECT id FROM kb_artigos WHERE slug = ? LIMIT 1");
            $chk->execute([$slug]);
            if (!$chk->fetch()) break;
            $slug = $base . '-' . $i++;
        }

        $pdo->prepare(
            "INSERT INTO kb_artigos (titulo, slug, conteudo, categoria_id, autor_id)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $titulo,
            $slug,
            $conteudo,
            $categoriaId > 0 ? $categoriaId : null,
            $_SESSION['usuario_id'],
        ]);

        $novoId = (int) $pdo->lastInsertId();
        registrarLog($pdo, 'KB_CRIAR', "Artigo criado: \"{$titulo}\" (ID {$novoId})");
        setFlash('success', "Artigo \"$titulo\" criado com sucesso!");
        header('Location: ' . BASE_URL . '/pages/conhecimento/visualizar.php?id=' . $novoId);
        exit;
    }
}

require_once ROOT_PATH . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/pages/conhecimento/index.php" class="btn btn-outline-secondary btn-sm me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Novo Artigo</h4>
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

<form method="POST" action="" id="formArtigo" novalidate>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" id="titulo" name="titulo" class="form-control"
                               maxlength="255" required autofocus
                               value="<?= htmlspecialchars($_POST['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Conteúdo <span class="text-danger">*</span></label>
                    </div>
                    <div id="editor" style="min-height:320px;"></div>
                    <textarea name="conteudo" id="conteudo" style="display:none;"></textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="categoria_id" class="form-label fw-semibold">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="form-select">
                            <option value="">— Sem categoria —</option>
                            <?php foreach ($kbCategorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                    <?= ((int)($_POST['categoria_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Publicar Artigo
                        </button>
                        <a href="<?= BASE_URL ?>/pages/conhecimento/index.php"
                           class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ indent: '-1' }, { indent: '+1' }],
            ['blockquote', 'code-block'],
            ['link'],
            ['clean']
        ]
    }
});

<?php if (!empty($_POST['conteudo'])): ?>
quill.root.innerHTML = <?= json_encode($_POST['conteudo']) ?>;
<?php endif; ?>

document.getElementById('formArtigo').addEventListener('submit', function () {
    document.getElementById('conteudo').value = quill.root.innerHTML;
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
