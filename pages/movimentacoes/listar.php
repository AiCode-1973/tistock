<?php
/**
 * TI Stock - Listar Movimentações
 * Histórico completo com filtros por item, tipo e período.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Histórico de Movimentações';
$activePage = 'movimentacoes';

// Filtros
$itemId     = (int)($_GET['item_id']  ?? 0);
$tipo       = $_GET['tipo']            ?? '';
$dataInicio = $_GET['data_inicio']     ?? '';
$dataFim    = $_GET['data_fim']        ?? '';
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina  = 20;

$where  = "WHERE 1=1";
$params = [];

if ($itemId > 0) {
    $where   .= " AND m.item_id = ?";
    $params[] = $itemId;
}
if (in_array($tipo, ['entrada', 'saida'], true)) {
    $where   .= " AND m.tipo = ?";
    $params[] = $tipo;
}
if ($dataInicio) {
    $where   .= " AND DATE(m.data_movimentacao) >= ?";
    $params[] = $dataInicio;
}
if ($dataFim) {
    $where   .= " AND DATE(m.data_movimentacao) <= ?";
    $params[] = $dataFim;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM movimentacoes m {$where}");
$stmtCount->execute($params);
$total     = (int) $stmtCount->fetchColumn();
$paginacao = paginar($total, $porPagina, $pagina);

$stmt = $pdo->prepare(
    "SELECT m.*, i.nome AS item_nome, u.nome AS usuario_nome
     FROM movimentacoes m
     JOIN itens i ON i.id = m.item_id
     LEFT JOIN usuarios u ON u.id = m.usuario_id
     {$where}
     ORDER BY m.data_movimentacao DESC
     LIMIT {$paginacao['por_pagina']} OFFSET {$paginacao['offset']}"
);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

// Itens para o filtro select
$listaItens = $pdo->query("SELECT id, nome FROM itens WHERE ativo = 1 ORDER BY nome")->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Histórico de Movimentações</h4>
    <?php if (hasPermission('tecnico')): ?>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/pages/movimentacoes/entrada.php" class="btn btn-success btn-sm">
            <i class="fas fa-arrow-circle-down me-1"></i>Registrar Entrada
        </a>
        <a href="<?= BASE_URL ?>/pages/movimentacoes/saida.php" class="btn btn-danger btn-sm">
            <i class="fas fa-arrow-circle-up me-1"></i>Registrar Saída
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label form-label-sm text-muted">Item</label>
                <select name="item_id" class="form-select form-select-sm">
                    <option value="">Todos os itens</option>
                    <?php foreach ($listaItens as $it): ?>
                    <option value="<?= $it['id'] ?>" <?= $itemId == $it['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="saida"   <?= $tipo === 'saida'   ? 'selected' : '' ?>>Saída</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">De</label>
                <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= $dataInicio ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">Até</label>
                <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= $dataFim ?>">
            </div>
            <div class="col-sm-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-search"></i></button>
                <a href="<?= BASE_URL ?>/pages/movimentacoes/listar.php" class="btn btn-outline-secondary btn-sm" title="Limpar"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <span class="text-muted small"><?= $total ?> movimentação(ões) encontrada(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($movimentacoes)): ?>
        <p class="text-muted text-center py-5 mb-0">Nenhuma movimentação encontrada.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Data/Hora</th>
                        <th>Item</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th class="text-center">Qtd</th>
                        <th>Responsável</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimentacoes as $mov): ?>
                    <tr>
                        <td class="small text-muted text-nowrap"><?= formatarData($mov['data_movimentacao'], true) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $mov['item_id'] ?>" class="fw-semibold text-decoration-none small">
                                <?= htmlspecialchars($mov['item_nome'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= getBadgeMovimentacao($mov['tipo']) ?></td>
                        <td class="small"><?= getLabelMotivo($mov['motivo']) ?></td>
                        <td class="text-center fw-bold"><?= $mov['quantidade'] ?></td>
                        <td class="small"><?= htmlspecialchars($mov['responsavel'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($mov['observacoes'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($paginacao['total_paginas'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <small class="text-muted">Página <?= $paginacao['pagina_atual'] ?> de <?= $paginacao['total_paginas'] ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $paginacao['pagina_atual'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?item_id=<?= $itemId ?>&tipo=<?= $tipo ?>&data_inicio=<?= $dataInicio ?>&data_fim=<?= $dataFim ?>&pagina=<?= $paginacao['anterior'] ?>">Anterior</a>
                    </li>
                    <?php for ($i = max(1, $pagina - 2); $i <= min($paginacao['total_paginas'], $pagina + 2); $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?item_id=<?= $itemId ?>&tipo=<?= $tipo ?>&data_inicio=<?= $dataInicio ?>&data_fim=<?= $dataFim ?>&pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $paginacao['pagina_atual'] >= $paginacao['total_paginas'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?item_id=<?= $itemId ?>&tipo=<?= $tipo ?>&data_inicio=<?= $dataInicio ?>&data_fim=<?= $dataFim ?>&pagina=<?= $paginacao['proximo'] ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
