<?php
/**
 * TI Stock - Excluir Item (desativação lógica)
 * Requer nível administrador.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$id   = (int)($_GET['id'] ?? 0);
$item = getItem($pdo, $id);

if (!$item) {
    setFlash('danger', 'Item não encontrado.');
    header('Location: ' . BASE_URL . '/pages/itens/listar.php');
    exit;
}

// Desativação lógica: preserva o histórico
$pdo->prepare("UPDATE itens SET ativo = 0 WHERE id = ?")->execute([$id]);

registrarLog($pdo, 'item_excluido', "Item desativado: \"{$item['nome']}\" (ID {$id})");
setFlash('success', 'Item "' . htmlspecialchars($item['nome']) . '" removido do estoque.');
header('Location: ' . BASE_URL . '/pages/itens/listar.php');
exit;
