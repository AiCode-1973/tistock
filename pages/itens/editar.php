<?php
/**
 * TI Stock - Editar Item
 * Requer nível técnico ou superior.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$pageTitle  = 'Editar Item';
$activePage = 'itens';

$id   = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$item = getItem($pdo, $id);

if (!$item) {
    setFlash('danger', 'Item não encontrado.');
    header('Location: ' . BASE_URL . '/pages/itens/listar.php');
    exit;
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome'               => trim($_POST['nome']              ?? ''),
        'categoria_id'       => (int)($_POST['categoria_id']     ?? 0),
        'numero_serie'       => trim($_POST['numero_serie']      ?? ''),
        'numero_patrimonio'  => trim($_POST['numero_patrimonio'] ?? ''),
        'fornecedor'         => trim($_POST['fornecedor']        ?? ''),
        'data_aquisicao'     => trim($_POST['data_aquisicao']    ?? ''),
        'valor_unitario'     => str_replace(',', '.', trim($_POST['valor_unitario'] ?? '0')),
        'quantidade_minima'  => (int)($_POST['quantidade_minima'] ?? 1),
        'localizacao'        => trim($_POST['localizacao']        ?? ''),
        'descricao'          => trim($_POST['descricao']          ?? ''),
    ];

    if (empty($dados['nome']))            $erros[] = 'O nome do item é obrigatório.';
    if ($dados['categoria_id'] <= 0)     $erros[] = 'Selecione uma categoria.';
    if (!is_numeric($dados['valor_unitario']) || (float)$dados['valor_unitario'] < 0)
                                          $erros[] = 'Valor unitário inválido.';
    if ($dados['quantidade_minima'] < 0) $erros[] = 'A quantidade mínima não pode ser negativa.';

    if (empty($erros)) {
        $pdo->prepare(
            "UPDATE itens SET nome=?, categoria_id=?, numero_serie=?, numero_patrimonio=?,
             fornecedor=?, data_aquisicao=?, valor_unitario=?, quantidade_minima=?,
             localizacao=?, descricao=?
             WHERE id=?"
        )->execute([
            $dados['nome'],
            $dados['categoria_id'],
            $dados['numero_serie']      ?: null,
            $dados['numero_patrimonio'] ?: null,
            $dados['fornecedor']        ?: null,
            $dados['data_aquisicao']    ?: null,
            (float)$dados['valor_unitario'],
            $dados['quantidade_minima'],
            $dados['localizacao']       ?: null,
            $dados['descricao']         ?: null,
            $id,
        ]);

        setFlash('success', 'Item atualizado com sucesso!');
        header('Location: ' . BASE_URL . '/pages/itens/visualizar.php?id=' . $id);
        exit;
    }

    // Preserva os dados nas variáveis em caso de erro
    $item = array_merge($item, $dados);
}

$categorias = getCategorias($pdo);
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2 text-primary"></i>Editar Item</h4>
    <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<?php if (!empty($erros)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($erros as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nome do Item <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                    <select name="categoria_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $item['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Número de Série</label>
                    <input type="text" name="numero_serie" class="form-control"
                           value="<?= htmlspecialchars($item['numero_serie'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nº de Patrimônio</label>
                    <input type="text" name="numero_patrimonio" class="form-control"
                           value="<?= htmlspecialchars($item['numero_patrimonio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fornecedor</label>
                    <input type="text" name="fornecedor" class="form-control"
                           value="<?= htmlspecialchars($item['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Data de Aquisição</label>
                    <input type="date" name="data_aquisicao" class="form-control"
                           value="<?= htmlspecialchars($item['data_aquisicao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Valor Unitário (R$)</label>
                    <input type="number" name="valor_unitario" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars((string)$item['valor_unitario'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Quantidade Mínima</label>
                    <input type="number" name="quantidade_minima" class="form-control" min="0"
                           value="<?= htmlspecialchars((string)$item['quantidade_minima'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Qtd Atual (somente leitura)</label>
                    <input type="text" class="form-control" disabled value="<?= $item['quantidade_atual'] ?>">
                    <div class="form-text">Altere via movimentações de estoque.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Localização</label>
                    <input type="text" name="localizacao" class="form-control"
                           value="<?= htmlspecialchars($item['localizacao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($item['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Alterações</button>
                <a href="<?= BASE_URL ?>/pages/itens/visualizar.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
