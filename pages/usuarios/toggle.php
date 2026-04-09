<?php
/**
 * TI Stock - Ativar/Desativar Usuário
 * Requer nível administrador. Impede desativar a si próprio.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$id = (int)($_GET['id'] ?? 0);

if ($id === (int)$_SESSION['usuario_id']) {
    setFlash('danger', 'Você não pode desativar sua própria conta.');
    header('Location: ' . BASE_URL . '/pages/usuarios/listar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, ativo, nome FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    setFlash('danger', 'Usuário não encontrado.');
    header('Location: ' . BASE_URL . '/pages/usuarios/listar.php');
    exit;
}

$novoStatus = $usuario['ativo'] ? 0 : 1;
$pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?")->execute([$novoStatus, $id]);

$acao = $novoStatus ? 'ativado' : 'desativado';
setFlash('success', "Usuário \"{$usuario['nome']}\" {$acao} com sucesso.");
header('Location: ' . BASE_URL . '/pages/usuarios/listar.php');
exit;
