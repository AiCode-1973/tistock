<?php
/**
 * TI Stock - Visualizar Item
 * Exibe detalhes completos do item e histórico de movimentações.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Detalhes do Item';
$activePage = 'itens';

$id   = (int)($_GET['id'] ?? 0);
$item = getItem($pdo, $id);

if (!$item) {
    setFlash('danger', 'Item não encontrado.');
    header('Location: ' . BASE_URL . '/pages/itens/listar.php');
    exit;
}

// Busca categoria
$stmtCat = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
$stmtCat->execute([$item['categoria_id']]);
$categoriaNome = $stmtCat->fetchColumn() ?: '—';

// Histórico de movimentações
$stmtMovs = $pdo->prepare(
    "SELECT m.*, u.nome AS usuario_nome
     FROM movimentacoes m
     LEFT JOIN usuarios u ON u.id = m.usuario_id
     WHERE m.item_id = ?
     ORDER BY m.data_movimentacao DESC
     LIMIT 30"
);
$stmtMovs->execute([$id]);
$movimentacoes = $stmtMovs->fetchAll();

// Empréstimos do item
$stmtEmp = $pdo->prepare(
    "SELECT * FROM emprestimos WHERE item_id = ? ORDER BY data_saida DESC LIMIT 10"
);
$stmtEmp->execute([$id]);
$emprestimos = $stmtEmp->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">
        <i class="fas fa-box me-2 text-primary"></i>
        <?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>
    </h4>
    <div class="d-flex gap-2">
        <?php if (hasPermission('tecnico')): ?>
        <a href="<?= BASE_URL ?>/pages/itens/editar.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/pages/itens/listar.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<?php
$critico = $item['quantidade_atual'] <= $item['quantidade_minima'];
$zerado  = $item['quantidade_atual'] == 0;
if ($zerado):
?>
<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Item com estoque <strong>zerado</strong>. Providencie reposição imediata.</div>
<?php elseif ($critico): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Estoque <strong>abaixo do mínimo</strong>. Providencie reposição.</div>
<?php endif; ?>

<div class="row g-4">

    <!-- Dados do Item -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white"><i class="fas fa-info-circle me-2"></i>Dados do Item</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr><th class="w-40 text-muted">Categoria</th><td><span class="badge bg-secondary"><?= htmlspecialchars($categoriaNome, ENT_QUOTES, 'UTF-8') ?></span></td></tr>
                        <tr><th class="text-muted">Nº Série</th><td><?= htmlspecialchars($item['numero_serie'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><th class="text-muted">Nº Patrimônio</th><td><?= htmlspecialchars($item['numero_patrimonio'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><th class="text-muted">Fornecedor</th><td><?= htmlspecialchars($item['fornecedor'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><th class="text-muted">Data de Aquisição</th><td><?= formatarData($item['data_aquisicao']) ?></td></tr>
                        <tr><th class="text-muted">Valor Unitário</th><td class="fw-bold"><?= formatarMoeda((float)$item['valor_unitario']) ?></td></tr>
                        <tr><th class="text-muted">Localização</th><td><?= htmlspecialchars($item['localizacao'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><th class="text-muted">Cadastrado em</th><td><?= formatarData($item['criado_em'], true) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Estoque -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-header bg-<?= $zerado ? 'danger' : ($critico ? 'warning text-dark' : 'success') ?> text-white">
                <i class="fas fa-warehouse me-2"></i>Estoque
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="display-4 fw-bold <?= $zerado ? 'text-danger' : ($critico ? 'text-warning' : 'text-success') ?>">
                    <?= $item['quantidade_atual'] ?>
                </div>
                <div class="text-muted small mb-3">unidades disponíveis</div>
                <hr>
                <div class="text-muted small">Mínimo: <strong><?= $item['quantidade_minima'] ?></strong></div>
                <div class="text-muted small">Valor total: <strong><?= formatarMoeda((float)$item['valor_unitario'] * $item['quantidade_atual']) ?></strong></div>

                <?php if (hasPermission('tecnico')): ?>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <a href="<?= BASE_URL ?>/pages/movimentacoes/entrada.php?item_id=<?= $item['id'] ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Entrada
                    </a>
                    <a href="<?= BASE_URL ?>/pages/movimentacoes/saida.php?item_id=<?= $item['id'] ?>" class="btn btn-danger btn-sm">
                        <i class="fas fa-minus me-1"></i>Saída
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Descrição -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-secondary text-white"><i class="fas fa-sticky-note me-2"></i>Descrição</div>
            <div class="card-body">
                <p class="mb-0 text-muted">
                    <?= $item['descricao'] ? nl2br(htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8')) : '<em>Sem descrição cadastrada.</em>' ?>
                </p>
            </div>
        </div>
    </div>

</div>

<!-- Histórico de Movimentações -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span><i class="fas fa-history me-2"></i>Histórico de Movimentações</span>
        <small class="text-muted">Últimas 30</small>
    </div>
    <div class="card-body p-0">
        <?php if (empty($movimentacoes)): ?>
        <p class="text-muted text-center py-4 mb-0">Nenhuma movimentação registrada para este item.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
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
                        <td class="small"><?= formatarData($mov['data_movimentacao'], true) ?></td>
                        <td><?= getBadgeMovimentacao($mov['tipo']) ?></td>
                        <td><?= getLabelMotivo($mov['motivo']) ?></td>
                        <td class="text-center fw-bold"><?= $mov['quantidade'] ?></td>
                        <td class="small"><?= htmlspecialchars($mov['responsavel'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($mov['observacoes'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
