<?php
/**
 * TI Stock - Base de Conhecimento - Editar Artigo
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kb_artigos WHERE id = ? AND ativo = 1 LIMIT 1");
$stmt->execute([$id]);
$artigo = $stmt->fetch();

if (!$artigo) {
    setFlash('danger', 'Artigo não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$kbCategorias = $pdo->query("SELECT id, nome FROM kb_categorias ORDER BY nome")->fetchAll();
$erros        = [];
$UPLOAD_DIR   = ROOT_PATH . '/uploads/conhecimento/';
$MAX_BYTES    = 10 * 1024 * 1024; // 10 MB
$MIME_OK      = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/zip',
    'application/x-zip-compressed',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo']       ?? '');
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $conteudo    = trim($_POST['conteudo']      ?? '');

    if (empty($titulo))                                    $erros[] = 'O título é obrigatório.';
    if (mb_strlen($titulo) > 255)                          $erros[] = 'O título deve ter no máximo 255 caracteres.';
    if (empty($conteudo) || $conteudo === '<p><br></p>')   $erros[] = 'O conteúdo é obrigatório.';

    // Valida e coleta os arquivos enviados
    $arquivos = [];
    if (!empty($_FILES['anexos']['name'][0])) {
        $total = count($_FILES['anexos']['name']);
        if ($total > 10) {
            $erros[] = 'Máximo de 10 anexos por artigo.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            for ($f = 0; $f < $total; $f++) {
                if ($_FILES['anexos']['error'][$f] === UPLOAD_ERR_NO_FILE) continue;
                if ($_FILES['anexos']['error'][$f] !== UPLOAD_ERR_OK) {
                    $erros[] = 'Erro ao enviar o arquivo "' . htmlspecialchars(basename($_FILES['anexos']['name'][$f]), ENT_QUOTES, 'UTF-8') . '".';
                    continue;
                }
                $nomeOriginal = basename($_FILES['anexos']['name'][$f]);
                $tamanho      = (int) $_FILES['anexos']['size'][$f];
                $tmpPath      = $_FILES['anexos']['tmp_name'][$f];
                if ($tamanho > $MAX_BYTES) {
                    $erros[] = 'O arquivo "' . htmlspecialchars($nomeOriginal, ENT_QUOTES, 'UTF-8') . '" excede o limite de 10 MB.';
                    continue;
                }
                $mimeReal = $finfo->file($tmpPath);
                if (!in_array($mimeReal, $MIME_OK, true)) {
                    $erros[] = 'Tipo não permitido: "' . htmlspecialchars($nomeOriginal, ENT_QUOTES, 'UTF-8') . '".';
                    continue;
                }
                $ext       = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
                $nomeSalvo = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');
                $arquivos[] = [
                    'tmp'      => $tmpPath,
                    'original' => $nomeOriginal,
                    'salvo'    => $nomeSalvo,
                    'tamanho'  => $tamanho,
                    'mime'     => $mimeReal,
                ];
            }
        }
    }

    if (empty($erros)) {
        // Regenera slug apenas se o título mudou
        if ($titulo !== $artigo['titulo']) {
            $base = mb_strtolower($titulo, 'UTF-8');
            $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: '';
            $base = preg_replace('/[^a-z0-9]+/', '-', $base);
            $base = trim($base, '-') ?: 'artigo';

            $slug = $base;
            $i    = 2;
            while (true) {
                $chk = $pdo->prepare("SELECT id FROM kb_artigos WHERE slug = ? AND id != ? LIMIT 1");
                $chk->execute([$slug, $id]);
                if (!$chk->fetch()) break;
                $slug = $base . '-' . $i++;
            }
        } else {
            $slug = $artigo['slug'];
        }

        $pdo->prepare(
            "UPDATE kb_artigos
             SET titulo = ?, slug = ?, conteudo = ?, categoria_id = ?
             WHERE id = ?"
        )->execute([
            $titulo,
            $slug,
            $conteudo,
            $categoriaId > 0 ? $categoriaId : null,
            $id,
        ]);

        // Salva os novos arquivos no disco e registra no banco
        if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);
        $stmtAnexo = $pdo->prepare(
            "INSERT INTO kb_anexos (artigo_id, nome_original, nome_arquivo, tamanho, mime_type) VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($arquivos as $arq) {
            if (move_uploaded_file($arq['tmp'], $UPLOAD_DIR . $arq['salvo'])) {
                $stmtAnexo->execute([$id, $arq['original'], $arq['salvo'], $arq['tamanho'], $arq['mime']]);
            }
        }

        registrarLog($pdo, 'KB_EDITAR', "Artigo editado: \"{$titulo}\" (ID {$id})");
        setFlash('success', "Artigo \"$titulo\" atualizado com sucesso!");
        header('Location: ' . BASE_URL . '/pages/conhecimento/visualizar.php?id=' . $id);
        exit;
    }

    // Reaproveita valores postados em caso de erro
    $artigo['titulo']       = $titulo;
    $artigo['conteudo']     = $conteudo;
    $artigo['categoria_id'] = $categoriaId;
}

$pageTitle  = 'Editar Artigo';
$activePage = 'conhecimento';

// Carrega anexos existentes
$stmtAnexos = $pdo->prepare("SELECT * FROM kb_anexos WHERE artigo_id = ? ORDER BY criado_em ASC");
$stmtAnexos->execute([$id]);
$anexos = $stmtAnexos->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/pages/conhecimento/visualizar.php?id=<?= $id ?>"
       class="btn btn-outline-secondary btn-sm me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2 text-primary"></i>Editar Artigo</h4>
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

<form method="POST" action="" id="formArtigo" enctype="multipart/form-data" novalidate>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" id="titulo" name="titulo" class="form-control"
                               maxlength="255" required autofocus
                               value="<?= htmlspecialchars($artigo['titulo'], ENT_QUOTES, 'UTF-8') ?>">
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
                                    <?= ((int)$artigo['categoria_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($anexos)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Anexos atuais</label>
                        <ul class="list-group list-group-flush border rounded">
                            <?php foreach ($anexos as $anx):
                                $kb = $anx['tamanho'] >= 1048576
                                    ? round($anx['tamanho'] / 1048576, 1) . ' MB'
                                    : round($anx['tamanho'] / 1024) . ' KB';
                            ?>
                            <li class="list-group-item d-flex align-items-center gap-2 px-2 py-1">
                                <i class="fas fa-paperclip text-muted fa-fw"></i>
                                <span class="flex-grow-1 small text-truncate" title="<?= htmlspecialchars($anx['nome_original'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($anx['nome_original'], ENT_QUOTES, 'UTF-8') ?>
                                    <span class="text-muted">(<?= $kb ?>)</span>
                                </span>
                                <a href="<?= BASE_URL ?>/pages/conhecimento/anexo_excluir.php?id=<?= $anx['id'] ?>&artigo=<?= $id ?>"
                                   class="btn btn-outline-danger btn-sm py-0 px-1"
                                   title="Remover"
                                   onclick="return confirm('Remover o anexo &quot;<?= htmlspecialchars(addslashes($anx['nome_original']), ENT_QUOTES, 'UTF-8') ?>&quot;?')">
                                    <i class="fas fa-times fa-xs"></i>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Adicionar anexos</label>
                        <input type="file" name="anexos[]" id="anexos" class="form-control form-control-sm" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.zip">
                        <div class="form-text">Máx. 10 arquivos · 10 MB cada. PDF, Word, Excel, imagens e ZIP.</div>
                    </div>

                    <div class="d-grid gap-2 mt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="<?= BASE_URL ?>/pages/conhecimento/visualizar.php?id=<?= $id ?>"
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

quill.root.innerHTML = <?= json_encode($artigo['conteudo']) ?>;

document.getElementById('formArtigo').addEventListener('submit', function () {
    document.getElementById('conteudo').value = quill.root.innerHTML;
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
