<?php
/**
 * Script para instalar o banco de dados
 * Execute: php install-db.php
 */

echo "=== Instalador do Banco de Dados ===\n\n";

// Configurações
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'diarias_db';

try {
    // Conectar ao MySQL (sem especificar banco)
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Conectado ao MySQL\n";
    
    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($sqlFile)) {
        die("✗ Arquivo SQL não encontrado: {$sqlFile}\n");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ Arquivo SQL carregado\n";
    
    // Executar o SQL
    // Dividir em comandos individuais (separados por ;)
    $pdo->exec($sql);
    
    echo "✓ Banco de dados criado com sucesso!\n\n";
    
    // Verificar tabelas criadas
    $pdo->exec("USE {$dbname}");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tabelas criadas:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
    echo "\n✓ Instalação concluída!\n";
    echo "\nAcesse o sistema com:\n";
    echo "  Email: admin@diarias.com\n";
    echo "  Senha: password\n";
    
} catch (PDOException $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
