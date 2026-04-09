<?php
/**
 * TI Stock - Listar Itens
 * Exibe todos os itens com filtros e paginação.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Itens de Estoque';
$activePage = 'itens';

// ----------------------------------------
// Filtros
// ----------------------------------------
$busca       = trim($_GET['busca']       ?? '');
$categoriaId = (int) ($_GET['categoria'] ?? 0);
$filtro      = $_GET['filtro'] ?? '';
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina   = 15;

// Monta a cláusula WHERE dinamicamente
$where  = "WHERE i.ativo = 1";
$params = [];

if ($busca !== '') {
    $where   .= " AND (i.nome LIKE ? OR i.numero_serie LIKE ? OR i.numero_patrimonio LIKE ? OR i.fornecedor LIKE ?)";
    $termoBusca = "%{$busca}%";
    array_push($params, $termoBusca, $termoBusca, $termoBusca, $termoBusca);
}

if ($categoriaId > 0) {
    $where   .= " AND i.categoria_id = ?";
    $params[] = $categoriaId;
}

if ($filtro === 'critico') {
    $where .= " AND i.quantidade_atual <= i.quantidade_minima";
}

// Total de registros para paginação
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM itens i JOIN categorias c ON c.id = i.categoria_id {$where}");
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();
$paginacao = paginar($total, $porPagina, $pagina);

// Consulta principal
$sql = "SELECT i.*, c.nome AS categoria_nome
        FROM itens i
        JOIN categorias c ON c.id = i.categoria_id
        {$where}
        ORDER BY i.nome
        LIMIT {$paginacao['por_pagina']} OFFSET {$paginacao['offset']}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll();

$categorias = getCategorias($pdo);

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-box-open me-2 text-primary"></i>Itens de Estoque</h4>
    <?php if (hasPermission('tecnico')): ?>
    <a href="<?= BASE_URL ?>/pages/itens/cadastrar.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Novo Item
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label form-label-sm text-muted">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                       placeholder="Nome, série, patrimônio ou fornecedor..."
                       value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm text-muted">Categoria</label>
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoriaId == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">Status</label>
                <select name="filtro" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="critico" <?= $filtro === 'critico' ? 'selected' : '' ?>>Estoque crítico</option>
                </select>
            </div>
            <div class="col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="<?= BASE_URL ?>/pages/itens/listar.php" class="btn btn-outline-secondary btn-sm" title="Limpar filtros">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">
            <?= $total ?> item(s) encontrado(s)
            <?= $filtro === 'critico' ? '<span class="badge bg-danger ms-2">Somente críticos</span>' : '' ?>
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($itens)): ?>
        <p class="text-muted text-center py-5 mb-0">Nenhum item encontrado com os filtros selecionados.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Nº Patrimônio</th>
                        <th>Localização</th>
                        <th class="text-center">Qtd Atual</th>
                        <th class="text-center">Qtd Mín.</th>
                        <th class="text-end">Valor Unit.</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item):
                        $critico = $item['quantidade_atual'] <= $item['quantidade_minima'];
                        $zerado  = $item['quantidade_atual'] == 0;
                    ?>
                    <tr class="<?= $zerado ? 'table-danger' : ($critico ? 'table-warning' : '') ?>">
                        <td>
                            <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $item['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php if ($item['numero_serie']): ?>
                            <br><small class="text-muted">S/N: <?= htmlspecialchars($item['numero_serie'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($item['categoria_nome'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="small text-muted"><?= htmlspecialchars($item['numero_patrimonio'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small"><?= htmlspecialchars($item['localizacao'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center fw-bold <?= $zerado ? 'text-danger' : ($critico ? 'text-warning' : 'text-success') ?>">
                            <?= $item['quantidade_atual'] ?>
                        </td>
                        <td class="text-center text-muted small"><?= $item['quantidade_minima'] ?></td>
                        <td class="text-end small"><?= formatarMoeda((float)$item['valor_unitario']) ?></td>
                        <td class="text-center">
                            <?php if ($zerado): ?>
                                <span class="badge bg-danger">Esgotado</span>
                            <?php elseif ($critico): ?>
                                <span class="badge bg-warning text-dark">Crítico</span>
                            <?php else: ?>
                                <span class="badge bg-success">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $item['id'] ?>" class="btn btn-outline-info" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (hasPermission('tecnico')): ?>
                                <a href="<?= BASE_URL ?>/pages/itens/editar.php?id=<?= $item['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (hasPermission('administrador')): ?>
                                <a href="<?= BASE_URL ?>/pages/itens/excluir.php?id=<?= $item['id'] ?>" class="btn btn-outline-danger btn-excluir" title="Excluir"
                                   data-nome="<?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($paginacao['total_paginas'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <small class="text-muted">
                Página <?= $paginacao['pagina_atual'] ?> de <?= $paginacao['total_paginas'] ?>
            </small>
            <nav aria-label="Paginação de itens">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $paginacao['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?busca=<?= urlencode($busca) ?>&categoria=<?= $categoriaId ?>&filtro=<?= $filtro ?>&pagina=<?= $paginacao['anterior'] ?>">Anterior</a>
                    </li>
                    <?php for ($i = max(1, $pagina - 2); $i <= min($paginacao['total_paginas'], $pagina + 2); $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?busca=<?= urlencode($busca) ?>&categoria=<?= $categoriaId ?>&filtro=<?= $filtro ?>&pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $paginacao['pagina_atual'] >= $paginacao['total_paginas'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?busca=<?= urlencode($busca) ?>&categoria=<?= $categoriaId ?>&filtro=<?= $filtro ?>&pagina=<?= $paginacao['proximo'] ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
