<?php
/**
 * Executar migração de cobrança
 */
require_once __DIR__ . '/app.php';

$db = Database::getInstance();

echo "<h1>Executando Migração de Cobrança</h1>";

try {
    // Adicionar colunas na tabela diarias se não existirem
    $cols = $db->fetchAll("SHOW COLUMNS FROM diarias");
    $colNames = array_column($cols, 'Field');
    
    if (!in_array('valor_empresa', $colNames)) {
        $db->query("ALTER TABLE diarias ADD COLUMN valor_empresa DECIMAL(10,2) DEFAULT NULL");
        echo "✓ Coluna valor_empresa adicionada<br>";
    }
    
    if (!in_array('taxa_agencia', $colNames)) {
        $db->query("ALTER TABLE diarias ADD COLUMN taxa_agencia DECIMAL(10,2) DEFAULT 0.00");
        echo "✓ Coluna taxa_agencia adicionada<br>";
    }
    
    if (!in_array('modelo_cobranca', $colNames)) {
        $db->query("ALTER TABLE diarias ADD COLUMN modelo_cobranca VARCHAR(50) DEFAULT NULL");
        echo "✓ Coluna modelo_cobranca adicionada<br>";
    }
    
    if (!in_array('pago_empresa', $colNames)) {
        $db->query("ALTER TABLE diarias ADD COLUMN pago_empresa TINYINT(1) DEFAULT 0");
        echo "✓ Coluna pago_empresa adicionada<br>";
    }
    
    if (!in_array('pago_empresa_at', $colNames)) {
        $db->query("ALTER TABLE diarias ADD COLUMN pago_empresa_at DATETIME DEFAULT NULL");
        echo "✓ Coluna pago_empresa_at adicionada<br>";
    }
    
    // Criar tabela pagamentos_prestadores se não existir
    $tables = $db->fetchAll("SHOW TABLES LIKE 'pagamentos_prestadores'");
    if (empty($tables)) {
        $db->query("CREATE TABLE pagamentos_prestadores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            diaria_id INT NOT NULL,
            prestador_id INT NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
            pago_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (diaria_id) REFERENCES diarias(id) ON DELETE CASCADE,
            FOREIGN KEY (prestador_id) REFERENCES prestadores(id) ON DELETE CASCADE,
            UNIQUE KEY unique_diaria_prestador (diaria_id, prestador_id)
        ) ENGINE=InnoDB");
        echo "✓ Tabela pagamentos_prestadores criada<br>";
    }
    
    // Inserir configurações de cobrança
    $configs = [
        ['cobranca_ativa', '0', 'Ativar sistema de cobrança'],
        ['modelo_cobranca', 'spread', 'Modelo de cobrança'],
        ['margem_padrao', '20', 'Margem padrão (%)'],
        ['taxa_fixa_profissional', '30.00', 'Taxa fixa por profissional (R$)'],
        ['taxa_urgencia_ativa', '0', 'Ativar taxa de urgência'],
        ['taxa_urgencia_valor', '50.00', 'Valor da taxa de urgência'],
        ['prazo_pagamento_empresa', '7', 'Prazo para empresa pagar (dias)'],
        ['prazo_pagamento_prestador', '3', 'Prazo para pagar prestador (dias)']
    ];
    
    foreach ($configs as $c) {
        $exists = $db->fetch("SELECT id FROM configuracoes WHERE chave = :chave", ['chave' => $c[0]]);
        if (!$exists) {
            $db->insert('configuracoes', [
                'chave' => $c[0],
                'valor' => $c[1],
                'descricao' => $c[2]
            ]);
            echo "✓ Configuração '{$c[0]}' criada<br>";
        } else {
            echo "• Configuração '{$c[0]}' já existe<br>";
        }
    }
    
    echo "<br><strong style='color:green'>✓ Migração concluída com sucesso!</strong>";
    echo "<br><a href='admin/configuracoes.php'>Ir para Configurações</a>";
    
} catch (Exception $e) {
    echo "<span style='color:red'>Erro: " . $e->getMessage() . "</span>";
}
