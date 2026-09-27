CREATE DATABASE IF NOT EXISTS gestao_servicos_publicos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gestao_servicos_publicos;


-- =========================================
-- USUÁRIO
-- =========================================

CREATE TABLE usuario (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);


-- =========================================
-- CIDADÃO
-- Herda de usuário
-- =========================================

CREATE TABLE cidadao (
    usuario_id BIGINT PRIMARY KEY,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(20),

    CONSTRAINT fk_cidadao_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuario(id)
        ON DELETE CASCADE
);


-- =========================================
-- ADMINISTRADOR
-- Herda de usuário
-- =========================================

CREATE TABLE administrador (
    usuario_id BIGINT PRIMARY KEY,
    nivel_acesso VARCHAR(50) NOT NULL,

    CONSTRAINT fk_administrador_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuario(id)
        ON DELETE CASCADE
);


-- =========================================
-- SOLICITAÇÃO
-- =========================================

CREATE TABLE solicitacao (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    descricao TEXT NOT NULL,

    data_abertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    status VARCHAR(50) NOT NULL DEFAULT 'ABERTA',

    prioridade VARCHAR(30) NOT NULL DEFAULT 'NORMAL',

    cidadao_id BIGINT NOT NULL,

    administrador_id BIGINT NULL,

    CONSTRAINT fk_solicitacao_cidadao
        FOREIGN KEY (cidadao_id)
        REFERENCES cidadao(usuario_id),

    CONSTRAINT fk_solicitacao_administrador
        FOREIGN KEY (administrador_id)
        REFERENCES administrador(usuario_id)
        ON DELETE SET NULL
);


-- =========================================
-- LOCALIZAÇÃO
-- Uma solicitação possui uma localização
-- =========================================

CREATE TABLE localizacao (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    latitude DECIMAL(10,8),

    longitude DECIMAL(11,8),

    endereco VARCHAR(255) NOT NULL,

    solicitacao_id BIGINT NOT NULL UNIQUE,

    CONSTRAINT fk_localizacao_solicitacao
        FOREIGN KEY (solicitacao_id)
        REFERENCES solicitacao(id)
        ON DELETE CASCADE
);


-- =========================================
-- IMAGEM
-- Uma solicitação pode possuir várias imagens
-- O banco guarda apenas o caminho/URL
-- =========================================

CREATE TABLE imagem (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    arquivo VARCHAR(500) NOT NULL,

    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    solicitacao_id BIGINT NOT NULL,

    CONSTRAINT fk_imagem_solicitacao
        FOREIGN KEY (solicitacao_id)
        REFERENCES solicitacao(id)
        ON DELETE CASCADE
);


-- =========================================
-- NOTIFICAÇÃO
-- =========================================

CREATE TABLE notificacao (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    mensagem TEXT NOT NULL,

    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    lida BOOLEAN NOT NULL DEFAULT FALSE,

    solicitacao_id BIGINT NOT NULL,

    CONSTRAINT fk_notificacao_solicitacao
        FOREIGN KEY (solicitacao_id)
        REFERENCES solicitacao(id)
        ON DELETE CASCADE
);


-- =========================================
-- HISTÓRICO DA SOLICITAÇÃO
-- Guarda as mudanças de status
-- =========================================

CREATE TABLE historico_solicitacao (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    solicitacao_id BIGINT NOT NULL,

    status_anterior VARCHAR(50),

    status_novo VARCHAR(50) NOT NULL,

    data_alteracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    administrador_id BIGINT NULL,

    CONSTRAINT fk_historico_solicitacao
        FOREIGN KEY (solicitacao_id)
        REFERENCES solicitacao(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_historico_administrador
        FOREIGN KEY (administrador_id)
        REFERENCES administrador(usuario_id)
        ON DELETE SET NULL
);


-- =========================================
-- RELATÓRIO
-- =========================================

CREATE TABLE relatorio (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    periodo_inicial DATE NOT NULL,

    periodo_final DATE NOT NULL,

    formato VARCHAR(20) NOT NULL,

    data_geracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    administrador_id BIGINT NOT NULL,

    CONSTRAINT fk_relatorio_administrador
        FOREIGN KEY (administrador_id)
        REFERENCES administrador(usuario_id)
);


-- =========================================
-- ÍNDICES
-- Melhoram pesquisas mais frequentes
-- =========================================

CREATE INDEX idx_solicitacao_status
ON solicitacao(status);

CREATE INDEX idx_solicitacao_prioridade
ON solicitacao(prioridade);

CREATE INDEX idx_solicitacao_cidadao
ON solicitacao(cidadao_id);

CREATE INDEX idx_notificacao_solicitacao
ON notificacao(solicitacao_id);

CREATE INDEX idx_historico_solicitacao
ON historico_solicitacao(solicitacao_id);