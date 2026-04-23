<?php
/**
 * TI Stock - Configurações do Recibo
 * Permite ao admin editar os textos do recibo.
 */

define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';
requireLogin();

// Apenas administradores podem acessar
if (!hasPermission('administrador')) {
    setFlash('danger', 'Acesso negado.');
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$pageTitle  = 'Configurar Recibo';
$activePage = 'admin_recibo';

// Processar salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $declaracao = $_POST['recibo_texto_declaracao'] ?? '';
    $compromisso = $_POST['recibo_texto_compromisso'] ?? '';

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'recibo_texto_declaracao'");
        $stmt->execute([$declaracao]);
        
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'recibo_texto_compromisso'");
        $stmt->execute([$compromisso]);

        $pdo->commit();
        setFlash('success', 'Configurações do recibo salvas com sucesso!');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Erro ao salvar: ' . $e->getMessage());
    }
}

// Buscar configurações atuais
$configs = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'recibo_texto%'")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2 text-primary"></i>Configurar Template do Recibo</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Use as tags <b>{solicitante}</b> e <b>{setor}</b> para que o sistema substitua automaticamente pelos dados reais no momento da geração.
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Texto da Declaração (Início)</label>
                        <textarea name="recibo_texto_declaracao" class="form-control" rows="5" required><?= htmlspecialchars($configs['recibo_texto_declaracao'] ?? '') ?></textarea>
                        <small class="text-muted">Exemplo: Eu, {solicitante}, do setor {setor}, recebi os materiais...</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Texto de Compromisso (Fim)</label>
                        <textarea name="recibo_texto_compromisso" class="form-control" rows="5" required><?= htmlspecialchars($configs['recibo_texto_compromisso'] ?? '') ?></textarea>
                        <small class="text-muted">Exemplo: Comprometo-me a zelar pelo material...</small>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Salvar Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
