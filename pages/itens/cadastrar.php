<?php
/**
 * TI Stock - Cadastrar Novo Item
 * Requer nível técnico ou superior.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$pageTitle  = 'Cadastrar Item';
$activePage = 'itens_novo';

$erros = [];
$dados = [
    'nome'               => '',
    'categoria_id'       => '',
    'numero_serie'       => '',
    'numero_patrimonio'  => '',
    'fornecedor'         => '',
    'data_aquisicao'     => '',
    'valor_unitario'     => '',
    'quantidade_atual'   => '',
    'quantidade_minima'  => '1',
    'localizacao'        => '',
    'descricao'          => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e saneamento dos dados do formulário
    $dados = [
        'nome'               => trim($_POST['nome']              ?? ''),
        'categoria_id'       => (int)($_POST['categoria_id']     ?? 0),
        'numero_serie'       => trim($_POST['numero_serie']      ?? ''),
        'numero_patrimonio'  => trim($_POST['numero_patrimonio'] ?? ''),
        'fornecedor'         => trim($_POST['fornecedor']        ?? ''),
        'data_aquisicao'     => trim($_POST['data_aquisicao']    ?? ''),
        'valor_unitario'     => str_replace(',', '.', trim($_POST['valor_unitario'] ?? '0')),
        'quantidade_atual'   => (int)($_POST['quantidade_atual']  ?? 0),
        'quantidade_minima'  => (int)($_POST['quantidade_minima'] ?? 1),
        'localizacao'        => trim($_POST['localizacao']        ?? ''),
        'descricao'          => trim($_POST['descricao']          ?? ''),
    ];

    // Validações
    if (empty($dados['nome']))             $erros[] = 'O nome do item é obrigatório.';
    if ($dados['categoria_id'] <= 0)      $erros[] = 'Selecione uma categoria.';
    if (!is_numeric($dados['valor_unitario']) || (float)$dados['valor_unitario'] < 0)
                                           $erros[] = 'Informe um valor unitário válido.';
    if ($dados['quantidade_atual'] < 0)   $erros[] = 'A quantidade atual não pode ser negativa.';
    if ($dados['quantidade_minima'] < 0)  $erros[] = 'A quantidade mínima não pode ser negativa.';

    if (empty($erros)) {
        $stmt = $pdo->prepare(
            "INSERT INTO itens (nome, categoria_id, numero_serie, numero_patrimonio, fornecedor,
                                data_aquisicao, valor_unitario, quantidade_atual, quantidade_minima,
                                localizacao, descricao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $dados['nome'],
            $dados['categoria_id'],
            $dados['numero_serie']     ?: null,
            $dados['numero_patrimonio']?: null,
            $dados['fornecedor']       ?: null,
            $dados['data_aquisicao']   ?: null,
            (float)$dados['valor_unitario'],
            $dados['quantidade_atual'],
            $dados['quantidade_minima'],
            $dados['localizacao']      ?: null,
            $dados['descricao']        ?: null,
        ]);

        $novoId = $pdo->lastInsertId();

        // Registra movimentação de entrada inicial, se houver quantidade
        if ($dados['quantidade_atual'] > 0) {
            $pdo->prepare(
                "INSERT INTO movimentacoes (item_id, tipo, motivo, quantidade, responsavel, observacoes, usuario_id)
                 VALUES (?, 'entrada', 'compra', ?, ?, 'Cadastro inicial do item', ?)"
            )->execute([
                $novoId,
                $dados['quantidade_atual'],
                $_SESSION['usuario_nome'],
                $_SESSION['usuario_id'],
            ]);
        }

        setFlash('success', 'Item "' . htmlspecialchars($dados['nome']) . '" cadastrado com sucesso!');
        header('Location: ' . BASE_URL . '/pages/itens/listar.php');
        exit;
    }
}

$categorias = getCategorias($pdo);
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Cadastrar Novo Item</h4>
    <a href="<?= BASE_URL ?>/pages/itens/listar.php" class="btn btn-outline-secondary btn-sm">
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

            <div class="row g-3">

                <!-- Nome -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nome do Item <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" required
                           value="<?= htmlspecialchars($dados['nome'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ex: Switch 24 portas, Monitor 24'', Cabo UTP Cat6...">
                </div>

                <!-- Categoria -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                    <select name="categoria_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $dados['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Número de Série -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Número de Série</label>
                    <input type="text" name="numero_serie" class="form-control"
                           value="<?= htmlspecialchars($dados['numero_serie'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="S/N">
                </div>

                <!-- Número de Patrimônio -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nº de Patrimônio</label>
                    <input type="text" name="numero_patrimonio" class="form-control"
                           value="<?= htmlspecialchars($dados['numero_patrimonio'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Patrimônio hospitalar">
                </div>

                <!-- Fornecedor -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fornecedor</label>
                    <input type="text" name="fornecedor" class="form-control"
                           value="<?= htmlspecialchars($dados['fornecedor'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nome da empresa fornecedora">
                </div>

                <!-- Data de Aquisição -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Data de Aquisição</label>
                    <input type="date" name="data_aquisicao" class="form-control"
                           value="<?= htmlspecialchars($dados['data_aquisicao'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Valor Unitário -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Valor Unitário (R$) <span class="text-danger">*</span></label>
                    <input type="number" name="valor_unitario" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars($dados['valor_unitario'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="0.00">
                </div>

                <!-- Quantidade Atual -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Quantidade Atual <span class="text-danger">*</span></label>
                    <input type="number" name="quantidade_atual" class="form-control" min="0" required
                           value="<?= htmlspecialchars((string)$dados['quantidade_atual'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Quantidade Mínima -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Quantidade Mínima <span class="text-danger">*</span></label>
                    <input type="number" name="quantidade_minima" class="form-control" min="0" required
                           value="<?= htmlspecialchars((string)$dados['quantidade_minima'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-text">Alerta de estoque crítico abaixo deste valor.</div>
                </div>

                <!-- Localização -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Localização (Sala/Armário)</label>
                    <input type="text" name="localizacao" class="form-control"
                           value="<?= htmlspecialchars($dados['localizacao'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ex: Sala de TI, Armário 3, Prateleira B">
                </div>

                <!-- Descrição -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição / Observações</label>
                    <textarea name="descricao" class="form-control" rows="3"
                              placeholder="Informações adicionais sobre o item..."><?= htmlspecialchars($dados['descricao'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Salvar Item
                </button>
                <a href="<?= BASE_URL ?>/pages/itens/listar.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>

        </form>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
