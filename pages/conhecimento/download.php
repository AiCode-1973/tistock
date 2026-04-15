<?php
/**
 * TI Stock - Base de Conhecimento - Download de Anexo
 * Serve o arquivo de forma segura, sem expor o caminho real no disco.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

// Busca o anexo e verifica que o artigo está ativo
$stmt = $pdo->prepare(
    "SELECT a.nome_original, a.nome_arquivo, a.mime_type, a.tamanho
     FROM kb_anexos a
     JOIN kb_artigos art ON art.id = a.artigo_id AND art.ativo = 1
     WHERE a.id = ?
     LIMIT 1"
);
$stmt->execute([$id]);
$anexo = $stmt->fetch();

if (!$anexo) {
    setFlash('danger', 'Anexo não encontrado.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

$caminho = ROOT_PATH . '/uploads/conhecimento/' . $anexo['nome_arquivo'];

if (!is_file($caminho)) {
    setFlash('danger', 'Arquivo não encontrado no servidor.');
    header('Location: ' . BASE_URL . '/pages/conhecimento/index.php');
    exit;
}

// Envia o arquivo
$nomeDownload = $anexo['nome_original'];
header('Content-Type: ' . $anexo['mime_type']);
header('Content-Disposition: attachment; filename="' . rawurlencode($nomeDownload) . '"');
header('Content-Length: ' . $anexo['tamanho']);
header('Cache-Control: private, no-cache');
header('X-Content-Type-Options: nosniff');
readfile($caminho);
exit;
