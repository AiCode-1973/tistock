<?php
/**
 * TI Stock - Gerar Recibo de Entrega de Material (PDF)
 * Suporta geração via Empréstimo (GET id) ou Avulso (POST).
 * Agora utiliza templates dinâmicos salvos no banco de dados.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    die("Erro: O autoload do Composer não foi encontrado. Execute 'composer install'.");
}

require_once ROOT_PATH . '/vendor/autoload.php';

// --- BUSCAR CONFIGURAÇÕES DO RECIBO ---
$configs = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'recibo_texto%'")->fetchAll(PDO::FETCH_KEY_PAIR);
$txtDeclaracao = $configs['recibo_texto_declaracao'] ?? 'Eu, {solicitante}, lotado(a) no setor {setor}, declaro para os devidos fins que recebi do Setor de TI o(s) material(is) abaixo relacionado...';
$txtCompromisso = $configs['recibo_texto_compromisso'] ?? 'Comprometo-me a zelar pela guarda e conservação do referido material...';

$dados = null;
$item_id = 0;

// MODO AVULSO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avulso'])) {
    $item_id = (int)$_POST['item_id'];
    $dados = [
        'id'                => 0,
        'solicitante'       => $_POST['solicitante'] ?? '',
        'setor_destino'     => $_POST['setor_destino'] ?? '',
        'quantidade'        => (int)($_POST['quantidade'] ?? 1),
        'observacoes'       => $_POST['observacoes'] ?? '',
        'tecnico_nome'      => $_SESSION['usuario_nome'] ?? 'Setor de TI'
    ];
} 
// MODO EMPRÉSTIMO (GET)
else {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) die("ID inválido.");

    $stmt = $pdo->prepare(
        "SELECT e.*, i.id AS item_id, i.nome AS item_nome, i.numero_serie, i.numero_patrimonio, 
                u.nome AS tecnico_nome
         FROM emprestimos e
         JOIN itens i ON i.id = e.item_id
         LEFT JOIN usuarios u ON u.id = e.usuario_id
         WHERE e.id = ? LIMIT 1"
    );
    $stmt->execute([id]);
    $dados = $stmt->fetch();
    if ($dados) $item_id = $dados['item_id'];
}

if (!$dados || $item_id <= 0) {
    die("Dados insuficientes para gerar o recibo.");
}

// Se for avulso, buscar dados do item
if (!isset($dados['item_nome'])) {
    $stmtItem = $pdo->prepare("SELECT nome, numero_serie, numero_patrimonio FROM itens WHERE id = ?");
    $stmtItem->execute([$item_id]);
    $item = $stmtItem->fetch();
    if ($item) {
        $dados['item_nome'] = $item['nome'];
        $dados['numero_serie'] = $item['numero_serie'];
        $dados['numero_patrimonio'] = $item['numero_patrimonio'];
    } else {
        die("Item não encontrado.");
    }
}

// --- PROCESSAR TAGS NO TEMPLATE ---
$mapaTags = [
    '{solicitante}' => $dados['solicitante'],
    '{setor}'       => $dados['setor_destino']
];
$txtDeclaracaoFinal = strtr($txtDeclaracao, $mapaTags);
$txtCompromissoFinal = strtr($txtCompromisso, $mapaTags);

// Configurações do PDF
class MYPDF extends TCPDF {
    public function Header() {
        $logo_file = ROOT_PATH . '/assets/img/logo.png';
        if (file_exists($logo_file)) {
            $this->Image($logo_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $this->SetY(15);
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 5, APP_NAME, 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Setor de Tecnologia da Informação', 0, 1, 'C');
        $this->Cell(0, 5, 'RECIBO DE ENTREGA DE MATERIAL', 0, 1, 'C');
        $this->Ln(5);
        $this->Line(15, 35, 195, 35);
    }
    public function Footer() {
        $this->SetY(-25);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Gerado em ' . date('d/m/Y H:i:s') . ' por ' . APP_NAME, 0, 0, 'L');
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('Recibo de Entrega');
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

$html = '
<br><br>
<p style="text-align: justify;">' . nl2br(htmlspecialchars($txtDeclaracaoFinal)) . '</p>

<table cellpadding="5" border="1" style="width: 100%;">
    <tr style="background-color: #f2f2f2; font-weight: bold;">
        <th width="10%">Qtd.</th>
        <th width="45%">Descrição do Item</th>
        <th width="25%">Patrimônio</th>
        <th width="20%">Nº Série</th>
    </tr>
    <tr>
        <td width="10%" style="text-align: center;">' . $dados['quantidade'] . '</td>
        <td width="45%">' . htmlspecialchars($dados['item_nome']) . '</td>
        <td width="25%">' . htmlspecialchars($dados['numero_patrimonio'] ?? 'N/A') . '</td>
        <td width="20%">' . htmlspecialchars($dados['numero_serie'] ?? 'N/A') . '</td>
    </tr>
</table>

<p style="text-align: justify; font-size: 10pt;">
    <b>Observações:</b> ' . htmlspecialchars($dados['observacoes'] ?? 'Nenhuma.') . '
</p>

<p style="text-align: justify;">' . nl2br(htmlspecialchars($txtCompromissoFinal)) . '</p>

<br><br>
<p style="text-align: right;">' . CIDADE_ESTADO . ', ' . date('d') . ' de ' . getMesExtenso(date('m')) . ' de ' . date('Y') . '.</p>

<br><br><br><br>

<table style="width: 100%;">
    <tr>
        <td style="width: 45%; border-top: 1px solid black; text-align: center;">
            <br>
            <b>' . htmlspecialchars($dados['solicitante']) . '</b><br>
            Responsável pelo Recebimento
        </td>
        <td style="width: 10%;"></td>
        <td style="width: 45%; border-top: 1px solid black; text-align: center;">
            <br>
            <b>' . htmlspecialchars($dados['tecnico_nome'] ?? 'Setor de TI') . '</b><br>
            Responsável pela Entrega
        </td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('recibo_entrega.pdf', 'I');
