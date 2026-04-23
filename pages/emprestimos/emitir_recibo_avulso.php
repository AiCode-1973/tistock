<?php
/**
 * TI Stock - Emitir Recibo Avulso
 * Permite selecionar um item e preencher os dados do recebedor para gerar o recibo na hora.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Emitir Recibo de Entrega';
$activePage = 'emprestimos_recibos';

// Busca todos os itens ativos para o select
$stmtItens = $pdo->query("SELECT id, nome, numero_patrimonio, numero_serie FROM itens WHERE ativo = 1 ORDER BY nome ASC");
$itens = $stmtItens->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">
        <i class="fas fa-file-invoice me-2 text-primary"></i>Emitir Recibo de Entrega (Avulso)
    </h4>
    <a href="<?= BASE_URL ?>/pages/emprestimos/recibos.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-primary">Informações da Entrega</h6>
                <small class="text-muted">Preencha os dados abaixo para gerar o termo de entrega em PDF.</small>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASE_URL ?>/pages/emprestimos/gerar_recibo_entrega.php" method="POST" target="_blank">
                    <input type="hidden" name="avulso" value="1">
                    
                    <div class="row g-3">
                        <!-- Seleção do Item -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Item do Estoque</label>
                            <select name="item_id" class="form-select select2" required>
                                <option value="">Selecione um item...</option>
                                <?php foreach ($itens as $item): ?>
                                    <option value="<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['nome']) ?> 
                                        <?= $item['numero_patrimonio'] ? " (Pat: {$item['numero_patrimonio']})" : "" ?>
                                        <?= $item['numero_serie'] ? " (SN: {$item['numero_serie']})" : "" ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantidade</label>
                            <input type="number" name="quantidade" class="form-control" value="1" min="1" required>
                        </div>

                        <div class="col-md-12 mt-4">
                            <hr>
                            <h6 class="fw-bold text-dark">Dados do Recebedor</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome do Solicitante/Recebedor</label>
                            <input type="text" name="solicitante" class="form-control" placeholder="Ex: João Silva" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Setor de Destino</label>
                            <input type="text" name="setor_destino" class="form-control" placeholder="Ex: Administrativo" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Observações Adicionais (Opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Ex: Entrega para uso em home office..."></textarea>
                        </div>

                        <div class="col-12 pt-3">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="fas fa-file-pdf me-2"></i>GERAR RECIBO (PDF)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
