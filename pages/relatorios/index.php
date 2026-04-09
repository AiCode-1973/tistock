<?php
/**
 * TI Stock - Página de Relatórios
 * Seleciona o tipo de relatório e os parâmetros para geração em PDF.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$pageTitle  = 'Relatórios';
$activePage = 'relatorios';

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-file-pdf me-2 text-danger"></i>Relatórios</h4>
</div>

<div class="row g-4">

    <!-- Posição do Estoque -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-boxes me-2"></i>Posição Atual do Estoque
            </div>
            <div class="card-body">
                <p class="text-muted small">Gera um PDF com todos os itens, quantidades atuais, valores e status de estoque.</p>
                <form method="GET" action="<?= BASE_URL ?>/pages/relatorios/gerar.php">
                    <input type="hidden" name="tipo" value="estoque">
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Categoria (opcional)</label>
                        <select name="categoria_id" class="form-select form-select-sm">
                            <option value="">Todas as categorias</option>
                            <?php foreach (getCategorias($pdo) as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>Gerar PDF
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Movimentações por Período -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <i class="fas fa-exchange-alt me-2"></i>Movimentações por Período
            </div>
            <div class="card-body">
                <p class="text-muted small">Lista todas as entradas e saídas em um intervalo de datas.</p>
                <form method="GET" action="<?= BASE_URL ?>/pages/relatorios/gerar.php">
                    <input type="hidden" name="tipo" value="movimentacoes">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label form-label-sm">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control form-control-sm"
                                   value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm">Data Fim</label>
                            <input type="date" name="data_fim" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>Gerar PDF
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Estoque Crítico -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-triangle me-2"></i>Itens em Estoque Crítico
            </div>
            <div class="card-body">
                <p class="text-muted small">Relatório de todos os itens com quantidade igual ou abaixo do mínimo.</p>
                <a href="<?= BASE_URL ?>/pages/relatorios/gerar.php?tipo=critico" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf me-1"></i>Gerar PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Itens Emprestados -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-handshake me-2"></i>Itens Emprestados
            </div>
            <div class="card-body">
                <p class="text-muted small">Lista de todos os empréstimos ativos e em atraso, com detalhes do solicitante.</p>
                <a href="<?= BASE_URL ?>/pages/relatorios/gerar.php?tipo=emprestimos" class="btn btn-warning btn-sm">
                    <i class="fas fa-file-pdf me-1"></i>Gerar PDF
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Instruções TCPDF -->
<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Geração de PDF:</strong> O sistema utiliza a biblioteca <strong>TCPDF</strong>.
    Para ativar a geração de PDF, instale via Composer:
    <code class="ms-2">composer require tecnickcom/tcpdf</code>
    e certifique-se de que o arquivo <code>vendor/autoload.php</code> está disponível na raiz do projeto.
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
