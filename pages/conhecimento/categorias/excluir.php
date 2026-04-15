<?php
/**
 * TI Stock - Base de Conhecimento - Excluir Categoria KB
 * Só permite excluir se não houver artigos vinculados.
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/categorias/listar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT nome FROM kb_categorias WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    setFlash('danger', 'Categoria não encontrada.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/categorias/listar.php');
    exit;
}

$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM kb_artigos WHERE categoria_id = ? AND ativo = 1");
$stmt2->execute([$id]);
$totalArtigos = (int) $stmt2->fetchColumn();

if ($totalArtigos > 0) {
    setFlash('danger', "Não é possível excluir a categoria \"{$categoria['nome']}\": há {$totalArtigos} artigo(s) vinculado(s).");
    header('Location: ' . BASE_URL . '/pages/conhecimento/categorias/listar.php');
    exit;
}

$pdo->prepare("DELETE FROM kb_categorias WHERE id = ?")->execute([$id]);

setFlash('success', "Categoria \"{$categoria['nome']}\" excluída com sucesso!");
header('Location: ' . BASE_URL . '/pages/conhecimento/categorias/listar.php');
exit;
