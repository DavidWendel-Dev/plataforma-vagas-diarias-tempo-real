<?php
require_once __DIR__ . '/app.php';

$db = Database::getInstance();

$migrations = [
    // Adicionar coluna codigo_checkin na tabela diarias
    "ALTER TABLE diarias ADD COLUMN codigo_checkin VARCHAR(10) NULL AFTER status",
    
    // Adicionar colunas de avaliação na tabela diarias
    "ALTER TABLE diarias ADD COLUMN nota_prestador DECIMAL(3,1) NULL AFTER latitude",
    "ALTER TABLE diarias ADD COLUMN comentario_empresa TEXT NULL AFTER nota_prestador",
    "ALTER TABLE diarias ADD COLUMN avaliacao_empresa TEXT NULL AFTER comentario_empresa",
    
    // Adicionar coluna checkin_at na tabela candidaturas
    "ALTER TABLE candidaturas ADD COLUMN checkin_at DATETIME NULL AFTER status",
];

echo "<h2>Executando Migrações</h2>";

foreach ($migrations as $sql) {
    try {
        $db->query($sql);
        echo "<p style='color:green'>✓ Executado: " . substr($sql, 0, 60) . "...</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange'>⊙ Coluna já existe: " . substr($sql, 0, 60) . "...</p>";
        } else {
            echo "<p style='color:red'>✗ Erro: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr>";
echo "<h3>✓ Migrações concluídas!</h3>";
echo '<a href="app/index.php">Ir para o App</a>';
