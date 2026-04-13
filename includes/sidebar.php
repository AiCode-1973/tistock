<?php
/**
 * TI Stock - Sidebar de Navegação
 *
 * Menu lateral responsivo com Bootstrap 5.
 * Destaca o item ativo conforme $activePage.
 */

// Atalho: verifica se um item de menu está ativo
$isActive = fn(string $page): string => ($activePage === $page) ? 'active' : '';
?>

<nav id="sidebar" class="d-flex flex-column" style="min-height:100vh;background-color:#1e2a38;">

    <!-- Logo / Título do sistema -->
    <div class="sidebar-brand d-flex align-items-center px-3 py-3" style="border-bottom:1px solid rgba(255,255,255,.1);">
        <i class="fas fa-server me-2 fs-4" style="color:#4d8ef0;"></i>
        <div>
            <div class="fw-bold lh-1" style="color:#ffffff;"><?= APP_NAME ?></div>
            <small style="font-size:.72rem;color:#8fa3b8;">Setor de TI</small>
        </div>
    </div>

    <!-- Navegação -->
    <div class="sidebar-nav flex-grow-1 pt-2" style="overflow-y:auto;">
        <ul class="nav flex-column px-2">

            <!-- Dashboard -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/dashboard.php"
                   class="sidebar-link rounded <?= $isActive('dashboard') ?>">
                    <i class="fas fa-tachometer-alt me-2 fa-fw"></i>Dashboard
                </a>
            </li>

            <!-- ---- Itens ---- -->
            <li class="nav-item mt-2">
                <span class="sidebar-section-label px-3">Estoque</span>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/itens/listar.php"
                   class="sidebar-link rounded <?= $isActive('itens') ?>">
                    <i class="fas fa-box-open me-2 fa-fw"></i>Itens
                </a>
            </li>

            <?php if (hasPermission('tecnico')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/itens/cadastrar.php"
                   class="sidebar-link rounded ps-4 <?= $isActive('itens_novo') ?>">
                    <i class="fas fa-plus-circle me-2 fa-fw"></i>Novo Item
                </a>
            </li>
            <?php endif; ?>

            <!-- ---- Movimentações ---- -->
            <li class="nav-item mt-2">
                <span class="sidebar-section-label px-3">Movimentações</span>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/movimentacoes/listar.php"
                   class="sidebar-link rounded <?= $isActive('movimentacoes') ?>">
                    <i class="fas fa-exchange-alt me-2 fa-fw"></i>Histórico
                </a>
            </li>

            <?php if (hasPermission('tecnico')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/movimentacoes/entrada.php"
                   class="sidebar-link rounded ps-4 <?= $isActive('mov_entrada') ?>">
                    <i class="fas fa-arrow-circle-down me-2 fa-fw" style="color:#28a745;"></i>Registrar Entrada
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/movimentacoes/saida.php"
                   class="sidebar-link rounded ps-4 <?= $isActive('mov_saida') ?>">
                    <i class="fas fa-arrow-circle-up me-2 fa-fw" style="color:#dc3545;"></i>Registrar Saída
                </a>
            </li>
            <?php endif; ?>

            <!-- ---- Empréstimos ---- -->
            <li class="nav-item mt-2">
                <span class="sidebar-section-label px-3">Empréstimos</span>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php"
                   class="sidebar-link rounded <?= $isActive('emprestimos') ?>">
                    <i class="fas fa-handshake me-2 fa-fw"></i>Empréstimos
                    <?php
                    $atrasados = (int)($pdo->query(
                        "SELECT COUNT(*) FROM emprestimos WHERE status = 'atrasado'"
                    )->fetchColumn());
                    if ($atrasados > 0):
                    ?>
                    <span class="badge bg-danger ms-1"><?= $atrasados ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <?php if (hasPermission('tecnico')): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/emprestimos/novo.php"
                   class="sidebar-link rounded ps-4 <?= $isActive('emprestimo_novo') ?>">
                    <i class="fas fa-plus-circle me-2 fa-fw"></i>Novo Empréstimo
                </a>
            </li>
            <?php endif; ?>

            <!-- ---- Relatórios ---- -->
            <li class="nav-item mt-2">
                <span class="sidebar-section-label px-3">Relatórios</span>
            </li>

            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/relatorios/index.php"
                   class="sidebar-link rounded <?= $isActive('relatorios') ?>">
                    <i class="fas fa-file-pdf me-2 fa-fw"></i>Relatórios
                </a>
            </li>

            <!-- ---- Documentos ---- -->
            <li class="nav-item mt-2">
                <span class="sidebar-section-label px-3">Documentos</span>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/images/<?= rawurlencode('POP — Controle de Estoque de TI no Sistema TIStock.docx') ?>"
                   class="sidebar-link rounded <?= $isActive('pop') ?>"
                   target="_blank" download="POP-TIStock.docx">
                    <i class="fas fa-file-word me-2 fa-fw" style="color:#2b9af3;"></i>POP TIStock
                </a>
            </li>

            <!-- ---- Administração ---- -->
            <?php if (hasPermission('administrador')): ?>
            <li class="nav-item mt-2">
                <span class="sidebar-section-label px-3">Administração</span>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/categorias/listar.php"
                   class="sidebar-link rounded <?= $isActive('categorias') ?>">
                    <i class="fas fa-tags me-2 fa-fw"></i>Categorias
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/usuarios/listar.php"
                   class="sidebar-link rounded <?= $isActive('usuarios') ?>">
                    <i class="fas fa-users-cog me-2 fa-fw"></i>Usuários
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/admin/logs.php"
                   class="sidebar-link rounded <?= $isActive('logs') ?>">
                    <i class="fas fa-clipboard-list me-2 fa-fw"></i>Log de Auditoria
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/admin/zerar.php"
                   class="sidebar-link rounded <?= $isActive('zerar') ?>"
                   style="color:#ff6b6b !important;">
                    <i class="fas fa-trash-alt me-2 fa-fw"></i>Zerar Sistema
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>

    <!-- Rodapé da sidebar -->
    <div class="px-3 py-2" style="border-top:1px solid rgba(255,255,255,.1);">
        <small style="color:#8fa3b8;"><?= APP_NAME ?> v<?= APP_VERSION ?></small>
    </div>
</nav>
