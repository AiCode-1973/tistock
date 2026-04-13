<?php
/**
 * TI Stock - Zerar Sistema
 * Apaga todos os dados operacionais. Requer confirmação por frase digitada.
 * Acesso exclusivo para administradores.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Zerar Sistema';
$activePage = 'zerar';

$FRASE_CONFIRMACAO = 'CONFIRMO ZERAR O SISTEMA';
$erros   = [];
$sucesso = false;
$log     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $frase         = trim($_POST['frase_confirmacao'] ?? '');
    $zerarItens    = isset($_POST['zerar_itens']);
    $zerarMovs     = isset($_POST['zerar_movimentacoes']);
    $zerarEmps     = isset($_POST['zerar_emprestimos']);
    $zerarCats     = isset($_POST['zerar_categorias']);

    if ($frase !== $FRASE_CONFIRMACAO) {
        $erros[] = 'A frase de confirmação está incorreta. Nenhuma alteração foi feita.';
    }

    if (!$zerarItens && !$zerarMovs && !$zerarEmps && !$zerarCats) {
        $erros[] = 'Selecione ao menos uma opção para zerar.';
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            // A ordem importa por causa das FK
            if ($zerarEmps) {
                $n = $pdo->exec("DELETE FROM emprestimos");
                $log[] = "Empréstimos excluídos: {$n} registro(s).";
            }
            if ($zerarMovs) {
                $n = $pdo->exec("DELETE FROM movimentacoes");
                $log[] = "Movimentações excluídas: {$n} registro(s).";
            }
            if ($zerarItens) {
                $n = $pdo->exec("DELETE FROM itens");
                $log[] = "Itens excluídos: {$n} registro(s).";
            }
            if ($zerarCats) {
                // Só apaga categorias que não têm itens vinculados
                $n = $pdo->exec("DELETE FROM categorias WHERE id NOT IN (SELECT DISTINCT categoria_id FROM itens)");
                $log[] = "Categorias sem itens excluídas: {$n} registro(s).";
            }

            $pdo->commit();
            $sucesso = true;

            $tabelasZeradas = array_filter([
                $zerarEmps  ? 'Empréstimos' : null,
                $zerarMovs  ? 'Movimentações' : null,
                $zerarItens ? 'Itens' : null,
                $zerarCats  ? 'Categorias' : null,
            ]);
            registrarLog($pdo, 'sistema_zerado', 'Dados zerados: ' . implode(', ', $tabelasZeradas));

        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = 'Erro durante a operação: ' . $e->getMessage();
            error_log('[TI Stock] Erro ao zerar sistema: ' . $e->getMessage());
        }
    }
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-4">
    <h4 class="fw-bold mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Zerar Sistema</h4>
</div>

<?php if ($sucesso): ?>
<div class="alert alert-success">
    <h6 class="fw-bold"><i class="fas fa-check-circle me-2"></i>Operação concluída com sucesso.</h6>
    <ul class="mb-0 mt-2 ps-3">
        <?php foreach ($log as $linha): ?>
        <li class="small"><?= htmlspecialchars($linha, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-primary">
    <i class="fas fa-home me-1"></i>Ir ao Dashboard
</a>

<?php else: ?>

<?php if (!empty($erros)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($erros as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <div class="alert alert-warning d-flex gap-2">
            <i class="fas fa-triangle-exclamation fs-5 mt-1 flex-shrink-0"></i>
            <div>
                <strong>Atenção:</strong> Esta operação é <strong>irreversível</strong>.
                Os dados selecionados serão excluídos permanentemente do banco de dados.
                Faça um backup antes de prosseguir.
            </div>
        </div>

        <div class="card border-danger border-2 shadow-sm">
            <div class="card-header bg-danger text-white fw-semibold">
                <i class="fas fa-trash-alt me-2"></i>Selecione o que deseja zerar
            </div>
            <div class="card-body p-4">
                <form method="POST" action="" id="formZerar" novalidate>

                    <!-- Checkboxes -->
                    <div class="mb-4">
                        <p class="text-muted small mb-2">Marque os dados que deseja excluir permanentemente:</p>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="zerar_emprestimos" id="zerar_emprestimos" value="1">
                            <label class="form-check-label" for="zerar_emprestimos">
                                <i class="fas fa-handshake me-1 text-warning"></i>
                                <strong>Empréstimos</strong>
                                <span class="text-muted small">— todos os registros de empréstimos</span>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="zerar_movimentacoes" id="zerar_movimentacoes" value="1">
                            <label class="form-check-label" for="zerar_movimentacoes">
                                <i class="fas fa-exchange-alt me-1 text-info"></i>
                                <strong>Movimentações</strong>
                                <span class="text-muted small">— todo o histórico de entradas e saídas</span>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="zerar_itens" id="zerar_itens" value="1">
                            <label class="form-check-label" for="zerar_itens">
                                <i class="fas fa-box-open me-1 text-primary"></i>
                                <strong>Itens</strong>
                                <span class="text-muted small">— todos os itens do estoque</span>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="zerar_categorias" id="zerar_categorias" value="1">
                            <label class="form-check-label" for="zerar_categorias">
                                <i class="fas fa-tags me-1 text-secondary"></i>
                                <strong>Categorias</strong>
                                <span class="text-muted small">— apenas as que não possuem itens vinculados</span>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <!-- Frase de confirmação -->
                    <div class="mb-4">
                        <label for="frase_confirmacao" class="form-label fw-semibold">
                            Para confirmar, digite exatamente:
                            <code class="ms-1 text-danger"><?= $FRASE_CONFIRMACAO ?></code>
                        </label>
                        <input type="text" id="frase_confirmacao" name="frase_confirmacao"
                               class="form-control border-danger"
                               placeholder="Digite a frase de confirmação"
                               autocomplete="off">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger fw-semibold" id="btnZerar" disabled>
                            <i class="fas fa-trash-alt me-1"></i>Zerar Dados Selecionados
                        </button>
                        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script>
const FRASE = <?= json_encode($FRASE_CONFIRMACAO) ?>;
const inputFrase = document.getElementById('frase_confirmacao');
const btnZerar   = document.getElementById('btnZerar');

function verificar() {
    btnZerar.disabled = inputFrase.value.trim() !== FRASE;
}

inputFrase.addEventListener('input', verificar);
</script>

<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
