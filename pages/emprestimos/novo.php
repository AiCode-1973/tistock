<?php
/**
 * TI Stock - Novo Empréstimo
 * Requer nível técnico ou superior.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$pageTitle  = 'Novo Empréstimo';
$activePage = 'emprestimo_novo';

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId      = (int)($_POST['item_id']    ?? 0);
    $qtd         = (int)($_POST['quantidade'] ?? 1);
    $solicitante = trim($_POST['solicitante'] ?? '');
    $setor       = trim($_POST['setor_destino'] ?? '');
    $dataSaida   = trim($_POST['data_saida']    ?? date('Y-m-d H:i:s'));
    $previsao    = trim($_POST['previsao_devolucao'] ?? '');
    $obs         = trim($_POST['observacoes'] ?? '');

    if ($itemId <= 0)         $erros[] = 'Selecione o item.';
    if ($qtd <= 0)            $erros[] = 'A quantidade deve ser maior que zero.';
    if (empty($solicitante))  $erros[] = 'Informe o nome do solicitante.';
    if (empty($setor))        $erros[] = 'Informe o setor de destino.';
    if (empty($previsao))     $erros[] = 'Informe a previsão de devolução.';
    if (!empty($previsao) && $previsao < date('Y-m-d'))
                               $erros[] = 'A previsão de devolução não pode ser uma data passada.';

    if (empty($erros)) {
        $item = getItem($pdo, $itemId);
        if (!$item) {
            $erros[] = 'Item não encontrado.';
        } elseif ($item['quantidade_atual'] < $qtd) {
            $erros[] = "Estoque insuficiente. Disponível: {$item['quantidade_atual']} unidade(s).";
        } else {
            $pdo->beginTransaction();
            try {
                // Registra o empréstimo
                $pdo->prepare(
                    "INSERT INTO emprestimos (item_id, quantidade, solicitante, setor_destino, data_saida, previsao_devolucao, observacoes, usuario_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([$itemId, $qtd, $solicitante, $setor, $dataSaida, $previsao, $obs ?: null, $_SESSION['usuario_id']]);

                // Registra movimentação de saída
                $pdo->prepare(
                    "INSERT INTO movimentacoes (item_id, tipo, motivo, quantidade, data_movimentacao, responsavel, observacoes, usuario_id)
                     VALUES (?, 'saida', 'emprestimo', ?, ?, ?, ?, ?)"
                )->execute([$itemId, $qtd, $dataSaida, $_SESSION['usuario_nome'],
                    "Empréstimo para {$solicitante} - {$setor}" . ($obs ? ". {$obs}" : ''),
                    $_SESSION['usuario_id']]);

                // Atualiza estoque
                $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual - ? WHERE id = ?")
                    ->execute([$qtd, $itemId]);

                $pdo->commit();

                registrarLog($pdo, 'emprestimo_novo', "Empréstimo de \"{$item['nome']}\" ({$qtd} un.) para {$solicitante} — {$setor}");
                setFlash('success', "Empréstimo de \"{$item['nome']}\" registrado com sucesso!");
                header('Location: ' . BASE_URL . '/pages/emprestimos/listar.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $erros[] = 'Erro ao registrar empréstimo. Tente novamente.';
                error_log('[TI Stock] Erro empréstimo: ' . $e->getMessage());
            }
        }
    }
}

$listaItens = $pdo->query("SELECT id, nome, quantidade_atual FROM itens WHERE ativo = 1 AND quantidade_atual > 0 ORDER BY nome")->fetchAll();
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Novo Empréstimo</h4>
    <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3"><?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-header bg-primary text-white"><i class="fas fa-handshake me-2"></i>Detalhes do Empréstimo</div>
    <div class="card-body p-4">
        <form method="POST">

            <div class="mb-3">
                <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                <select name="item_id" class="form-select" required id="selectItem">
                    <option value="">Selecione o item (apenas itens com estoque disponível)...</option>
                    <?php foreach ($listaItens as $it): ?>
                    <option value="<?= $it['id'] ?>" data-qtd="<?= $it['quantidade_atual'] ?>"
                        <?= (isset($_POST['item_id']) && $_POST['item_id'] == $it['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['nome'], ENT_QUOTES, 'UTF-8') ?> (Disponível: <?= $it['quantidade_atual'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Quantidade <span class="text-danger">*</span></label>
                    <input type="number" name="quantidade" class="form-control" min="1" required
                           value="<?= (int)($_POST['quantidade'] ?? 1) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Data/Hora de Saída <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="data_saida" class="form-control" required
                           value="<?= htmlspecialchars($_POST['data_saida'] ?? date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Previsão de Devolução <span class="text-danger">*</span></label>
                    <input type="date" name="previsao_devolucao" class="form-control" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['previsao_devolucao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Solicitante <span class="text-danger">*</span></label>
                    <input type="text" name="solicitante" class="form-control" required
                           value="<?= htmlspecialchars($_POST['solicitante'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nome completo do solicitante">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Setor de Destino <span class="text-danger">*</span></label>
                    <input type="text" name="setor_destino" class="form-control" required
                           value="<?= htmlspecialchars($_POST['setor_destino'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ex: UTI, Cardiologia, Radiologia">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Observações</label>
                <textarea name="observacoes" class="form-control" rows="2"
                          placeholder="Motivo do empréstimo, número do chamado..."><?= htmlspecialchars($_POST['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Registrar Empréstimo</button>
                <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
