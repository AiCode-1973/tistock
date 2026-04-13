<?php
/**
 * TI Stock - Recibo de Empréstimo
 * Página de impressão do recibo para um empréstimo específico.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/emprestimos/listar.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT e.*,
            i.nome          AS item_nome,
            i.numero_serie,
            i.numero_patrimonio,
            i.localizacao,
            c.nome          AS categoria_nome,
            u.nome          AS responsavel_nome
     FROM emprestimos e
     JOIN itens i       ON i.id = e.item_id
     JOIN categorias c  ON c.id = i.categoria_id
     LEFT JOIN usuarios u ON u.id = e.usuario_id
     WHERE e.id = ?
     LIMIT 1"
);
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) {
    header('Location: ' . BASE_URL . '/pages/emprestimos/listar.php');
    exit;
}

$statusLabels = [
    'ativo'     => 'Ativo',
    'atrasado'  => 'Atrasado',
    'devolvido' => 'Devolvido',
];
$statusColors = [
    'ativo'     => '#0d6efd',
    'atrasado'  => '#dc3545',
    'devolvido' => '#198754',
];
$logoPath = BASE_URL . '/images/hse.png';
$numero   = str_pad($emp['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Empréstimo #<?= $numero ?> | <?= APP_NAME ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #212529;
            background: #f4f4f4;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            margin: 20px auto;
            padding: 20mm 18mm 16mm;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 12px rgba(0,0,0,.12);
        }

        /* ---- Cabeçalho ---- */
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header img { height: 64px; width: auto; }
        .header-info { flex: 1; }
        .header-info h1 {
            font-size: 16px;
            font-weight: 700;
            color: #0d3365;
            margin-bottom: 2px;
        }
        .header-info p { font-size: 11px; color: #6c757d; }
        .header-number {
            text-align: right;
            min-width: 120px;
        }
        .header-number .label {
            font-size: 10px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .header-number .num {
            font-size: 20px;
            font-weight: 700;
            color: #0d6efd;
        }

        /* ---- Título do recibo ---- */
        .recibo-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #0d3365;
            text-align: center;
            margin-bottom: 14px;
        }

        /* ---- Grade de campos ---- */
        .grid { display: grid; gap: 8px 16px; margin-bottom: 10px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        .field { display: flex; flex-direction: column; }
        .field .flabel {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .field .fvalue {
            font-size: 12.5px;
            font-weight: 600;
            color: #212529;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 2px;
        }
        .field .fvalue.highlight { color: #0d6efd; }

        /* ---- Seção ---- */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #fff;
            background: #0d6efd;
            padding: 3px 8px;
            border-radius: 3px;
            margin: 12px 0 8px;
        }

        /* ---- Badge de status ---- */
        .badge-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }

        /* ---- Observações ---- */
        .obs-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 12px;
            color: #495057;
            min-height: 36px;
            margin-bottom: 12px;
        }

        /* ---- Assinaturas ---- */
        .assinaturas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 18px;
        }
        .assinatura-box { text-align: center; }
        .assinatura-box .linha {
            border-top: 1px solid #212529;
            margin-bottom: 4px;
            margin-top: 32px;
        }
        .assinatura-box .asn-label { font-size: 10.5px; color: #495057; }
        .assinatura-box .asn-name { font-size: 11.5px; font-weight: 600; }

        /* ---- Rodapé ---- */
        .footer {
            border-top: 1px solid #dee2e6;
            margin-top: 14px;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #adb5bd;
        }

        /* ---- Botões (não imprimem) ---- */
        .no-print {
            width: 210mm;
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
        }
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print:hover { background: #0b5ed7; }
        .btn-back:hover  { background: #5c636a; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .page {
                margin: 0;
                border: none;
                box-shadow: none;
                width: 100%;
                padding: 15mm 15mm;
            }
            @page { size: A4 portrait; margin: 0; }
        }
    </style>
</head>
<body>

<!-- Botões de ação (não aparecem na impressão) -->
<div class="no-print">
    <button class="btn-print" onclick="window.print()">
        🖨 Imprimir
    </button>
    <a class="btn-back" href="<?= BASE_URL ?>/pages/emprestimos/listar.php">
        ← Voltar
    </a>
</div>

<div class="page">

    <!-- Cabeçalho -->
    <div class="header">
        <img src="<?= $logoPath ?>" alt="Logo">
        <div class="header-info">
            <h1><?= APP_NAME ?> — Setor de TI</h1>
            <p>Controle de Empréstimos de Equipamentos</p>
        </div>
        <div class="header-number">
            <div class="label">Recibo Nº</div>
            <div class="num">#<?= $numero ?></div>
        </div>
    </div>

    <div class="recibo-title">Recibo de Empréstimo de Equipamento</div>

    <!-- Dados do Equipamento -->
    <div class="section-title">Equipamento</div>
    <div class="grid grid-2">
        <div class="field">
            <span class="flabel">Descrição do Item</span>
            <span class="fvalue highlight"><?= htmlspecialchars($emp['item_nome'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="field">
            <span class="flabel">Categoria</span>
            <span class="fvalue"><?= htmlspecialchars($emp['categoria_nome'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div class="grid grid-4" style="margin-top:8px;">
        <div class="field">
            <span class="flabel">Nº Série</span>
            <span class="fvalue"><?= htmlspecialchars($emp['numero_serie'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="field">
            <span class="flabel">Patrimônio</span>
            <span class="fvalue"><?= htmlspecialchars($emp['numero_patrimonio'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="field">
            <span class="flabel">Localização</span>
            <span class="fvalue"><?= htmlspecialchars($emp['localizacao'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="field">
            <span class="flabel">Quantidade</span>
            <span class="fvalue"><?= $emp['quantidade'] ?></span>
        </div>
    </div>

    <!-- Dados do Empréstimo -->
    <div class="section-title">Empréstimo</div>
    <div class="grid grid-2">
        <div class="field">
            <span class="flabel">Solicitante</span>
            <span class="fvalue"><?= htmlspecialchars($emp['solicitante'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="field">
            <span class="flabel">Setor de Destino</span>
            <span class="fvalue"><?= htmlspecialchars($emp['setor_destino'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div class="grid grid-3" style="margin-top:8px;">
        <div class="field">
            <span class="flabel">Data de Saída</span>
            <span class="fvalue"><?= formatarData($emp['data_saida'], true) ?></span>
        </div>
        <div class="field">
            <span class="flabel">Previsão de Devolução</span>
            <span class="fvalue"><?= formatarData($emp['previsao_devolucao']) ?></span>
        </div>
        <div class="field">
            <span class="flabel">Status</span>
            <span class="fvalue">
                <span class="badge-status" style="background:<?= $statusColors[$emp['status']] ?>;">
                    <?= $statusLabels[$emp['status']] ?>
                </span>
            </span>
        </div>
    </div>

    <?php if ($emp['data_devolucao']): ?>
    <div class="grid grid-2" style="margin-top:8px;">
        <div class="field">
            <span class="flabel">Data de Devolução Real</span>
            <span class="fvalue"><?= formatarData($emp['data_devolucao'], true) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Observações -->
    <div class="section-title">Observações</div>
    <div class="obs-box">
        <?= $emp['observacoes']
            ? nl2br(htmlspecialchars($emp['observacoes'], ENT_QUOTES, 'UTF-8'))
            : '<span style="color:#adb5bd;font-style:italic;">Nenhuma observação registrada.</span>' ?>
    </div>

    <!-- Assinaturas -->
    <div class="assinaturas">
        <div class="assinatura-box">
            <div class="linha"></div>
            <div class="asn-label">Responsável pelo Empréstimo (TI)</div>
            <div class="asn-name"><?= htmlspecialchars($emp['responsavel_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="assinatura-box">
            <div class="linha"></div>
            <div class="asn-label">Solicitante — Retirou o Equipamento</div>
            <div class="asn-name"><?= htmlspecialchars($emp['solicitante'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="assinatura-box">
            <div class="linha"></div>
            <div class="asn-label">Solicitante — Devolveu o Equipamento</div>
            <div class="asn-name">&nbsp;</div>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        <span><?= APP_NAME ?> v<?= APP_VERSION ?> — Gerado em <?= date('d/m/Y \à\s H:i') ?></span>
        <span>Recibo Nº <?= $numero ?></span>
    </div>

</div>

</body>
</html>
