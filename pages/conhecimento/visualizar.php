<?php
/**
 * TI Stock - Base de Conhecimento - Visualizar Artigo
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT a.*, c.nome AS categoria_nome, u.nome AS autor_nome
     FROM kb_artigos a
     LEFT JOIN kb_categorias c ON c.id = a.categoria_id
     LEFT JOIN usuarios u      ON u.id = a.autor_id
     WHERE a.id = ? AND a.ativo = 1
     LIMIT 1"
);
$stmt->execute([$id]);
$artigo = $stmt->fetch();

if (!$artigo) {
    setFlash('danger', 'Artigo não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$pdo->prepare("UPDATE kb_artigos SET visualizacoes = visualizacoes + 1 WHERE id = ?")
    ->execute([$id]);

$pageTitle  = htmlspecialchars($artigo['titulo'], ENT_QUOTES, 'UTF-8');
$activePage = 'conhecimento';

require_once ROOT_PATH . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">

<div class="d-flex align-items-center mb-3">
    <a href="<?= BASE_URL ?>/pages/conhecimento/index.php" class="btn btn-outline-secondary btn-sm me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0 flex-grow-1">
        <i class="fas fa-file-alt me-2 text-primary"></i>
        <?= htmlspecialchars($artigo['titulo'], ENT_QUOTES, 'UTF-8') ?>
    </h4>
    <?php if (hasPermission('tecnico')): ?>
    <div class="d-flex gap-2 ms-3">
        <a href="<?= BASE_URL ?>/pages/conhecimento/editar.php?id=<?= $artigo['id'] ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btnExcluir">
            <i class="fas fa-trash me-1"></i>Excluir
        </button>
    </div>
    <?php endif; ?>
</div>

<?= flashMessage() ?>

<div class="d-flex flex-wrap gap-3 mb-4 text-muted small">
    <?php if ($artigo['categoria_nome']): ?>
    <span><i class="fas fa-folder me-1"></i><?= htmlspecialchars($artigo['categoria_nome'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
    <?php if ($artigo['autor_nome']): ?>
    <span><i class="fas fa-user me-1"></i><?= htmlspecialchars($artigo['autor_nome'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
    <span><i class="fas fa-calendar me-1"></i>Criado em <?= formatarData($artigo['criado_em']) ?></span>
    <?php if ($artigo['atualizado_em'] !== $artigo['criado_em']): ?>
    <span><i class="fas fa-clock me-1"></i>Atualizado em <?= formatarData($artigo['atualizado_em']) ?></span>
    <?php endif; ?>
    <span><i class="fas fa-eye me-1"></i><?= (int)$artigo['visualizacoes'] + 1 ?> visualizações</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="ql-editor" style="padding:0; min-height:auto;">
            <?= $artigo['conteudo'] ?>
        </div>
    </div>
</div>

<?php if (hasPermission('tecnico')): ?>
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Deseja excluir o artigo <strong><?= htmlspecialchars($artigo['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>?
                <p class="text-muted small mt-2 mb-0">Esta ação não poderá ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a href="<?= BASE_URL ?>/pages/conhecimento/excluir.php?id=<?= $artigo['id'] ?>"
                   class="btn btn-danger btn-sm">
                    <i class="fas fa-trash me-1"></i>Excluir
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
