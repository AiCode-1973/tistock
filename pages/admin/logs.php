<?php
/**
 * TI Stock - Log de Auditoria
 * Visualização de todas as ações realizadas por usuários.
 * Acesso exclusivo para administradores.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Log de Auditoria';
$activePage = 'logs';

// Filtros
$busca      = trim($_GET['busca']     ?? '');
$filtroAcao = trim($_GET['acao']      ?? '');
$filtroUser = trim($_GET['usuario']   ?? '');
$dataInicio = trim($_GET['data_ini']  ?? '');
$dataFim    = trim($_GET['data_fim']  ?? '');
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina  = 50;

// Lista de ações distintas para o filtro
$acoes = $pdo->query("SELECT DISTINCT acao FROM logs ORDER BY acao")->fetchAll(PDO::FETCH_COLUMN);

// Lista de usuários distintos para o filtro
$usuarios = $pdo->query("SELECT DISTINCT usuario_nome FROM logs WHERE usuario_nome != '' ORDER BY usuario_nome")->fetchAll(PDO::FETCH_COLUMN);

// Monta WHERE
$where  = "WHERE 1=1";
$params = [];

if ($busca !== '') {
    $where   .= " AND (l.descricao LIKE ? OR l.usuario_nome LIKE ? OR l.ip LIKE ?)";
    $termo    = "%{$busca}%";
    array_push($params, $termo, $termo, $termo);
}
if ($filtroAcao !== '') {
    $where   .= " AND l.acao = ?";
    $params[] = $filtroAcao;
}
if ($filtroUser !== '') {
    $where   .= " AND l.usuario_nome = ?";
    $params[] = $filtroUser;
}
if ($dataInicio !== '') {
    $where   .= " AND DATE(l.criado_em) >= ?";
    $params[] = $dataInicio;
}
if ($dataFim !== '') {
    $where   .= " AND DATE(l.criado_em) <= ?";
    $params[] = $dataFim;
}

// Total e paginação
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM logs l {$where}");
$stmtCount->execute($params);
$total     = (int) $stmtCount->fetchColumn();
$paginacao = paginar($total, $porPagina, $pagina);

// Registros
$stmt = $pdo->prepare(
    "SELECT l.* FROM logs l {$where}
     ORDER BY l.criado_em DESC
     LIMIT {$paginacao['por_pagina']} OFFSET {$paginacao['offset']}"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Mapa visual por ação
$acaoConfig = [
    'login'                => ['icon' => 'fa-sign-in-alt',      'color' => 'success'],
    'login_falhou'         => ['icon' => 'fa-times-circle',     'color' => 'danger' ],
    'logout'               => ['icon' => 'fa-sign-out-alt',     'color' => 'secondary'],
    'entrada_estoque'      => ['icon' => 'fa-arrow-circle-down','color' => 'success'],
    'saida_estoque'        => ['icon' => 'fa-arrow-circle-up',  'color' => 'danger' ],
    'emprestimo_novo'      => ['icon' => 'fa-handshake',        'color' => 'primary'],
    'emprestimo_devolucao' => ['icon' => 'fa-undo',             'color' => 'info'   ],
    'item_cadastrado'      => ['icon' => 'fa-plus-circle',      'color' => 'primary'],
    'item_excluido'        => ['icon' => 'fa-trash',            'color' => 'danger' ],
    'sistema_zerado'       => ['icon' => 'fa-exclamation-triangle','color' => 'warning'],
];

$getAcaoConfig = function(string $acao) use ($acaoConfig): array {
    return $acaoConfig[$acao] ?? ['icon' => 'fa-circle', 'color' => 'secondary'];
};

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Log de Auditoria</h4>
    <?php if ($total > 0): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['exportar' => '1'])) ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-file-csv me-1"></i>Exportar CSV
    </a>
    <?php endif; ?>
</div>

<?php
// ---- Exportação CSV ----
if (isset($_GET['exportar'])) {
    $stmtExp = $pdo->prepare("SELECT l.* FROM logs l {$where} ORDER BY l.criado_em DESC");
    $stmtExp->execute($params);
    $todos = $stmtExp->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="logs_tistock_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Data/Hora', 'Usuário', 'Ação', 'Descrição', 'IP'], ';');
    foreach ($todos as $row) {
        fputcsv($out, [
            $row['id'],
            $row['criado_em'],
            $row['usuario_nome'],
            $row['acao'],
            $row['descricao'],
            $row['ip'],
        ], ';');
    }
    fclose($out);
    exit;
}
?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label form-label-sm text-muted">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Descrição, usuário ou IP...">
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">Ação</label>
                <select name="acao" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($acoes as $a): ?>
                    <option value="<?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>" <?= $filtroAcao === $a ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">Usuário</label>
                <select name="usuario" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= $filtroUser === $u ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">De</label>
                <input type="date" name="data_ini" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label form-label-sm text-muted">Até</label>
                <input type="date" name="data_fim" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-sm-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-search"></i></button>
                <a href="<?= BASE_URL ?>/pages/admin/logs.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small"><?= number_format($total, 0, ',', '.') ?> registro(s) encontrado(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
        <p class="text-muted text-center py-5 mb-0">Nenhum registro de log encontrado.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted" style="width:140px;">Data/Hora</th>
                        <th style="width:140px;">Usuário</th>
                        <th style="width:160px;">Ação</th>
                        <th>Descrição</th>
                        <th style="width:110px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log):
                        $cfg = $getAcaoConfig($log['acao']);
                    ?>
                    <tr>
                        <td class="small text-muted text-nowrap"><?= formatarData($log['criado_em'], true) ?></td>
                        <td class="small fw-semibold">
                            <?= htmlspecialchars($log['usuario_nome'] ?: '—', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $cfg['color'] ?> d-inline-flex align-items-center gap-1">
                                <i class="fas <?= $cfg['icon'] ?>"></i>
                                <?= htmlspecialchars($log['acao'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="small"><?= htmlspecialchars($log['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted text-nowrap"><?= htmlspecialchars($log['ip'], ENT_QUOTES, 'UTF-8') ?></td>
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
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginacao['anterior']])) ?>">Anterior</a>
                    </li>
                    <li class="page-item <?= $paginacao['pagina_atual'] >= $paginacao['total_paginas'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $paginacao['proximo']])) ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
