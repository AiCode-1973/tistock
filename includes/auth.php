<?php
/**
 * TI Stock - Funções de Autenticação e Controle de Acesso
 *
 * Três níveis de permissão (do menor para o maior):
 *   consultor  → somente leitura
 *   tecnico    → registrar entradas e saídas
 *   administrador → acesso total
 */

/**
 * Verifica se existe uma sessão de usuário ativa.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Redireciona para login caso o usuário não esteja autenticado.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Verifica se o nível do usuário logado é suficiente para o acesso.
 * Hierarquia: consultor(1) < tecnico(2) < administrador(3)
 */
function hasPermission(string $nivelRequerido): bool
{
    if (!isLoggedIn()) {
        return false;
    }
    $hierarquia   = ['consultor' => 1, 'tecnico' => 2, 'administrador' => 3];
    $nivelUsuario = $_SESSION['nivel'] ?? 'consultor';

    return ($hierarquia[$nivelUsuario] ?? 0) >= ($hierarquia[$nivelRequerido] ?? 99);
}

/**
 * Exige nível mínimo; redireciona ao dashboard com mensagem de erro se insuficiente.
 */
function requirePermission(string $nivelRequerido): void
{
    requireLogin();
    if (!hasPermission($nivelRequerido)) {
        setFlash('danger', 'Acesso negado. Você não possui permissão para esta área.');
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

/**
 * Autentica o usuário e inicia a sessão.
 * Retorna true em caso de sucesso, false caso contrário.
 */
function loginUsuario(PDO $pdo, string $email, string $senha): bool
{
    $stmt = $pdo->prepare(
        "SELECT id, nome, email, senha, nivel FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1"
    );
    $stmt->execute([trim($email)]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Regenera o ID de sessão para prevenir session fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario_nome']  = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['nivel']         = $usuario['nivel'];

        // Registra data/hora do último acesso
        $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?")
            ->execute([$usuario['id']]);

        return true;
    }

    return false;
}

/**
 * Encerra a sessão do usuário com segurança.
 */
function logoutUsuario(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Retorna o rótulo em português do nível de acesso.
 */
function getNivelLabel(string $nivel): string
{
    return match ($nivel) {
        'administrador' => 'Administrador',
        'tecnico'       => 'Técnico',
        'consultor'     => 'Consultor',
        default         => 'Desconhecido',
    };
}
