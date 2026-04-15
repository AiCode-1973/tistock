<?php
/**
 * TI Stock - POPs - Tornar POP Obsoleto (soft delete)
 */

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT codigo, titulo FROM kb_pops WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$pop = $stmt->fetch();

if (!$pop) {
    setFlash('danger', 'POP não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
    exit;
}

$pdo->prepare("UPDATE kb_pops SET status = 'obsoleto' WHERE id = ?")->execute([$id]);

registrarLog($pdo, 'POP_OBSOLETO', "POP marcado como obsoleto: {$pop['codigo']} \"{$pop['titulo']}\" (ID {$id})");
setFlash('success', "POP \"{$pop['codigo']}\" marcado como obsoleto.");
header('Location: ' . BASE_URL . '/pages/conhecimento/pops/listar.php');
exit;
