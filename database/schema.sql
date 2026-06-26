-- =============================================
-- BANCO DE DADOS DO SISTEMA DE DIÁRIAS
-- =============================================

CREATE DATABASE IF NOT EXISTS diarias_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diarias_db;

-- =============================================
-- TABELA: usuarios (todos os usuários do sistema)
-- =============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    foto VARCHAR(255),
    tipo ENUM('admin', 'empresa', 'prestador') NOT NULL DEFAULT 'prestador',
    ativo TINYINT(1) DEFAULT 1,
    email_verificado TINYINT(1) DEFAULT 0,
    token_recuperacao VARCHAR(255),
    ultimo_acesso DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: prestadores (dados específicos de prestadores)
-- =============================================
CREATE TABLE prestadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    data_nascimento DATE NOT NULL,
    cpf VARCHAR(14),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    bio TEXT,
    funcoes JSON, -- Array de funções que atua: ['garcom', 'seguranca', 'recepcionista']
    status ENUM('pendente', 'aprovado', 'rejeitado', 'banido', 'suspenso') DEFAULT 'pendente',
    nota_media DECIMAL(3,2) DEFAULT 0.00,
    total_avaliacoes INT DEFAULT 0,
    total_diarias INT DEFAULT 0,
    total_faltas INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_nota (nota_media)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: empresas (dados específicos de empresas)
-- =============================================
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    razao_social VARCHAR(200),
    cnpj VARCHAR(18),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
   telefone_empresa VARCHAR(20),
    contato_nome VARCHAR(100),
    status ENUM('ativo', 'inativo', 'pendente') DEFAULT 'ativo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: diarias (vagas de trabalho)
-- =============================================
CREATE TABLE diarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    funcao VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('na_hora', 'posterior') DEFAULT 'na_hora',
    data_evento DATE NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    vagas_total INT NOT NULL DEFAULT 1,
    vagas_preenchidas INT DEFAULT 0,
    endereco TEXT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    observacoes TEXT,
    status ENUM('ativa', 'em_andamento', 'finalizada', 'cancelada') DEFAULT 'ativa',
    created_by INT, -- ID do admin que criou (ou NULL se foi a empresa)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    INDEX idx_status (status),
    INDEX idx_data (data_evento),
    INDEX idx_funcao (funcao),
    INDEX idx_localizacao (latitude, longitude)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: candidaturas (reservas de vagas)
-- =============================================
CREATE TABLE candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    diaria_id INT NOT NULL,
    prestador_id INT NOT NULL,
    status ENUM('pendente', 'confirmada', 'checkin_realizado', 'faltou', 'cancelada') DEFAULT 'confirmada',
    data_candidatura DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_checkin DATETIME,
    observacoes TEXT,

    FOREIGN KEY (diaria_id) REFERENCES diarias(id) ON DELETE CASCADE,
    FOREIGN KEY (prestador_id) REFERENCES prestadores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_diaria_prestador (diaria_id, prestador_id),
    INDEX idx_status (status),
    INDEX idx_diaria (diaria_id),
    INDEX idx_prestador (prestador_id)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: avaliacoes (feedback pós-evento)
-- =============================================
CREATE TABLE avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    diaria_id INT NOT NULL,
    prestador_id INT NOT NULL,
    empresa_id INT NOT NULL,
    nota TINYINT NOT NULL CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (diaria_id) REFERENCES diarias(id) ON DELETE CASCADE,
    FOREIGN KEY (prestador_id) REFERENCES prestadores(id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id),
    UNIQUE KEY unique_avaliacao (diaria_id, prestador_id),
    INDEX idx_prestador (prestador_id),
    INDEX idx_nota (nota)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: pagamentos (controle financeiro)
-- =============================================
CREATE TABLE pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    diaria_id INT NOT NULL,
    prestador_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
    data_pagamento DATETIME,
    forma_pagamento VARCHAR(50),
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (diaria_id) REFERENCES diarias(id) ON DELETE CASCADE,
    FOREIGN KEY (prestador_id) REFERENCES prestadores(id),
    INDEX idx_status (status),
    INDEX idx_prestador (prestador_id)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: notificacoes
-- =============================================
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('nova_diaria', 'candidatura_aceita', 'lembrete', 'avaliacao', 'pagamento', 'sistema') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    link VARCHAR(255),
    lida TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_lida (usuario_id, lida)
) ENGINE=InnoDB;

-- =============================================
-- TABELA: configuracoes (configurações do sistema)
-- =============================================
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABELA: logs_auditoria
-- =============================================
CREATE TABLE logs_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(50),
    registro_id INT,
    dados_antigos JSON,
    dados_novos JSON,
    ip VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_tabela_registro (tabela, registro_id)
) ENGINE=InnoDB;

-- =============================================
-- DADOS INICIAIS
-- =============================================

-- Inserir usuário admin padrão
INSERT INTO usuarios (nome, email, senha, tipo, ativo, email_verificado) VALUES
('Administrador', 'admin@diarias.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1);
-- Senha: password

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('nome_empresa', 'Diárias', 'Nome da empresa/marca'),
('email_contato', 'contato@diarias.com', 'Email de contato'),
('telefone_contato', '(00) 00000-0000', 'Telefone de contato'),
('lat_padrao', '-23.5505', 'Latitude padrão para mapas'),
('lng_padrao', '-46.6333', 'Longitude padrão para mapas'),
('zoom_mapa', '12', 'Zoom padrão do mapa'),
('idade_minima', '18', 'Idade mínima para cadastro de prestador'),
('max_faltas', '3', 'Máximo de faltas antes de suspensão');

-- Funções disponíveis para prestadores
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('funcoes_disponiveis', '["Garçom", "Segurança", "Recepcionista", "Bartender", "Limpeza", "Montador", "Sonoplasta", "Fotógrafo", "Decorador", "Cozinheiro", "Auxiliar de Cozinha", "Churrasqueiro"]', 'Lista de funções disponíveis');
