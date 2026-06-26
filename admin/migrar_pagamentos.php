<?php
require_once __DIR__ . '/../app.php';

$db = Database::getInstance();

echo "<h2>Adicionando campos de pagamento...</h2>";

// Adicionar campos na tabela diarias
$sqls = [
    "ALTER TABLE diarias ADD COLUMN valor_empresa DECIMAL(10,2) NULL AFTER valor",
    "ALTER TABLE diarias ADD COLUMN taxa_agencia DECIMAL(5,2) DEFAULT 10.00 AFTER valor_empresa",
    "ALTER TABLE diarias ADD COLUMN pago_empresa TINYINT(1) DEFAULT 0",
    "ALTER TABLE diarias ADD COLUMN pago_empresa_at DATETIME NULL",
    "ALTER TABLE diarias ADD COLUMN forma_pagamento_empresa VARCHAR(20) DEFAULT 'pix'",
];

foreach ($sqls as $sql) {
    try {
        $db->query($sql);
        echo "<p style='color:green'>✓ " . substr($sql, 13, 50) . "...</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange'>⊙ Já existe: " . substr($sql, 13, 40) . "...</p>";
        } else {
            echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
        }
    }
}

// Adicionar tabela de pagamentos_prestadores
try {
    $db->query("CREATE TABLE IF NOT EXISTS pagamentos_prestadores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        diaria_id INT NOT NULL,
        prestador_id INT NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        forma_pagamento VARCHAR(20) DEFAULT 'pix',
        status ENUM('pendente', 'pago') DEFAULT 'pendente',
        pago_at DATETIME NULL,
        comprovante VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (diaria_id) REFERENCES diarias(id),
        FOREIGN KEY (prestador_id) REFERENCES prestadores(id)
    )");
    echo "<p style='color:green'>✓ Tabela pagamentos_prestadores criada!</p>";
} catch (Exception $e) {
    echo "<p style='color:orange'>⊙ " . $e->getMessage() . "</p>";
}

echo "<hr><h3>✓ Migrações concluídas!</h3>";
echo '<a href="pagamentos.php">Ir para Pagamentos</a>';
