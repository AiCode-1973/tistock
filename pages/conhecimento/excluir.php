<?php
/**
 * TI Stock - Base de Conhecimento - Excluir Artigo (soft delete)
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT titulo FROM kb_artigos WHERE id = ? AND ativo = 1 LIMIT 1");
$stmt->execute([$id]);
$artigo = $stmt->fetch();

if (!$artigo) {
    setFlash('danger', 'Artigo não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$pdo->prepare("UPDATE kb_artigos SET ativo = 0 WHERE id = ?")->execute([$id]);

registrarLog($pdo, 'KB_EXCLUIR', "Artigo excluído: \"{$artigo['titulo']}\" (ID {$id})");
setFlash('success', "Artigo \"{$artigo['titulo']}\" excluído com sucesso!");
header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
exit;
