<?php
/**
 * TI Stock - Dashboard Principal
 * Exibe resumo do estoque, alertas críticos e últimas movimentações.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// ----------------------------------------
// Métricas do painel
// ----------------------------------------

// Total de itens ativos
$totalItens = (int) $pdo->query("SELECT COUNT(*) FROM itens WHERE ativo = 1")->fetchColumn();

// Total de itens em estoque crítico
$itensCriticos = (int) $pdo->query(
    "SELECT COUNT(*) FROM itens WHERE quantidade_atual <= quantidade_minima AND ativo = 1"
)->fetchColumn();

// Total de empréstimos ativos
$emprestimosAtivos = (int) $pdo->query(
    "SELECT COUNT(*) FROM emprestimos WHERE status IN ('ativo','atrasado')"
)->fetchColumn();

// Empréstimos com atraso
$emprestimosAtrasados = (int) $pdo->query(
    "SELECT COUNT(*) FROM emprestimos WHERE status = 'atrasado'"
)->fetchColumn();

// Movimentações do mês atual
$movMes = (int) $pdo->query(
    "SELECT COUNT(*) FROM movimentacoes WHERE MONTH(data_movimentacao) = MONTH(NOW()) AND YEAR(data_movimentacao) = YEAR(NOW())"
)->fetchColumn();

// Valor total do estoque
$valorTotal = (float) $pdo->query(
    "SELECT SUM(valor_unitario * quantidade_atual) FROM itens WHERE ativo = 1"
)->fetchColumn();

// ----------------------------------------
// Itens em estoque crítico
// ----------------------------------------
$stmtCriticos = $pdo->query(
    "SELECT i.id, i.nome, i.quantidade_atual, i.quantidade_minima, c.nome AS categoria, i.localizacao
     FROM itens i
     JOIN categorias c ON i.categoria_id = c.id
     WHERE i.quantidade_atual <= i.quantidade_minima AND i.ativo = 1
     ORDER BY i.quantidade_atual ASC
     LIMIT 10"
);
$listaCriticos = $stmtCriticos->fetchAll();

// ----------------------------------------
// Últimas movimentações
// ----------------------------------------
$stmtMovs = $pdo->query(
    "SELECT m.id, i.nome AS item_nome, m.tipo, m.motivo, m.quantidade,
            m.data_movimentacao, m.responsavel
     FROM movimentacoes m
     JOIN itens i ON i.id = m.item_id
     ORDER BY m.data_movimentacao DESC
     LIMIT 8"
);
$ultimasMovs = $stmtMovs->fetchAll();

// ----------------------------------------
// Empréstimos atrasados
// ----------------------------------------
$stmtAtrasados = $pdo->query(
    "SELECT e.id, i.nome AS item_nome, e.solicitante, e.setor_destino,
            e.previsao_devolucao, e.data_saida
     FROM emprestimos e
     JOIN itens i ON i.id = e.item_id
     WHERE e.status = 'atrasado'
     ORDER BY e.previsao_devolucao ASC
     LIMIT 5"
);
$listaAtrasados = $stmtAtrasados->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard</h4>
        <small class="text-muted">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            &mdash; <?= formatarData(date('Y-m-d H:i:s'), true) ?></small>
    </div>
</div>

<!-- ========== CARDS DE MÉTRICAS ========== -->
<div class="row g-3 mb-4">

    <!-- Total de Itens -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="fas fa-boxes fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $totalItens ?></div>
                    <div class="text-muted small">Total de Itens</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estoque Crítico -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-<?= $itensCriticos > 0 ? 'danger' : 'success' ?> border-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-<?= $itensCriticos > 0 ? 'danger' : 'success' ?> bg-opacity-10 p-3">
                    <i class="fas fa-exclamation-triangle fa-lg text-<?= $itensCriticos > 0 ? 'danger' : 'success' ?>"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $itensCriticos ?></div>
                    <div class="text-muted small">Estoque Crítico</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empréstimos Ativos -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="fas fa-handshake fa-lg text-info"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $emprestimosAtivos ?></div>
                    <div class="text-muted small">Empréstimos Ativos
                        <?php if ($emprestimosAtrasados > 0): ?>
                        <span class="badge bg-danger"><?= $emprestimosAtrasados ?> atrasado(s)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Valor Total -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="fas fa-dollar-sign fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold lh-1"><?= formatarMoeda($valorTotal) ?></div>
                    <div class="text-muted small">Valor do Estoque</div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ========== ALERTAS CRÍTICOS ========== -->
<?php if ($itensCriticos > 0): ?>
<div class="alert alert-danger border-danger d-flex align-items-start gap-3 mb-4" role="alert">
    <i class="fas fa-exclamation-triangle fa-lg mt-1 flex-shrink-0"></i>
    <div>
        <strong>Atenção! <?= $itensCriticos ?> item(s) com estoque crítico.</strong>
        Verifique a lista abaixo e providencie a reposição.
        <a href="<?= BASE_URL ?>/pages/itens/listar.php?filtro=critico" class="alert-link ms-2">Ver todos</a>
    </div>
</div>
<?php endif; ?>

<?php if ($emprestimosAtrasados > 0): ?>
<div class="alert alert-warning border-warning d-flex align-items-start gap-3 mb-4" role="alert">
    <i class="fas fa-clock fa-lg mt-1 flex-shrink-0"></i>
    <div>
        <strong>Atenção! <?= $emprestimosAtrasados ?> empréstimo(s) com devolução em atraso.</strong>
        <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php?filtro=atrasado" class="alert-link ms-2">Ver empréstimos atrasados</a>
    </div>
</div>
<?php endif; ?>

<!-- ========== LINHA COM TABELAS ========== -->
<div class="row g-4">

    <!-- Itens críticos -->
    <?php if (!empty($listaCriticos)): ?>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-circle me-2"></i>Estoque Crítico</span>
                <a href="<?= BASE_URL ?>/pages/itens/listar.php?filtro=critico" class="badge bg-white text-danger text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Atual</th>
                                <th class="text-center">Mínimo</th>
                                <th>Localização</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listaCriticos as $item): ?>
                            <tr class="<?= $item['quantidade_atual'] == 0 ? 'table-danger' : 'table-warning' ?>">
                                <td>
                                    <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $item['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <br><small class="text-muted"><?= htmlspecialchars($item['categoria'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-center fw-bold <?= $item['quantidade_atual'] == 0 ? 'text-danger' : 'text-warning' ?>">
                                    <?= $item['quantidade_atual'] ?>
                                </td>
                                <td class="text-center text-muted"><?= $item['quantidade_minima'] ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($item['localizacao'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Últimas movimentações -->
    <div class="col-lg-<?= !empty($listaCriticos) ? '6' : '8 mx-auto' ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2"></i>Últimas Movimentações</span>
                <a href="<?= BASE_URL ?>/pages/movimentacoes/listar.php" class="badge bg-white text-secondary text-decoration-none">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasMovs)): ?>
                <p class="text-muted text-center py-4 mb-0">Nenhuma movimentação registrada.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Tipo</th>
                                <th class="text-center">Qtd</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasMovs as $mov): ?>
                            <tr>
                                <td class="fw-semibold small"><?= htmlspecialchars($mov['item_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= getBadgeMovimentacao($mov['tipo']) ?></td>
                                <td class="text-center"><?= $mov['quantidade'] ?></td>
                                <td class="small text-muted"><?= formatarData($mov['data_movimentacao'], true) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Empréstimos atrasados -->
<?php if (!empty($listaAtrasados)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <span><i class="fas fa-clock me-2"></i>Empréstimos com Devolução em Atraso</span>
        <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php" class="badge bg-dark text-warning text-decoration-none">Ver todos</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Solicitante</th>
                        <th>Setor</th>
                        <th>Previsão de Devolução</th>
                        <th>Atraso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaAtrasados as $emp):
                        $dias = (int) (new DateTime())->diff(new DateTime($emp['previsao_devolucao']))->days;
                    ?>
                    <tr class="table-warning">
                        <td class="fw-semibold"><?= htmlspecialchars($emp['item_nome'],    ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($emp['solicitante'],  ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($emp['setor_destino'],ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-danger fw-bold"><?= formatarData($emp['previsao_devolucao']) ?></td>
                        <td><span class="badge bg-danger"><?= $dias ?> dia(s)</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
