<?php
/**
 * TI Stock - Registrar Entrada de Itens
 * Requer nível técnico ou superior.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$pageTitle  = 'Registrar Entrada';
$activePage = 'mov_entrada';

$itemPreSelecionado = (int)($_GET['item_id'] ?? 0);
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId   = (int)($_POST['item_id']  ?? 0);
    $motivo   = $_POST['motivo']          ?? '';
    $qtd      = (int)($_POST['quantidade'] ?? 0);
    $resp     = trim($_POST['responsavel'] ?? '');
    $obs      = trim($_POST['observacoes'] ?? '');
    $dataHora = trim($_POST['data_movimentacao'] ?? date('Y-m-d H:i:s'));

    $motivosValidos = ['compra', 'doacao', 'devolucao'];

    if ($itemId <= 0)                       $erros[] = 'Selecione o item.';
    if (!in_array($motivo, $motivosValidos, true)) $erros[] = 'Selecione um motivo válido.';
    if ($qtd <= 0)                          $erros[] = 'A quantidade deve ser maior que zero.';
    if (empty($resp))                       $erros[] = 'Informe o responsável pela movimentação.';

    if (empty($erros)) {
        $item = getItem($pdo, $itemId);
        if (!$item) {
            $erros[] = 'Item não encontrado.';
        } else {
            // Inicia transação para garantir consistência
            $pdo->beginTransaction();
            try {
                // Insere movimentação
                $pdo->prepare(
                    "INSERT INTO movimentacoes (item_id, tipo, motivo, quantidade, data_movimentacao, responsavel, observacoes, usuario_id)
                     VALUES (?, 'entrada', ?, ?, ?, ?, ?, ?)"
                )->execute([$itemId, $motivo, $qtd, $dataHora, $resp, $obs ?: null, $_SESSION['usuario_id']]);

                // Atualiza quantidade do item
                $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual + ? WHERE id = ?")
                    ->execute([$qtd, $itemId]);

                $pdo->commit();

                setFlash('success', "Entrada de {$qtd} unidade(s) de \"{$item['nome']}\" registrada com sucesso!");
                header('Location: ' . BASE_URL . '/pages/movimentacoes/listar.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $erros[] = 'Erro ao registrar entrada. Tente novamente.';
                error_log('[TI Stock] Erro entrada: ' . $e->getMessage());
            }
        }
    }
}

$listaItens = $pdo->query("SELECT id, nome, numero_serie, numero_patrimonio, quantidade_atual FROM itens WHERE ativo = 1 ORDER BY nome")->fetchAll();
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-arrow-circle-down me-2 text-success"></i>Registrar Entrada</h4>
    <a href="<?= BASE_URL ?>/pages/movimentacoes/listar.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3"><?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-header bg-success text-white"><i class="fas fa-plus me-2"></i>Nova Entrada de Estoque</div>
    <div class="card-body p-4">
        <form method="POST">

            <div class="mb-3">
                <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>

                <!-- Campo de busca -->
                <div class="input-group mb-1">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="buscaItem" class="form-control"
                           placeholder="Buscar por nome, patrimônio ou nº série..."
                           autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary" id="btnLimparBusca" title="Limpar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="resultadoBusca" class="list-group shadow-sm mb-2" style="display:none;max-height:220px;overflow-y:auto;"></div>

                <select name="item_id" class="form-select" required id="selectItem">
                    <option value="">Selecione o item...</option>
                    <?php foreach ($listaItens as $it): ?>
                    <option value="<?= $it['id'] ?>"
                            data-qtd="<?= $it['quantidade_atual'] ?>"
                            data-nome="<?= htmlspecialchars(mb_strtolower($it['nome']), ENT_QUOTES, 'UTF-8') ?>"
                            data-serie="<?= htmlspecialchars(mb_strtolower($it['numero_serie'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-patrimonio="<?= htmlspecialchars(mb_strtolower($it['numero_patrimonio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        <?= (isset($_POST['item_id']) && $_POST['item_id'] == $it['id']) || ($itemPreSelecionado == $it['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['nome'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($it['numero_patrimonio']): ?>[Pat: <?= htmlspecialchars($it['numero_patrimonio'], ENT_QUOTES, 'UTF-8') ?>]<?php endif; ?>
                        <?php if ($it['numero_serie']): ?>[S/N: <?= htmlspecialchars($it['numero_serie'], ENT_QUOTES, 'UTF-8') ?>]<?php endif; ?>
                        (Atual: <?= $it['quantidade_atual'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text" id="infoItemSelecionado"></div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                    <select name="motivo" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="compra"    <?= ($_POST['motivo'] ?? '') === 'compra'    ? 'selected' : '' ?>>Compra</option>
                        <option value="doacao"    <?= ($_POST['motivo'] ?? '') === 'doacao'    ? 'selected' : '' ?>>Doação</option>
                        <option value="devolucao" <?= ($_POST['motivo'] ?? '') === 'devolucao' ? 'selected' : '' ?>>Devolução</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Quantidade <span class="text-danger">*</span></label>
                    <input type="number" name="quantidade" class="form-control" min="1" required
                           value="<?= (int)($_POST['quantidade'] ?? 1) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Data/Hora <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="data_movimentacao" class="form-control" required
                           value="<?= htmlspecialchars($_POST['data_movimentacao'] ?? date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Responsável <span class="text-danger">*</span></label>
                <input type="text" name="responsavel" class="form-control" required
                       value="<?= htmlspecialchars($_POST['responsavel'] ?? $_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Nome de quem está realizando a entrada">
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Observações</label>
                <textarea name="observacoes" class="form-control" rows="2"
                          placeholder="NF, doador, contexto da entrada..."><?= htmlspecialchars($_POST['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Registrar Entrada</button>
                <a href="<?= BASE_URL ?>/pages/movimentacoes/listar.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const buscaInput   = document.getElementById('buscaItem');
    const selectItem   = document.getElementById('selectItem');
    const resultado    = document.getElementById('resultadoBusca');
    const infoItem     = document.getElementById('infoItemSelecionado');
    const btnLimpar    = document.getElementById('btnLimparBusca');
    const opcoes       = Array.from(selectItem.querySelectorAll('option[value]:not([value=""])'));

    function normalizarTexto(t) {
        return t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function atualizarInfo() {
        const sel = selectItem.options[selectItem.selectedIndex];
        if (!sel || !sel.value) { infoItem.textContent = ''; return; }
        const qtd = sel.dataset.qtd;
        infoItem.innerHTML = `<i class="fas fa-boxes me-1 text-secondary"></i>Estoque atual: <strong>${qtd}</strong> unidade(s)`;
    }

    function mostrarResultados(termo) {
        resultado.innerHTML = '';
        if (!termo) { resultado.style.display = 'none'; return; }

        const termoNorm = normalizarTexto(termo);
        const filtradas = opcoes.filter(op =>
            normalizarTexto(op.dataset.nome       || '').includes(termoNorm) ||
            normalizarTexto(op.dataset.serie      || '').includes(termoNorm) ||
            normalizarTexto(op.dataset.patrimonio || '').includes(termoNorm)
        );

        if (!filtradas.length) {
            resultado.innerHTML = '<div class="list-group-item text-muted small py-2">Nenhum item encontrado.</div>';
            resultado.style.display = 'block';
            return;
        }

        filtradas.slice(0, 30).forEach(op => {
            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'list-group-item list-group-item-action py-2 px-3 small';

            const serie      = op.dataset.serie      ? `<span class="text-muted ms-2">S/N: ${op.dataset.serie}</span>` : '';
            const patrimonio = op.dataset.patrimonio ? `<span class="text-muted ms-2">Pat: ${op.dataset.patrimonio}</span>` : '';
            btn.innerHTML = `<strong>${escapeHtml(op.textContent.trim().split('(Atual')[0].trim())}</strong>${patrimonio}${serie}
                             <span class="badge bg-secondary float-end">Qtd: ${op.dataset.qtd}</span>`;

            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectItem.value = op.value;
                buscaInput.value = op.textContent.trim().split('(Atual')[0].trim();
                resultado.style.display = 'none';
                atualizarInfo();
            });
            resultado.appendChild(btn);
        });

        resultado.style.display = 'block';
    }

    function escapeHtml(t) {
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    buscaInput.addEventListener('input',  () => mostrarResultados(buscaInput.value.trim()));
    buscaInput.addEventListener('blur',   () => setTimeout(() => { resultado.style.display = 'none'; }, 150));
    buscaInput.addEventListener('focus',  () => { if (buscaInput.value.trim()) mostrarResultados(buscaInput.value.trim()); });

    btnLimpar.addEventListener('click', () => {
        buscaInput.value       = '';
        selectItem.value       = '';
        resultado.style.display = 'none';
        infoItem.textContent   = '';
        buscaInput.focus();
    });

    selectItem.addEventListener('change', atualizarInfo);

    // Se já há item pré-selecionado, exibe info
    atualizarInfo();
})();
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
