<?php
/**
 * TI Stock - Listar Usuários
 * Requer nível administrador.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requirePermission('administrador');

$pageTitle  = 'Gerenciar Usuários';
$activePage = 'usuarios';

$stmt    = $pdo->query(
    "SELECT id, nome, email, nivel, ativo, ultimo_acesso, criado_em FROM usuarios ORDER BY nome"
);
$usuarios = $stmt->fetchAll();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-users-cog me-2 text-primary"></i>Gerenciar Usuários</h4>
    <a href="<?= BASE_URL ?>/pages/usuarios/novo.php" class="btn btn-primary btn-sm">
        <i class="fas fa-user-plus me-1"></i>Novo Usuário
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Nível</th>
                        <th>Status</th>
                        <th>Último Acesso</th>
                        <th>Cadastro</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($u['nome'],  ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= getBadgeNivel($u['nivel']) ?></td>
                        <td>
                            <?php if ($u['ativo']): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= formatarData($u['ultimo_acesso'], true) ?></td>
                        <td class="small text-muted"><?= formatarData($u['criado_em']) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/pages/usuarios/editar.php?id=<?= $u['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                <a href="<?= BASE_URL ?>/pages/usuarios/toggle.php?id=<?= $u['id'] ?>"
                                   class="btn btn-outline-<?= $u['ativo'] ? 'warning' : 'success' ?>"
                                   title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                    <i class="fas fa-<?= $u['ativo'] ? 'ban' : 'check' ?>"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
