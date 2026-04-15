<?php
/**
 * TI Stock - POPs - Listar Procedimentos Operacionais Padrão
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'POPs — Procedimentos Operacionais Padrão';
$activePage = 'pops';

$filtroStatus = $_GET['status'] ?? '';
$busca        = trim($_GET['busca'] ?? '');

$where  = "WHERE 1=1";
$params = [];

if (in_array($filtroStatus, ['ativo', 'revisao', 'obsoleto'], true)) {
    $where   .= " AND p.status = ?";
    $params[] = $filtroStatus;
}
if ($busca !== '') {
    $where   .= " AND (p.codigo LIKE ? OR p.titulo LIKE ? OR p.objetivo LIKE ?)";
    $termo    = "%{$busca}%";
    array_push($params, $termo, $termo, $termo);
}

$stmt = $pdo->prepare(
    "SELECT p.*, u.nome AS autor_nome
     FROM kb_pops p
     LEFT JOIN usuarios u ON u.id = p.autor_id
     {$where}
     ORDER BY p.codigo ASC"
);
$stmt->execute($params);
$pops = $stmt->fetchAll();

require_once ROOT_PATH . '/includes/header.php';

function badgeStatus(string $status): string {
    return match($status) {
        'ativo'    => '<span class="badge bg-success">Ativo</span>',
        'revisao'  => '<span class="badge bg-warning text-dark">Em Revisão</span>',
        'obsoleto' => '<span class="badge bg-secondary">Obsoleto</span>',
        default    => '<span class="badge bg-secondary">?</span>',
    };
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">
        <i class="fas fa-clipboard-check me-2 text-primary"></i>Procedimentos Operacionais Padrão
    </h4>
    <?php if (hasPermission('tecnico')): ?>
    <a href="<?= BASE_URL ?>/pages/conhecimento/pops/novo.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Novo POP
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
                       placeholder="Código, título ou objetivo...">
            </div>
            <div class="col-sm-3">
                <label class="form-label form-label-sm text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="ativo"    <?= $filtroStatus === 'ativo'    ? 'selected' : '' ?>>Ativo</option>
                    <option value="revisao"  <?= $filtroStatus === 'revisao'  ? 'selected' : '' ?>>Em Revisão</option>
                    <option value="obsoleto" <?= $filtroStatus === 'obsoleto' ? 'selected' : '' ?>>Obsoleto</option>
                </select>
            </div>
            <div class="col-sm-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-search me-1"></i>Buscar
                </button>
                <?php if ($busca !== '' || $filtroStatus !== ''): ?>
                <a href="<?= BASE_URL ?>/pages/conhecimento/pops/listar.php"
                   class="btn btn-outline-secondary btn-sm" title="Limpar">
                    <i class="fas fa-times"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <span class="text-muted small"><?= count($pops) ?> POP(s) encontrado(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pops)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-clipboard fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">Nenhum POP cadastrado.</p>
            <?php if (hasPermission('tecnico')): ?>
            <a href="<?= BASE_URL ?>/pages/conhecimento/pops/novo.php" class="btn btn-primary btn-sm mt-3">
                <i class="fas fa-plus me-1"></i>Criar primeiro POP
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Versão</th>
                        <th>Resp. Execução</th>
                        <th>Atualizado</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pops as $pop): ?>
                    <tr>
                        <td class="fw-semibold text-nowrap">
                            <a href="<?= BASE_URL ?>/pages/conhecimento/pops/visualizar.php?id=<?= $pop['id'] ?>"
                               class="text-decoration-none">
                                <?= htmlspecialchars($pop['codigo'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($pop['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($pop['versao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small"><?= htmlspecialchars($pop['responsavel_execucao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted text-nowrap"><?= formatarData($pop['atualizado_em']) ?></td>
                        <td class="text-center"><?= badgeStatus($pop['status']) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>/pages/conhecimento/pops/visualizar.php?id=<?= $pop['id'] ?>"
                               class="btn btn-outline-secondary btn-sm" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/pages/conhecimento/pops/gerar_pdf.php?id=<?= $pop['id'] ?>"
                               class="btn btn-outline-danger btn-sm" title="Baixar PDF" target="_blank">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <?php if (hasPermission('tecnico')): ?>
                            <a href="<?= BASE_URL ?>/pages/conhecimento/pops/editar.php?id=<?= $pop['id'] ?>"
                               class="btn btn-outline-primary btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
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

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
