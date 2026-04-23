<?php
/**
 * TI Stock - Listar Recibos de Entrega (Empréstimos Ativos/Atrasados)
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Recibos de Entrega';
$activePage = 'emprestimos_recibos';

// Filtros
$busca  = trim($_GET['busca'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 15;

$where  = "WHERE e.status IN ('ativo', 'atrasado')";
$params = [];

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
    "SELECT e.*, i.nome AS item_nome, i.numero_patrimonio, i.numero_serie
     FROM emprestimos e
     JOIN itens i ON i.id = e.item_id
     {$where}
     ORDER BY e.data_saida DESC
     LIMIT {$paginacao['por_pagina']} OFFSET {$paginacao['offset']}"
);
$stmt->execute($params);
$recibos = $stmt->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2 text-primary"></i>Recibos de Entrega de Material</h4>
    <a href="<?= BASE_URL ?>/pages/emprestimos/emitir_recibo_avulso.php" class="btn btn-success btn-sm">
        <i class="fas fa-plus me-1"></i>Emitir Recibo Avulso
    </a>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <i class="fas fa-info-circle me-2"></i>
    Selecione um empréstimo ativo para gerar o recibo de entrega de material.
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6">
                <label class="form-label form-label-sm text-muted">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Item, solicitante ou setor...">
            </div>
            <div class="col-sm-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-search"></i></button>
                <a href="<?= BASE_URL ?>/pages/emprestimos/recibos.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($recibos)): ?>
        <p class="text-muted text-center py-5 mb-0">Nenhum empréstimo ativo encontrado para gerar recibo.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 border-0">Data</th>
                        <th class="border-0">Solicitante / Setor</th>
                        <th class="border-0">Item</th>
                        <th class="border-0 text-center">Quantidade</th>
                        <th class="border-0 text-end pe-3">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recibos as $r): ?>
                    <tr>
                        <td class="ps-3">
                            <span class="d-block fw-bold"><?= formatarData($r['data_saida']) ?></span>
                        </td>
                        <td>
                            <span class="d-block fw-bold"><?= htmlspecialchars($r['solicitante']) ?></span>
                            <small class="text-muted"><?= htmlspecialchars($r['setor_destino']) ?></small>
                        </td>
                        <td>
                            <span class="d-block"><?= htmlspecialchars($r['item_nome']) ?></span>
                            <?php if ($r['numero_patrimonio']): ?>
                                <small class="badge bg-light text-dark border fw-normal">Pat: <?= htmlspecialchars($r['numero_patrimonio']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $r['quantidade'] ?></td>
                        <td class="text-end pe-3">
                            <a href="<?= BASE_URL ?>/pages/emprestimos/gerar_recibo_entrega.php?id=<?= $r['id'] ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-print me-1"></i>Imprimir Recibo
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($paginacao['total_paginas'] > 1): ?>
<div class="mt-4">
    <?= renderPaginacao($paginacao, BASE_URL . '/pages/emprestimos/recibos.php', "busca=" . urlencode($busca)) ?>
</div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
