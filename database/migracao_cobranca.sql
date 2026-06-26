-- =============================================
-- MIGRAÇÃO: Sistema de Cobrança
-- Execute esta migração para adicionar os campos necessários
-- =============================================

USE diarias_db;

-- Adicionar campos de cobrança na tabela diarias
ALTER TABLE diarias 
ADD COLUMN valor_empresa DECIMAL(10,2) DEFAULT NULL COMMENT 'Valor cobrado da empresa (com taxa)',
ADD COLUMN taxa_agencia DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Taxa da agência',
ADD COLUMN modelo_cobranca VARCHAR(50) DEFAULT NULL COMMENT 'Modelo usado: spread, taxa_fixa, ambos, personalizado',
ADD COLUMN pago_empresa TINYINT(1) DEFAULT 0 COMMENT 'Se a empresa já pagou',
ADD COLUMN pago_empresa_at DATETIME DEFAULT NULL COMMENT 'Data do pagamento da empresa';

-- Adicionar tabela de pagamentos_prestadores (se não existir)
CREATE TABLE IF NOT EXISTS pagamentos_prestadores (
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
) ENGINE=InnoDB;

-- Configurações padrão de cobrança
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('cobranca_ativa', '0', 'Ativar sistema de cobrança (0=desativado, 1=ativado)'),
('modelo_cobranca', 'spread', 'Modelo de cobrança: spread, taxa_fixa, ambos, personalizado'),
('margem_padrao', '20', 'Margem padrão (%) para modelo spread'),
('taxa_fixa_profissional', '30.00', 'Taxa fixa por profissional (R$)'),
('taxa_urgencia_ativa', '0', 'Ativar taxa de urgência'),
('taxa_urgencia_valor', '50.00', 'Valor da taxa de urgência (R$)'),
('prazo_pagamento_empresa', '7', 'Prazo em dias para empresa pagar'),
('prazo_pagamento_prestador', '3', 'Prazo em dias para pagar prestador após evento')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
