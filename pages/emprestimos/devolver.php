<?php
/**
 * TI Stock - Registrar Devolução de Empréstimo
 * Requer nível técnico ou superior.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT e.*, i.nome AS item_nome FROM emprestimos e JOIN itens i ON i.id = e.item_id WHERE e.id = ?"
);
$stmt->execute([$id]);
$emprestimo = $stmt->fetch();

if (!$emprestimo) {
    setFlash('danger', 'Empréstimo não encontrado.');
    header('Location: ' . BASE_URL . '/pages/emprestimos/listar.php');
    exit;
}

if ($emprestimo['status'] === 'devolvido') {
    setFlash('warning', 'Este empréstimo já foi devolvido.');
    header('Location: ' . BASE_URL . '/pages/emprestimos/listar.php');
    exit;
}

// Processa a devolução
$pdo->beginTransaction();
try {
    $dataAgora = date('Y-m-d H:i:s');

    // Atualiza empréstimo
    $pdo->prepare(
        "UPDATE emprestimos SET status = 'devolvido', data_devolucao = ? WHERE id = ?"
    )->execute([$dataAgora, $id]);

    // Registra movimentação de entrada (devolução)
    $pdo->prepare(
        "INSERT INTO movimentacoes (item_id, tipo, motivo, quantidade, data_movimentacao, responsavel, observacoes, usuario_id)
         VALUES (?, 'entrada', 'devolucao', ?, ?, ?, ?, ?)"
    )->execute([
        $emprestimo['item_id'],
        $emprestimo['quantidade'],
        $dataAgora,
        $_SESSION['usuario_nome'],
        "Devolução do empréstimo de {$emprestimo['solicitante']} ({$emprestimo['setor_destino']})",
        $_SESSION['usuario_id'],
    ]);

    // Atualiza estoque
    $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual + ? WHERE id = ?")
        ->execute([$emprestimo['quantidade'], $emprestimo['item_id']]);

    $pdo->commit();

    registrarLog($pdo, 'emprestimo_devolucao', "Devolução de \"{$emprestimo['item_nome']}\" ({$emprestimo['quantidade']} un.) de {$emprestimo['solicitante']} — {$emprestimo['setor_destino']}");
    setFlash('success', "Devolução de \"{$emprestimo['item_nome']}\" registrada com sucesso!");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[TI Stock] Erro devolução: ' . $e->getMessage());
    setFlash('danger', 'Erro ao registrar devolução. Tente novamente.');
}

header('Location: ' . BASE_URL . '/pages/emprestimos/listar.php');
exit;
