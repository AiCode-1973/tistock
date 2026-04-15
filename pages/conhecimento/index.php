<?php
/**
 * TI Stock - Base de Conhecimento - Listagem de Artigos
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Base de Conhecimento';
$activePage = 'conhecimento';

$busca       = trim($_GET['busca'] ?? '');
$categoriaId = (int)($_GET['categoria'] ?? 0);
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina   = 12;

$where  = "WHERE a.ativo = 1";
$params = [];

if ($categoriaId > 0) {
    $where   .= " AND a.categoria_id = ?";
    $params[] = $categoriaId;
}
if ($busca !== '') {
    $where   .= " AND (a.titulo LIKE ? OR a.conteudo LIKE ?)";
    $termo    = "%{$busca}%";
    array_push($params, $termo, $termo);
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM kb_artigos a {$where}");
$stmtCount->execute($params);
$total     = (int) $stmtCount->fetchColumn();
$paginacao = paginar($total, $porPagina, $pagina);

$stmt = $pdo->prepare(
    "SELECT a.id, a.titulo, a.visualizacoes, a.criado_em, a.atualizado_em,
            c.nome AS categoria_nome,
            u.nome AS autor_nome
     FROM kb_artigos a
     LEFT JOIN kb_categorias c ON c.id = a.categoria_id
     LEFT JOIN usuarios u      ON u.id = a.autor_id
     {$where}
     ORDER BY a.atualizado_em DESC
     LIMIT {$paginacao['por_pagina']} OFFSET {$paginacao['offset']}"
);
$stmt->execute($params);
$artigos = $stmt->fetchAll();

$kbCategorias = $pdo->query(
    "SELECT c.id, c.nome, COUNT(a.id) AS total
     FROM kb_categorias c
     LEFT JOIN kb_artigos a ON a.categoria_id = c.id AND a.ativo = 1
     GROUP BY c.id
     ORDER BY c.nome"
)->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-book me-2 text-primary"></i>Base de Conhecimento</h4>
    <?php if (hasPermission('tecnico')): ?>
    <a href="<?= BASE_URL ?>/pages/conhecimento/novo.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Novo Artigo
    </a>
    <?php endif; ?>
</div>

<?= flashMessage() ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label form-label-sm text-muted">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Título ou conteúdo...">
            </div>
            <div class="col-sm-4">
                <label class="form-label form-label-sm text-muted">Categoria</label>
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($kbCategorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoriaId === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?> (<?= $cat['total'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-search me-1"></i>Buscar
                </button>
                <?php if ($busca !== '' || $categoriaId > 0): ?>
                <a href="<?= BASE_URL ?>/pages/conhecimento/index.php"
                   class="btn btn-outline-secondary btn-sm" title="Limpar filtros">
                    <i class="fas fa-times"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="mb-3">
    <small class="text-muted">
        <?= ($busca !== '' || $categoriaId > 0) ? "{$total} resultado(s) encontrado(s)" : "{$total} artigo(s) cadastrado(s)" ?>
    </small>
</div>

<?php if (empty($artigos)): ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-book-open fa-3x mb-3 opacity-25"></i>
    <p class="mb-0">
        <?= ($busca !== '' || $categoriaId > 0)
            ? 'Nenhum artigo encontrado para os filtros aplicados.'
            : 'Nenhum artigo cadastrado ainda.' ?>
    </p>
    <?php if (hasPermission('tecnico')): ?>
    <a href="<?= BASE_URL ?>/pages/conhecimento/novo.php" class="btn btn-primary btn-sm mt-3">
        <i class="fas fa-plus me-1"></i>Criar primeiro artigo
    </a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($artigos as $artigo): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
                <?php if ($artigo['categoria_nome']): ?>
                <span class="badge bg-secondary mb-2 align-self-start">
                    <?= htmlspecialchars($artigo['categoria_nome'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php endif; ?>
                <h6 class="card-title fw-semibold mb-2">
                    <a href="<?= BASE_URL ?>/pages/conhecimento/visualizar.php?id=<?= $artigo['id'] ?>"
                       class="text-decoration-none text-dark stretched-link">
                        <?= htmlspecialchars($artigo['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </h6>
                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-eye me-1"></i><?= $artigo['visualizacoes'] ?>
                    </small>
                    <small class="text-muted"><?= formatarData($artigo['atualizado_em']) ?></small>
                </div>
                <?php if ($artigo['autor_nome']): ?>
                <small class="text-muted mt-1">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($artigo['autor_nome'], ENT_QUOTES, 'UTF-8') ?>
                </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($paginacao['total_paginas'] > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-4">
    <small class="text-muted">Página <?= $paginacao['pagina_atual'] ?> de <?= $paginacao['total_paginas'] ?></small>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $paginacao['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?busca=<?= urlencode($busca) ?>&categoria=<?= $categoriaId ?>&pagina=<?= $paginacao['anterior'] ?>">Anterior</a>
            </li>
            <?php for ($i = max(1, $pagina - 2); $i <= min($paginacao['total_paginas'], $pagina + 2); $i++): ?>
            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?busca=<?= urlencode($busca) ?>&categoria=<?= $categoriaId ?>&pagina=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $paginacao['pagina_atual'] >= $paginacao['total_paginas'] ? 'disabled' : '' ?>">
                <a class="page-link" href="?busca=<?= urlencode($busca) ?>&categoria=<?= $categoriaId ?>&pagina=<?= $paginacao['proximo'] ?>">Próximo</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
