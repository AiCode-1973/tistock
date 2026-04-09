<?php
/**
 * TI Stock - Listar Empréstimos
 * Exibe todos os empréstimos com destaque visual para itens atrasados.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Empréstimos';
$activePage = 'emprestimos';

atualizarEmprestimosAtrasados($pdo);

// Filtros
$status = $_GET['filtro'] ?? '';
$busca  = trim($_GET['busca'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 15;

$where  = "WHERE 1=1";
$params = [];

if (in_array($status, ['ativo', 'devolvido', 'atrasado'], true)) {
    $where   .= " AND e.status = ?";
    $params[] = $status;
}
if ($busca !== '') {
    $where   .= " AND (i.nome LIKE ? OR e.solicitante LIKE ? OR e.setor_destino LIKE ?)";
    $termo    = "%{$busca}%";
    array_push($params, $termo, $termo, $termo);
}

$stmtCount = $pdo->prepare(
    "SELECT COUNT(*) FROM emprestimos e JOIN itens i ON i.id = e.item_id {$where}"
);
$stmtCount->execute($params);
$total     = (int) $stmtCount->fetchColumn();
$paginacao = paginar($total, $porPagina, $pagina);

$stmt = $pdo->prepare(
    "SELECT e.*, i.nome AS item_nome
     FROM emprestimos e
     JOIN itens i ON i.id = e.item_id
     {$where}
     ORDER BY FIELD(e.status,'atrasado','ativo','devolvido'), e.previsao_devolucao ASC
     LIMIT {$paginacao['por_pagina']} OFFSET {$paginacao['offset']}"
);
$stmt->execute($params);
$emprestimos = $stmt->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-handshake me-2 text-primary"></i>Empréstimos</h4>
    <?php if (hasPermission('tecnico')): ?>
    <a href="<?= BASE_URL ?>/pages/emprestimos/novo.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Novo Empréstimo
    </a>
    <?php endif; ?>
</div>

<!-- Filtros rápidos -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label form-label-sm text-muted">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Item, solicitante ou setor...">
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm text-muted">Status</label>
                <select name="filtro" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="ativo"     <?= $status === 'ativo'     ? 'selected' : '' ?>>Ativos</option>
                    <option value="atrasado"  <?= $status === 'atrasado'  ? 'selected' : '' ?>>Atrasados</option>
                    <option value="devolvido" <?= $status === 'devolvido' ? 'selected' : '' ?>>Devolvidos</option>
                </select>
            </div>
            <div class="col-sm-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-search"></i></button>
                <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <span class="text-muted small"><?= $total ?> empréstimo(s) encontrado(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($emprestimos)): ?>
        <p class="text-muted text-center py-5 mb-0">Nenhum empréstimo encontrado.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qtd</th>
                        <th>Solicitante</th>
                        <th>Setor</th>
                        <th>Saída</th>
                        <th>Prev. Devolução</th>
                        <th>Devolução Real</th>
                        <th class="text-center">Status</th>
                        <?php if (hasPermission('tecnico')): ?><th class="text-center">Ações</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emprestimos as $emp): ?>
                    <tr class="<?= $emp['status'] === 'atrasado' ? 'table-danger' : '' ?>">
                        <td>
                            <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $emp['item_id'] ?>" class="fw-semibold text-decoration-none small">
                                <?= htmlspecialchars($emp['item_nome'],   ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td class="text-center"><?= $emp['quantidade'] ?></td>
                        <td class="small"><?= htmlspecialchars($emp['solicitante'],  ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small"><?= htmlspecialchars($emp['setor_destino'],ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted text-nowrap"><?= formatarData($emp['data_saida'],             true) ?></td>
                        <td class="small <?= $emp['status'] === 'atrasado' ? 'fw-bold text-danger' : '' ?> text-nowrap">
                            <?= formatarData($emp['previsao_devolucao']) ?>
                            <?php if ($emp['status'] === 'atrasado'):
                                $dias = (int) (new DateTime())->diff(new DateTime($emp['previsao_devolucao']))->days;
                            ?>
                            <span class="badge bg-danger ms-1"><?= $dias ?>d atraso</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= formatarData($emp['data_devolucao'] ?? null, true) ?></td>
                        <td class="text-center"><?= getBadgeEmprestimo($emp['status']) ?></td>
                        <?php if (hasPermission('tecnico')): ?>
                        <td class="text-center">
                            <?php if ($emp['status'] !== 'devolvido'): ?>
                            <a href="<?= BASE_URL ?>/pages/emprestimos/devolver.php?id=<?= $emp['id'] ?>"
                               class="btn btn-success btn-sm" title="Registrar Devolução">
                                <i class="fas fa-undo"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginacao['total_paginas'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <small class="text-muted">Página <?= $paginacao['pagina_atual'] ?> de <?= $paginacao['total_paginas'] ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $paginacao['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?filtro=<?= $status ?>&busca=<?= urlencode($busca) ?>&pagina=<?= $paginacao['anterior'] ?>">Anterior</a>
                    </li>
                    <?php for ($i = max(1, $pagina - 2); $i <= min($paginacao['total_paginas'], $pagina + 2); $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?filtro=<?= $status ?>&busca=<?= urlencode($busca) ?>&pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $paginacao['pagina_atual'] >= $paginacao['total_paginas'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?filtro=<?= $status ?>&busca=<?= urlencode($busca) ?>&pagina=<?= $paginacao['proximo'] ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
