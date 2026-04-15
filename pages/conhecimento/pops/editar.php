<?php
/**
 * TI Stock - POPs - Editar POP
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kb_pops WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$pop = $stmt->fetch();

if (!$pop) {
    setFlash('danger', 'POP não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$pageTitle  = 'Editar POP — ' . $pop['codigo'];
$activePage = 'pops';

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo         = strtoupper(trim($_POST['codigo']                 ?? ''));
    $titulo         = trim($_POST['titulo']                             ?? '');
    $versao         = trim($_POST['versao']                             ?? '1.0');
    $objetivo       = trim($_POST['objetivo']                           ?? '');
    $escopo         = trim($_POST['escopo']                             ?? '');
    $respExecucao   = trim($_POST['responsavel_execucao']               ?? '');
    $respElaboracao = trim($_POST['responsavel_elaboracao']             ?? '');
    $procedimento   = trim($_POST['procedimento']                       ?? '');
    $referencias    = trim($_POST['referencias']                        ?? '');
    $status         = $_POST['status'] ?? 'ativo';

    if (empty($codigo))                                          $erros[] = 'O código é obrigatório.';
    if (mb_strlen($codigo) > 20)                                 $erros[] = 'O código deve ter no máximo 20 caracteres.';
    if (empty($titulo))                                          $erros[] = 'O título é obrigatório.';
    if (empty($objetivo))                                        $erros[] = 'O objetivo é obrigatório.';
    if (empty($escopo))                                          $erros[] = 'O escopo é obrigatório.';
    if (empty($respExecucao))                                    $erros[] = 'O responsável pela execução é obrigatório.';
    if (empty($respElaboracao))                                  $erros[] = 'O responsável pela elaboração é obrigatório.';
    if (empty($procedimento) || $procedimento === '<p><br></p>') $erros[] = 'O procedimento é obrigatório.';
    if (!in_array($status, ['ativo','revisao','obsoleto'], true)) $status = 'ativo';

    if (empty($erros)) {
        $chk = $pdo->prepare("SELECT id FROM kb_pops WHERE codigo = ? AND id != ? LIMIT 1");
        $chk->execute([$codigo, $id]);
        if ($chk->fetch()) $erros[] = "Já existe outro POP com o código \"{$codigo}\".";
    }

    if (empty($erros)) {
        $pdo->prepare(
            "UPDATE kb_pops SET
             codigo = ?, titulo = ?, versao = ?, objetivo = ?, escopo = ?,
             responsavel_execucao = ?, responsavel_elaboracao = ?,
             procedimento = ?, referencias = ?, status = ?
             WHERE id = ?"
        )->execute([
            $codigo, $titulo, $versao, $objetivo, $escopo,
            $respExecucao, $respElaboracao, $procedimento,
            $referencias ?: null, $status, $id,
        ]);

        registrarLog($pdo, 'POP_EDITAR', "POP editado: {$codigo} \"{$titulo}\" (ID {$id})");
        setFlash('success', "POP \"{$codigo}\" atualizado com sucesso!");
        header('Location: ' . BASE_URL . '/pages/conhecimento/pops/visualizar.php?id=' . $id);
        exit;
    }

    // Reaproveita valores postados em caso de erro
    $pop = array_merge($pop, [
        'codigo' => $codigo, 'titulo' => $titulo, 'versao' => $versao,
        'objetivo' => $objetivo, 'escopo' => $escopo,
        'responsavel_execucao' => $respExecucao,
        'responsavel_elaboracao' => $respElaboracao,
        'procedimento' => $procedimento,
        'referencias' => $referencias,
        'status' => $status,
    ]);
}

require_once ROOT_PATH . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>/pages/conhecimento/pops/visualizar.php?id=<?= $id ?>"
       class="btn btn-outline-secondary btn-sm me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0">
        <i class="fas fa-edit me-2 text-primary"></i>Editar POP —
        <?= htmlspecialchars($pop['codigo'], ENT_QUOTES, 'UTF-8') ?>
    </h4>
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

<form method="POST" action="" id="formPop" novalidate>
    <div class="row g-4">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-id-card me-2 text-muted"></i>Identificação
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label for="codigo" class="form-label fw-semibold">Código <span class="text-danger">*</span></label>
                            <input type="text" id="codigo" name="codigo" class="form-control text-uppercase"
                                   maxlength="20" required
                                   value="<?= htmlspecialchars($pop['codigo'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="titulo" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" id="titulo" name="titulo" class="form-control"
                                   maxlength="255" required autofocus
                                   value="<?= htmlspecialchars($pop['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-sm-2">
                            <label for="versao" class="form-label fw-semibold">Versão</label>
                            <input type="text" id="versao" name="versao" class="form-control"
                                   maxlength="10"
                                   value="<?= htmlspecialchars($pop['versao'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="responsavel_elaboracao" class="form-label fw-semibold">Resp. Elaboração <span class="text-danger">*</span></label>
                            <input type="text" id="responsavel_elaboracao" name="responsavel_elaboracao"
                                   class="form-control" maxlength="200" required
                                   value="<?= htmlspecialchars($pop['responsavel_elaboracao'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="responsavel_execucao" class="form-label fw-semibold">Resp. Execução <span class="text-danger">*</span></label>
                            <input type="text" id="responsavel_execucao" name="responsavel_execucao"
                                   class="form-control" maxlength="200" required
                                   value="<?= htmlspecialchars($pop['responsavel_execucao'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-bullseye me-2 text-muted"></i>Objetivo e Escopo
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="objetivo" class="form-label fw-semibold">Objetivo <span class="text-danger">*</span></label>
                        <textarea id="objetivo" name="objetivo" class="form-control" rows="3" required
                        ><?= htmlspecialchars($pop['objetivo'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label for="escopo" class="form-label fw-semibold">Escopo <span class="text-danger">*</span></label>
                        <textarea id="escopo" name="escopo" class="form-control" rows="3" required
                        ><?= htmlspecialchars($pop['escopo'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-list-ol me-2 text-muted"></i>Procedimento <span class="text-danger">*</span>
                </div>
                <div class="card-body pb-0">
                    <div id="editor" style="min-height:350px;"></div>
                    <textarea name="procedimento" id="procedimento" style="display:none;"></textarea>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-link me-2 text-muted"></i>Referências e Documentos Relacionados
                </div>
                <div class="card-body">
                    <textarea id="referencias" name="referencias" class="form-control" rows="3"
                              placeholder="Liste referências, normas ou documentos relacionados (opcional)"
                    ><?= htmlspecialchars($pop['referencias'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="ativo"    <?= $pop['status'] === 'ativo'    ? 'selected' : '' ?>>Ativo</option>
                            <option value="revisao"  <?= $pop['status'] === 'revisao'  ? 'selected' : '' ?>>Em Revisão</option>
                            <option value="obsoleto" <?= $pop['status'] === 'obsoleto' ? 'selected' : '' ?>>Obsoleto</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="<?= BASE_URL ?>/pages/conhecimento/pops/visualizar.php?id=<?= $id ?>"
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
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ indent: '-1' }, { indent: '+1' }],
            ['blockquote', 'code-block'],
            ['link'],
            ['clean']
        ]
    }
});

quill.root.innerHTML = <?= json_encode($pop['procedimento']) ?>;

document.getElementById('formPop').addEventListener('submit', function () {
    document.getElementById('procedimento').value = quill.root.innerHTML;
});

document.getElementById('codigo').addEventListener('input', function() {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
