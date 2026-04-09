<?php
/**
 * TI Stock - Funções Auxiliares
 *
 * Funções reutilizáveis de formatação, alertas e consultas comuns.
 */

// ----------------------------------------
// Formatação
// ----------------------------------------

/** Formata valor monetário em Real brasileiro. */
function formatarMoeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/** Formata data/datetime para o padrão brasileiro. */
function formatarData(?string $data, bool $comHora = false): string
{
    if (empty($data) || str_starts_with($data, '0000')) {
        return '—';
    }
    try {
        $dt = new DateTime($data);
        return $comHora ? $dt->format('d/m/Y H:i') : $dt->format('d/m/Y');
    } catch (Exception) {
        return '—';
    }
}

// ----------------------------------------
// Mensagens Flash (feedback para o usuário)
// ----------------------------------------

/** Define uma mensagem flash para ser exibida na próxima requisição. */
function setFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash_type'] = $tipo;
    $_SESSION['flash_msg']  = $mensagem;
}

/**
 * Exibe e limpa a mensagem flash da sessão.
 * Deve ser chamada uma vez dentro do layout da página.
 */
function flashMessage(): void
{
    if (!empty($_SESSION['flash_msg'])) {
        $tipo = htmlspecialchars($_SESSION['flash_type'] ?? 'info', ENT_QUOTES, 'UTF-8');
        $msg  = htmlspecialchars($_SESSION['flash_msg'],             ENT_QUOTES, 'UTF-8');
        $icone = match ($tipo) {
            'success' => 'fa-check-circle',
            'danger'  => 'fa-times-circle',
            'warning' => 'fa-exclamation-triangle',
            default   => 'fa-info-circle',
        };
        echo <<<HTML
        <div class="alert alert-{$tipo} alert-dismissible fade show" role="alert">
            <i class="fas {$icone} me-2"></i>{$msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        HTML;
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    }
}

// ----------------------------------------
// Badges e Rótulos
// ----------------------------------------

/** Retorna o badge HTML para status de empréstimo. */
function getBadgeEmprestimo(string $status): string
{
    return match ($status) {
        'ativo'     => '<span class="badge bg-primary">Ativo</span>',
        'devolvido' => '<span class="badge bg-success">Devolvido</span>',
        'atrasado'  => '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>Atrasado</span>',
        default     => '<span class="badge bg-secondary">?</span>',
    };
}

/** Retorna badge para tipo de movimentação. */
function getBadgeMovimentacao(string $tipo): string
{
    return match ($tipo) {
        'entrada' => '<span class="badge bg-success"><i class="fas fa-arrow-circle-down me-1"></i>Entrada</span>',
        'saida'   => '<span class="badge bg-danger"><i class="fas fa-arrow-circle-up me-1"></i>Saída</span>',
        default   => '<span class="badge bg-secondary">?</span>',
    };
}

/** Retorna o rótulo legível do motivo de movimentação. */
function getLabelMotivo(string $motivo): string
{
    $motivos = [
        'compra'     => 'Compra',
        'doacao'     => 'Doação',
        'devolucao'  => 'Devolução',
        'emprestimo' => 'Empréstimo',
        'manutencao' => 'Manutenção',
        'descarte'   => 'Descarte',
        'alocacao'   => 'Alocação em Setor',
    ];
    return $motivos[$motivo] ?? ucfirst($motivo);
}

/** Retorna badge para nível de usuário. */
function getBadgeNivel(string $nivel): string
{
    return match ($nivel) {
        'administrador' => '<span class="badge bg-danger">Administrador</span>',
        'tecnico'       => '<span class="badge bg-warning text-dark">Técnico</span>',
        'consultor'     => '<span class="badge bg-info text-dark">Consultor</span>',
        default         => '<span class="badge bg-secondary">?</span>',
    };
}

// ----------------------------------------
// Consultas Frequentes
// ----------------------------------------

/** Conta itens em estoque igual ou abaixo do mínimo. */
function contarItensCriticos(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM itens WHERE quantidade_atual <= quantidade_minima AND ativo = 1"
    )->fetchColumn();
}

/** Conta empréstimos com status ativo ou atrasado. */
function contarEmprestimosAtivos(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM emprestimos WHERE status IN ('ativo','atrasado')"
    )->fetchColumn();
}

/**
 * Atualiza automaticamente para 'atrasado' os empréstimos vencidos.
 * Deve ser chamada no carregamento das páginas principais.
 */
function atualizarEmprestimosAtrasados(PDO $pdo): void
{
    $pdo->exec(
        "UPDATE emprestimos
         SET status = 'atrasado'
         WHERE status = 'ativo' AND previsao_devolucao < CURDATE()"
    );
}

/** Retorna array de categorias para uso em <select>. */
function getCategorias(PDO $pdo): array
{
    return $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
}

/** Busca item pelo ID; retorna false se não encontrado. */
function getItem(PDO $pdo, int $id): array|false
{
    $stmt = $pdo->prepare("SELECT * FROM itens WHERE id = ? AND ativo = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ----------------------------------------
// Paginação
// ----------------------------------------

/** Retorna array com dados de paginação. */
function paginar(int $total, int $porPagina, int $paginaAtual): array
{
    $totalPaginas = (int) ceil($total / max(1, $porPagina));
    return [
        'total'         => $total,
        'por_pagina'    => $porPagina,
        'pagina_atual'  => $paginaAtual,
        'total_paginas' => max(1, $totalPaginas),
        'offset'        => max(0, ($paginaAtual - 1) * $porPagina),
        'anterior'      => max(1, $paginaAtual - 1),
        'proximo'       => min($totalPaginas, $paginaAtual + 1),
    ];
}
