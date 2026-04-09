<?php
/**
 * TI Stock - Gerador de PDF com TCPDF
 *
 * Parâmetros $_GET:
 *   tipo          → estoque | movimentacoes | critico | emprestimos
 *   categoria_id  → (opcional, somente para tipo=estoque)
 *   data_inicio   → (formato Y-m-d, somente para tipo=movimentacoes)
 *   data_fim      → (formato Y-m-d, somente para tipo=movimentacoes)
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

// ----------------------------------------
// Verifica se o TCPDF está instalado
// ----------------------------------------
$autoload = ROOT_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    setFlash('danger', 'A biblioteca TCPDF não está instalada. Execute <code>composer require tecnickcom/tcpdf</code> na raiz do projeto.');
    header('Location: ' . BASE_URL . '/pages/relatorios/index.php');
    exit;
}
require_once $autoload;

$tipo = $_GET['tipo'] ?? '';
$tiposValidos = ['estoque', 'movimentacoes', 'critico', 'emprestimos'];
if (!in_array($tipo, $tiposValidos, true)) {
    setFlash('danger', 'Tipo de relatório inválido.');
    header('Location: ' . BASE_URL . '/pages/relatorios/index.php');
    exit;
}

// ----------------------------------------
// Monta os dados conforme o tipo
// ----------------------------------------
$titulo = '';
$dados  = [];
$colunas = [];

switch ($tipo) {

    case 'estoque':
        $titulo = 'Posição Atual do Estoque';
        $catId  = (int)($_GET['categoria_id'] ?? 0);
        $where  = 'WHERE i.ativo = 1';
        $params = [];
        if ($catId > 0) {
            $where   .= ' AND i.categoria_id = ?';
            $params[] = $catId;
        }
        $stmt = $pdo->prepare(
            "SELECT i.nome, c.nome AS categoria, i.numero_patrimonio,
                    i.quantidade_atual, i.quantidade_minima, i.valor_unitario, i.localizacao
             FROM itens i JOIN categorias c ON c.id = i.categoria_id
             {$where} ORDER BY c.nome, i.nome"
        );
        $stmt->execute($params);
        $dados   = $stmt->fetchAll();
        $colunas = ['Nome', 'Categoria', 'Patrimônio', 'Qtd Atual', 'Qtd Mín.', 'Valor Unit.', 'Localização'];
        break;

    case 'movimentacoes':
        $titulo     = 'Movimentações por Período';
        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim    = $_GET['data_fim']    ?? date('Y-m-d');
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(m.data_movimentacao,'%d/%m/%Y %H:%i') AS data_hora,
                    i.nome AS item, m.tipo, m.motivo, m.quantidade, m.responsavel, m.observacoes
             FROM movimentacoes m JOIN itens i ON i.id = m.item_id
             WHERE DATE(m.data_movimentacao) BETWEEN ? AND ?
             ORDER BY m.data_movimentacao"
        );
        $stmt->execute([$dataInicio, $dataFim]);
        $dados   = $stmt->fetchAll();
        $titulo .= sprintf(' (%s a %s)',
            date('d/m/Y', strtotime($dataInicio)),
            date('d/m/Y', strtotime($dataFim))
        );
        $colunas = ['Data/Hora', 'Item', 'Tipo', 'Motivo', 'Qtd', 'Responsável', 'Observações'];
        break;

    case 'critico':
        $titulo  = 'Itens em Estoque Crítico';
        $stmt    = $pdo->query(
            "SELECT i.nome, c.nome AS categoria, i.quantidade_atual, i.quantidade_minima,
                    i.localizacao, i.fornecedor
             FROM itens i JOIN categorias c ON c.id = i.categoria_id
             WHERE i.ativo = 1 AND i.quantidade_atual <= i.quantidade_minima
             ORDER BY i.quantidade_atual ASC"
        );
        $dados   = $stmt->fetchAll();
        $colunas = ['Nome', 'Categoria', 'Qtd Atual', 'Qtd Mín.', 'Localização', 'Fornecedor'];
        break;

    case 'emprestimos':
        $titulo  = 'Itens Emprestados';
        $stmt    = $pdo->query(
            "SELECT i.nome AS item, e.quantidade, e.solicitante, e.setor_destino,
                    DATE_FORMAT(e.data_saida,'%d/%m/%Y') AS saida,
                    DATE_FORMAT(e.previsao_devolucao,'%d/%m/%Y') AS previsao,
                    e.status
             FROM emprestimos e JOIN itens i ON i.id = e.item_id
             WHERE e.status IN ('ativo','atrasado')
             ORDER BY e.previsao_devolucao ASC"
        );
        $dados   = $stmt->fetchAll();
        $colunas = ['Item', 'Qtd', 'Solicitante', 'Setor', 'Saída', 'Prev. Devolução', 'Status'];
        break;
}

// ----------------------------------------
// Geração do PDF com TCPDF
// ----------------------------------------

$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor($_SESSION['usuario_nome'] ?? APP_NAME);
$pdf->SetTitle($titulo);
$pdf->SetSubject($titulo);

// Desabilita cabeçalho e rodapé padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(10, 12, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

// Fonte
$pdf->SetFont('helvetica', 'B', 14);

// Cabeçalho do relatório
$pdf->Cell(0, 8, APP_NAME . ' – ' . APP_SUBTITLE, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, $titulo, 0, 1, 'C');
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Gerado em ' . date('d/m/Y H:i') . ' por ' . ($_SESSION['usuario_nome'] ?? ''), 0, 1, 'C');
$pdf->Ln(4);

// Tabela — Cabeçalho
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(52, 73, 120);
$pdf->SetTextColor(255, 255, 255);

$nCols     = count($colunas);
$pageWidth = $pdf->getPageWidth() - 20; // margem 10 + 10
$colWidth  = $pageWidth / $nCols;

foreach ($colunas as $col) {
    $pdf->Cell($colWidth, 7, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// Tabela — Linhas de dados
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$linha = 0;

foreach ($dados as $row) {
    $linha++;
    $pdf->SetFillColor($linha % 2 === 0 ? 240 : 255, $linha % 2 === 0 ? 240 : 255, $linha % 2 === 0 ? 240 : 255);

    $values = array_values($row);
    foreach ($values as $idx => $val) {
        // Formata campos monetários (somente relatório de estoque)
        if ($tipo === 'estoque' && $idx === 5) {
            $val = 'R$ ' . number_format((float)$val, 2, ',', '.');
        }
        $pdf->Cell($colWidth, 6, (string)($val ?? '—'), 1, 0, 'L', true);
    }
    $pdf->Ln();
}

// Totalizador simples
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Total de registros: ' . count($dados), 0, 1, 'R');

// Saída do PDF
$nomeArquivo = 'tistock_' . $tipo . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($nomeArquivo, 'I');
exit;
