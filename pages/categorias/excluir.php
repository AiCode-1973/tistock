<?php
/**
 * TI Stock - Excluir Categoria
 * Só permite excluir se não houver itens vinculados.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
    exit;
}

// Verifica se a categoria existe
$stmt = $pdo->prepare("SELECT nome FROM categorias WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    setFlash('danger', 'Categoria não encontrada.');
    header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
    exit;
}

// Verifica se há itens vinculados
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria_id = ? AND ativo = 1");
$stmt2->execute([$id]);
$totalItens = (int) $stmt2->fetchColumn();

if ($totalItens > 0) {
    setFlash('danger', "Não é possível excluir a categoria \"{$categoria['nome']}\": há {$totalItens} item(ns) vinculado(s).");
    header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
    exit;
}

$pdo->prepare("DELETE FROM categorias WHERE id = ?")->execute([$id]);

setFlash('success', "Categoria \"{$categoria['nome']}\" excluída com sucesso!");
header('Location: ' . BASE_URL . '/pages/categorias/listar.php');
exit;
