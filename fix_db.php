<?php
define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/includes/init.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(50) PRIMARY KEY,
        valor TEXT,
        descricao VARCHAR(255),
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "Tabela 'configuracoes' verificada/criada com sucesso.<br>";

    $check = $pdo->query("SELECT COUNT(*) FROM configuracoes WHERE chave = 'recibo_texto_declaracao'")->fetchColumn();
    if (!$check) {
        $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, descricao) VALUES (?, ?, ?)");
        $stmt->execute([
            'recibo_texto_declaracao', 
            'Eu, {solicitante}, lotado(a) no setor {setor}, declaro para os devidos fins que recebi do Setor de TI o(s) material(is) abaixo relacionado(s) em perfeito estado de conservação e funcionamento:',
            'Texto principal da declaração do recibo'
        ]);
        $stmt->execute([
            'recibo_texto_compromisso',
            'Comprometo-me a zelar pela guarda e conservação do referido material, bem como utilizá-lo exclusivamente para fins profissionais, estando ciente de que deverei devolvê-lo nas mesmas condições em que foi recebido.',
            'Texto de compromisso do recibo'
        ]);
        echo "Dados iniciais inseridos com sucesso.<br>";
    } else {
        echo "Dados já existiam no banco.<br>";
    }

} catch (Exception $e) {
    echo "ERRO CRÍTICO: " . $e->getMessage();
}
unlink(__FILE__); // Deleta o próprio arquivo por segurança
?>
