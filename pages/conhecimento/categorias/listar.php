<?php
/**
 * TI Stock - Base de Conhecimento - Listar Categorias KB
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Categorias — Base de Conhecimento';
$activePage = 'kb_categorias';

$categorias = $pdo->query(
    "SELECT c.*, COUNT(a.id) AS total_artigos
     FROM kb_categorias c
     LEFT JOIN kb_artigos a ON a.categoria_id = c.id AND a.ativo = 1
     GROUP BY c.id
     ORDER BY c.nome"
)->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-folder me-2 text-primary"></i>Categorias — Base de Conhecimento</h4>
    <a href="<?= BASE_URL ?>/pages/conhecimento/categorias/nova.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Nova Categoria
    </a>
</div>

<?= flashMessage() ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <span class="text-muted small"><?= count($categorias) ?> categoria(s) cadastrada(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($categorias)): ?>
        <p class="text-muted text-center py-5 mb-0">Nenhuma categoria cadastrada.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th class="text-center">Artigos</th>
                        <th>Criado em</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td class="text-muted small"><?= $cat['id'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted">
                            <?= $cat['descricao']
                                ? htmlspecialchars(mb_strimwidth($cat['descricao'], 0, 60, '…'), ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?= $cat['total_artigos'] ?></span>
                        </td>
                        <td class="small text-muted"><?= formatarData($cat['criado_em']) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>/pages/conhecimento/categorias/editar.php?id=<?= $cat['id'] ?>"
                               class="btn btn-outline-primary btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ((int)$cat['total_artigos'] === 0): ?>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    title="Excluir"
                                    onclick="confirmarExclusao(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nome']), ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    title="Não é possível excluir: há artigos vinculados" disabled>
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Deseja excluir a categoria <strong id="nomeCategoria"></strong>?
                <p class="text-muted small mt-2 mb-0">Esta ação não poderá ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a id="linkExcluir" href="#" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash me-1"></i>Excluir
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('nomeCategoria').textContent = nome;
    document.getElementById('linkExcluir').href =
        '<?= BASE_URL ?>/pages/conhecimento/categorias/excluir.php?id=' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
