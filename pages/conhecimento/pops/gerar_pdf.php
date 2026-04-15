<?php
/**
 * TI Stock - POPs - Gerador de PDF (TCPDF)
 * Gera o documento formal do POP com cabeçalho, metadados, seções e rodapé.
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'POP inválido.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, u.nome AS autor_nome
     FROM kb_pops p
     LEFT JOIN usuarios u ON u.id = p.autor_id
     WHERE p.id = ? LIMIT 1"
);
$stmt->execute([$id]);
$pop = $stmt->fetch();

if (!$pop) {
    setFlash('danger', 'POP não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$autoload = ROOT_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    setFlash('danger', 'Biblioteca TCPDF não encontrada.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}
require_once $autoload;

// -----------------------------------------------
// Converte HTML do Quill para texto simples com
// formatação mínima adequada ao TCPDF WriteHTML
// -----------------------------------------------
function limparHtmlQuill(string $html): string
{
    // Remove atributos style e class desnecessários mas mantém estrutura
    $html = preg_replace('/\s*(class|data-[^=]*)="[^"]*"/', '', $html);
    // Normaliza espaços extras
    $html = preg_replace('/\s{2,}/', ' ', $html);
    return trim($html);
}

$statusLabel = match($pop['status']) {
    'ativo'    => 'Ativo',
    'revisao'  => 'Em Revisão',
    'obsoleto' => 'Obsoleto',
    default    => $pop['status'],
};

// -----------------------------------------------
// Inicializa TCPDF
// -----------------------------------------------
$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor($pop['responsavel_elaboracao']);
$pdf->SetTitle($pop['codigo'] . ' — ' . $pop['titulo']);
$pdf->SetSubject('Procedimento Operacional Padrão');
$pdf->SetKeywords('POP, TI, procedimento');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

$largura = $pdf->getPageWidth() - 40; // margens 20+20

// -----------------------------------------------
// CABEÇALHO DO DOCUMENTO
// -----------------------------------------------
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(30, 42, 56);   // #1e2a38 — cor da sidebar
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($largura, 10, APP_NAME . ' — ' . APP_SUBTITLE, 0, 1, 'C', true);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(77, 142, 240);  // #4d8ef0
$pdf->Cell($largura, 8, 'PROCEDIMENTO OPERACIONAL PADRÃO', 0, 1, 'C', true);

$pdf->Ln(4);

// -----------------------------------------------
// TABELA DE METADADOS
// -----------------------------------------------
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 240, 245);
$pdf->SetTextColor(0, 0, 0);

$col1 = $largura * 0.2;
$col2 = $largura * 0.3;
$col3 = $largura * 0.2;
$col4 = $largura * 0.3;

$linhas = [
    ['Código',          $pop['codigo'],                  'Versão',         $pop['versao']],
    ['Título',          $pop['titulo'],                  'Status',         $statusLabel],
    ['Resp. Elaboração',$pop['responsavel_elaboracao'],  'Resp. Execução', $pop['responsavel_execucao']],
    ['Elaborado em',    formatarData($pop['criado_em']), 'Última revisão', formatarData($pop['atualizado_em'])],
];

foreach ($linhas as $linha) {
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(230, 234, 242);
    $pdf->Cell($col1, 7, $linha[0], 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col2, 7, $linha[1], 1, 0, 'L', true);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(230, 234, 242);
    $pdf->Cell($col3, 7, $linha[2], 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col4, 7, $linha[3], 1, 1, 'L', true);
}

$pdf->Ln(5);

// -----------------------------------------------
// Função auxiliar para título de seção
// -----------------------------------------------
function tituloPDFsecao(TCPDF $pdf, string $texto, float $largura): void
{
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(52, 73, 120);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($largura, 7, '  ' . mb_strtoupper($texto), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(1);
}

// -----------------------------------------------
// SEÇÃO: OBJETIVO
// -----------------------------------------------
tituloPDFsecao($pdf, '1. Objetivo', $largura);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($largura, 6, $pop['objetivo'], 0, 'J', false, 1);
$pdf->Ln(3);

// -----------------------------------------------
// SEÇÃO: ESCOPO
// -----------------------------------------------
tituloPDFsecao($pdf, '2. Escopo', $largura);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($largura, 6, $pop['escopo'], 0, 'J', false, 1);
$pdf->Ln(3);

// -----------------------------------------------
// SEÇÃO: RESPONSABILIDADES
// -----------------------------------------------
tituloPDFsecao($pdf, '3. Responsabilidades', $largura);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($largura, 6,
    "Elaboração: {$pop['responsavel_elaboracao']}\nExecução: {$pop['responsavel_execucao']}",
    0, 'L', false, 1);
$pdf->Ln(3);

// -----------------------------------------------
// SEÇÃO: PROCEDIMENTO (HTML do Quill)
// -----------------------------------------------
tituloPDFsecao($pdf, '4. Procedimento', $largura);
$pdf->SetFont('helvetica', '', 9);
$htmlProc = limparHtmlQuill($pop['procedimento']);
$pdf->writeHTML($htmlProc, true, false, true, false, '');
$pdf->Ln(3);

// -----------------------------------------------
// SEÇÃO: REFERÊNCIAS (opcional)
// -----------------------------------------------
if (!empty(trim($pop['referencias'] ?? ''))) {
    tituloPDFsecao($pdf, '5. Referências e Documentos Relacionados', $largura);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell($largura, 6, $pop['referencias'], 0, 'L', false, 1);
    $pdf->Ln(3);
}

// -----------------------------------------------
// RODAPÉ DO DOCUMENTO (bloco fixo no final)
// -----------------------------------------------
$pdf->Ln(6);
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetFillColor(240, 240, 245);
$pdf->SetTextColor(100, 100, 100);

$rodapeTexto = sprintf(
    '%s — v%s  |  Gerado em %s por %s  |  %s',
    $pop['codigo'],
    $pop['versao'],
    date('d/m/Y H:i'),
    $_SESSION['usuario_nome'] ?? APP_NAME,
    APP_NAME
);
$pdf->Cell($largura, 6, $rodapeTexto, 'T', 1, 'C', false);

// Número de páginas
$totalPaginas = $pdf->getNumPages();
for ($pg = 1; $pg <= $totalPaginas; $pg++) {
    $pdf->setPage($pg);
    $pdf->SetXY(20, $pdf->getPageHeight() - 12);
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell($largura, 5, 'Página ' . $pg . ' de ' . $totalPaginas, 0, 0, 'R');
}

// -----------------------------------------------
// Saída
// -----------------------------------------------
$nomeArquivo = 'POP_' . preg_replace('/[^A-Z0-9\-]/', '_', $pop['codigo']) . '_v' . $pop['versao'] . '.pdf';
$pdf->Output($nomeArquivo, 'I');
exit;
