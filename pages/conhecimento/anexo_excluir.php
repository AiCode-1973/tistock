<?php
/**
 * TI Stock - Base de Conhecimento - Excluir Anexo Individual
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('tecnico');

$id       = (int)($_GET['id']     ?? 0);
$artigoId = (int)($_GET['artigo'] ?? 0);

if ($id <= 0 || $artigoId <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kb_anexos WHERE id = ? AND artigo_id = ? LIMIT 1");
$stmt->execute([$id, $artigoId]);
$anexo = $stmt->fetch();

if (!$anexo) {
    setFlash('danger', 'Anexo não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/editar.php?id=' . $artigoId);
    exit;
}

// Remove o arquivo físico
$caminho = ROOT_PATH . '/uploads/conhecimento/' . $anexo['nome_arquivo'];
if (is_file($caminho)) {
    unlink($caminho);
}

$pdo->prepare("DELETE FROM kb_anexos WHERE id = ?")->execute([$id]);

setFlash('success', 'Anexo "' . htmlspecialchars($anexo['nome_original'], ENT_QUOTES, 'UTF-8') . '" removido.');
header('Location: ' . BASE_URL . '/pages/conhecimento/editar.php?id=' . $artigoId);
exit;
