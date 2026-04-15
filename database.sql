-- ============================================================
-- TI Stock - Sistema de Controle de Estoque - Setor de TI
-- Banco de Dados MySQL
-- ============================================================
-- Execute este script primeiro, depois acesse install.php
-- ============================================================

CREATE DATABASE IF NOT EXISTS tistock
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tistock;

-- ----------------------------------------
-- Tabela: categorias
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    descricao   TEXT,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- Tabela: usuarios
-- Níveis: administrador, tecnico, consultor
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    senha           VARCHAR(255) NOT NULL,
    nivel           ENUM('administrador','tecnico','consultor') NOT NULL DEFAULT 'consultor',
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acesso   DATETIME NULL,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- Tabela: itens
-- Equipamentos e insumos do setor de TI
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS itens (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(200) NOT NULL,
    categoria_id        INT UNSIGNED NOT NULL,
    numero_serie        VARCHAR(100) NULL,
    numero_patrimonio   VARCHAR(100) NULL,
    fornecedor          VARCHAR(200) NULL,
    data_aquisicao      DATE NULL,
    valor_unitario      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantidade_atual    INT NOT NULL DEFAULT 0,
    quantidade_minima   INT NOT NULL DEFAULT 1,
    localizacao         VARCHAR(200) NULL,
    descricao           TEXT NULL,
    ativo               TINYINT(1) NOT NULL DEFAULT 1,
    criado_em           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_item_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- Tabela: movimentacoes
-- Histórico completo de entradas e saídas
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS movimentacoes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id             INT UNSIGNED NOT NULL,
    tipo                ENUM('entrada','saida') NOT NULL,
    motivo              ENUM('compra','doacao','devolucao','emprestimo','manutencao','descarte','alocacao') NOT NULL,
    quantidade          INT NOT NULL,
    data_movimentacao   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responsavel         VARCHAR(200) NOT NULL,
    observacoes         TEXT NULL,
    usuario_id          INT UNSIGNED NULL,
    CONSTRAINT fk_mov_item    FOREIGN KEY (item_id)    REFERENCES itens(id)    ON UPDATE CASCADE,
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- Tabela: emprestimos
-- Controle de itens emprestados a outros setores
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS emprestimos (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id             INT UNSIGNED NOT NULL,
    quantidade          INT NOT NULL DEFAULT 1,
    solicitante         VARCHAR(200) NOT NULL,
    setor_destino       VARCHAR(200) NOT NULL,
    data_saida          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    previsao_devolucao  DATE NOT NULL,
    data_devolucao      DATETIME NULL,
    status              ENUM('ativo','devolvido','atrasado') NOT NULL DEFAULT 'ativo',
    observacoes         TEXT NULL,
    usuario_id          INT UNSIGNED NULL,
    CONSTRAINT fk_emp_item    FOREIGN KEY (item_id)    REFERENCES itens(id)    ON UPDATE CASCADE,
    CONSTRAINT fk_emp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- Dados iniciais: Categorias padrão
-- ----------------------------------------
INSERT INTO categorias (nome, descricao) VALUES
('Hardware',   'Componentes físicos: computadores, servidores, placas e memórias'),
('Software',   'Licenças, mídias e pacotes de software'),
('Periférico', 'Dispositivos externos: teclados, mouses, monitores, impressoras'),
('Cabo',       'Cabos de rede, energia, HDMI, USB e outros'),
('Consumível', 'Itens consumíveis: toners, cartuchos, papel e similares');

-- ----------------------------------------
-- Tabela: kb_categorias
-- Categorias da base de conhecimento
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS kb_categorias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(100) NOT NULL,
    descricao   TEXT,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- Tabela: kb_artigos
-- Artigos/tutoriais da base de conhecimento
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS kb_artigos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo          VARCHAR(255) NOT NULL,
    slug            VARCHAR(280) NOT NULL UNIQUE,
    conteudo        LONGTEXT NOT NULL,
    categoria_id    INT UNSIGNED NULL,
    autor_id        INT UNSIGNED NULL,
    visualizacoes   INT UNSIGNED NOT NULL DEFAULT 0,
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_kb_artigo_categoria FOREIGN KEY (categoria_id)
        REFERENCES kb_categorias(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_kb_artigo_autor     FOREIGN KEY (autor_id)
        REFERENCES usuarios(id)      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------
-- NOTA: O usuário administrador é criado pelo install.php
-- Acesse /tistock/install.php após executar este script
-- ----------------------------------------
