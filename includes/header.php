<?php
/**
 * TI Stock - Cabeçalho HTML
 *
 * Variáveis esperadas (definidas antes deste include):
 *   $pageTitle  → título da página
 *   $activePage → identificador para highlight do menu ativo
 */

// Garante autenticação
requireLogin();

$pageTitle  = $pageTitle  ?? 'Painel';
$activePage = $activePage ?? '';

// Dados para os badges do topbar
$qtdCriticos    = contarItensCriticos($pdo);
$qtdEmprestimos = contarEmprestimosAtivos($pdo);
atualizarEmprestimosAtrasados($pdo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= APP_NAME ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(ROOT_PATH . '/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">

<div id="wrapper" class="d-flex">

    <!-- ===== SIDEBAR ===== -->
    <?php require_once ROOT_PATH . '/includes/sidebar.php'; ?>

    <!-- ===== CONTEÚDO PRINCIPAL ===== -->
    <div id="main-content" class="flex-grow-1 overflow-hidden">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
            <!-- Botão toggle da sidebar -->
            <button class="btn btn-sm btn-light me-3" id="sidebarToggle" title="Recolher menu">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Título da página atual -->
            <span class="fw-semibold text-secondary d-none d-md-inline">
                <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <div class="ms-auto d-flex align-items-center gap-2">

                <!-- Alerta: itens críticos -->
                <?php if ($qtdCriticos > 0): ?>
                <a href="<?= BASE_URL ?>/pages/itens/listar.php?filtro=critico"
                   class="btn btn-sm btn-outline-warning position-relative" title="<?= $qtdCriticos ?> item(s) em estoque crítico">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $qtdCriticos ?>
                    </span>
                </a>
                <?php endif; ?>

                <!-- Alerta: empréstimos ativos -->
                <?php if ($qtdEmprestimos > 0): ?>
                <a href="<?= BASE_URL ?>/pages/emprestimos/listar.php"
                   class="btn btn-sm btn-outline-info position-relative" title="<?= $qtdEmprestimos ?> empréstimo(s) ativo(s)">
                    <i class="fas fa-handshake"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                        <?= $qtdEmprestimos ?>
                    </span>
                </a>
                <?php endif; ?>

                <!-- Menu do usuário -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1 text-primary"></i>
                        <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                <?= htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </li>
                        <li>
                            <span class="dropdown-item-text">
                                <?= getBadgeNivel($_SESSION['nivel'] ?? 'consultor') ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Área de conteúdo da página -->
        <div class="content-body p-4">
            <?php flashMessage(); ?>
