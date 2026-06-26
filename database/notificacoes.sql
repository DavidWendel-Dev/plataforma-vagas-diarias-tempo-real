-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'ID do usuário que recebe a notificação (admin=1, empresa=logada)',
    tipo VARCHAR(50) NOT NULL DEFAULT 'candidatura' COMMENT 'Tipo: candidatura, checkin, sistema',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT,
    link VARCHAR(255) NULL COMMENT 'URL para abrir quando clicar',
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_leitura DATETIME NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notificacoes_usuario (usuario_id, lida),
    INDEX idx_notificacoes_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
