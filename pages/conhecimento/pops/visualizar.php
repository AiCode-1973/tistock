<?php
/**
 * TI Stock - POPs - Visualizar POP
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, u.nome AS autor_nome
     FROM kb_pops p
     LEFT JOIN usuarios u ON u.id = p.autor_id
     WHERE p.id = ? LIMIT 1"
);
$stmt->execute([$id]);
$pop = $stmt->fetch();

if (!$pop) {
    setFlash('danger', 'POP não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$pageTitle  = $pop['codigo'] . ' — ' . $pop['titulo'];
$activePage = 'pops';

$badgeStatus = match($pop['status']) {
    'ativo'    => '<span class="badge bg-success fs-6">Ativo</span>',
    'revisao'  => '<span class="badge bg-warning text-dark fs-6">Em Revisão</span>',
    'obsoleto' => '<span class="badge bg-secondary fs-6">Obsoleto</span>',
    default    => '',
};

require_once ROOT_PATH . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="d-flex align-items-center mb-3">
    <a href="<?= BASE_URL ?>/pages/conhecimento/pops/listar.php" class="btn btn-outline-secondary btn-sm me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <i class="fas fa-clipboard-check me-2 text-primary"></i>
            <?= htmlspecialchars($pop['codigo'], ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars($pop['titulo'], ENT_QUOTES, 'UTF-8') ?>
        </h4>
    </div>
    <div class="d-flex gap-2 ms-3 align-items-center">
        <?= $badgeStatus ?>
        <a href="<?= BASE_URL ?>/pages/conhecimento/pops/gerar_pdf.php?id=<?= $pop['id'] ?>"
           class="btn btn-danger btn-sm" target="_blank">
            <i class="fas fa-file-pdf me-1"></i>Baixar PDF
        </a>
        <?php if (hasPermission('tecnico')): ?>
        <a href="<?= BASE_URL ?>/pages/conhecimento/pops/editar.php?id=<?= $pop['id'] ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btnExcluir">
            <i class="fas fa-ban me-1"></i>Tornar Obsoleto
        </button>
        <?php endif; ?>
    </div>
</div>

<?= flashMessage() ?>

<!-- Metadados -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                <tr>
                    <th class="text-muted fw-normal w-25 ps-3">Código</th>
                    <td><?= htmlspecialchars($pop['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <th class="text-muted fw-normal w-25">Versão</th>
                    <td><?= htmlspecialchars($pop['versao'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal ps-3">Resp. Elaboração</th>
                    <td><?= htmlspecialchars($pop['responsavel_elaboracao'], ENT_QUOTES, 'UTF-8') ?></td>
                    <th class="text-muted fw-normal">Resp. Execução</th>
                    <td><?= htmlspecialchars($pop['responsavel_execucao'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal ps-3">Data Elaboração</th>
                    <td><?= $pop['data_elaboracao'] ? formatarData($pop['data_elaboracao']) : '<span class="text-muted">—</span>' ?></td>
                    <th class="text-muted fw-normal">Última revisão</th>
                    <td><?= formatarData($pop['atualizado_em']) ?></td>
                </tr>
                <?php if ($pop['autor_nome']): ?>
                <tr>
                    <th class="text-muted fw-normal ps-3">Autor no sistema</th>
                    <td colspan="3"><?= htmlspecialchars($pop['autor_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Objetivo -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-bullseye me-2 text-primary"></i>Objetivo</div>
    <div class="card-body">
        <?= nl2br(htmlspecialchars($pop['objetivo'], ENT_QUOTES, 'UTF-8')) ?>
    </div>
</div>

<!-- Escopo -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-expand-arrows-alt me-2 text-primary"></i>Escopo</div>
    <div class="card-body">
        <?= nl2br(htmlspecialchars($pop['escopo'], ENT_QUOTES, 'UTF-8')) ?>
    </div>
</div>

<!-- Procedimento -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-list-ol me-2 text-primary"></i>Procedimento</div>
    <div class="card-body">
        <div class="ql-editor" style="padding:0; min-height:auto;">
            <?= $pop['procedimento'] ?>
        </div>
    </div>
</div>

<!-- Referências -->
<?php if (!empty(trim($pop['referencias'] ?? ''))): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="fas fa-link me-2 text-primary"></i>Referências e Documentos Relacionados</div>
    <div class="card-body">
        <?= nl2br(htmlspecialchars($pop['referencias'], ENT_QUOTES, 'UTF-8')) ?>
    </div>
</div>
<?php endif; ?>

<?php if (hasPermission('tecnico')): ?>
<!-- Modal tornar obsoleto -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h6 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Tornar POP Obsoleto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                O POP <strong><?= htmlspecialchars($pop['codigo'], ENT_QUOTES, 'UTF-8') ?></strong>
                será marcado como <strong>Obsoleto</strong> e não aparecerá como ativo.
                <p class="text-muted small mt-2 mb-0">O registro será preservado para histórico.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a href="<?= BASE_URL ?>/pages/conhecimento/pops/excluir.php?id=<?= $pop['id'] ?>"
                   class="btn btn-warning btn-sm">
                    <i class="fas fa-ban me-1"></i>Tornar Obsoleto
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btnExcluir').addEventListener('click', function () {
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
});
</script>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
