-- =====================================================
-- MASTER CAR - SISTEMA DE GESTÃO DE LOCADORA
-- Database Schema Completo
-- Versão: 3.3
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABELA DE USUÁRIOS DO SISTEMA (ADMIN)
-- =====================================================
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'master') DEFAULT 'admin',
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuário padrão (senha: master123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@mastercar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'master');

-- =====================================================
-- TABELA DE CLIENTES
-- =====================================================
DROP TABLE IF EXISTS clientes;
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Dados pessoais
    nome VARCHAR(100) NOT NULL,
    cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
    rg_ie VARCHAR(20),
    data_nascimento DATE,
    
    -- Dados de contato
    email VARCHAR(100),
    telefone VARCHAR(20),
    celular VARCHAR(20),
    
    -- Endereço
    cep VARCHAR(10),
    endereco VARCHAR(150),
    numero VARCHAR(10),
    complemento VARCHAR(50),
    bairro VARCHAR(50),
    cidade VARCHAR(50),
    estado CHAR(2),
    
    -- Dados da CNH
    cnh_numero VARCHAR(20),
    cnh_categoria VARCHAR(5),
    cnh_validade DATE,
    cnh_primeira_habilitacao DATE,
    
    -- Dados de acesso
    senha VARCHAR(255),
    
    -- Status
    status ENUM('ativo', 'inativo', 'bloqueado') DEFAULT 'ativo',
    bloqueado TINYINT(1) DEFAULT 0,
    motivo_bloqueio TEXT,
    
    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cpf_cnpj (cpf_cnpj),
    INDEX idx_status (status),
    INDEX idx_bloqueado (bloqueado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE VEÍCULOS
-- =====================================================
DROP TABLE IF EXISTS veiculos;
CREATE TABLE veiculos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Dados do veículo
    placa VARCHAR(10) NOT NULL UNIQUE,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    ano_fabricacao INT,
    ano_modelo INT,
    cor VARCHAR(30),
    chassi VARCHAR(30),
    renavam VARCHAR(20),
    
    -- Categoria e combustível
    categoria ENUM('economico', 'intermediario', 'suv', 'luxo', 'utilitario') DEFAULT 'economico',
    combustivel ENUM('gasolina', 'alcool', 'flex', 'diesel', 'eletrico', 'hibrido') DEFAULT 'flex',
    
    -- Quilometragem
    km_atual INT DEFAULT 0,
    km_ultima_revisao INT DEFAULT 0,
    
    -- Valores
    valor_semanal DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_diaria DECIMAL(10,2) DEFAULT 0,
    valor_mensal DECIMAL(10,2) DEFAULT 0,
    
    -- Status
    status ENUM('disponivel', 'locado', 'manutencao', 'reservado', 'inativo') DEFAULT 'disponivel',
    
    -- Seguro
    seguradora VARCHAR(50),
    apolice VARCHAR(50),
    vencimento_seguro DATE,
    
    -- Observações
    observacoes TEXT,
    
    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_placa (placa),
    INDEX idx_status (status),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE FOTOS DOS VEÍCULOS
-- =====================================================
DROP TABLE IF EXISTS veiculos_fotos;
CREATE TABLE veiculos_fotos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    descricao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo_foto (veiculo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE DOCUMENTOS DOS VEÍCULOS
-- =====================================================
DROP TABLE IF EXISTS veiculos_documentos;
CREATE TABLE veiculos_documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'outro',
    descricao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo_doc (veiculo_id),
    INDEX idx_tipo_doc (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE CONTRATOS (COM SUPORTE A MÚLTIPLOS CONTRATOS)
-- =====================================================
DROP TABLE IF EXISTS contratos_semanal;
CREATE TABLE contratos_semanal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Relacionamentos
    cliente_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    
    -- Dados do contrato
    numero_contrato VARCHAR(30) NOT NULL UNIQUE,
    tipo_contrato VARCHAR(50) DEFAULT 'padrao',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    
    -- Valores
    valor_semanal DECIMAL(10,2) NOT NULL,
    valor_caucao DECIMAL(10,2) DEFAULT 0,
    valor_multa_diaria DECIMAL(10,2) DEFAULT 0,
    
    -- Quilometragem (para contratos de aplicativo)
    km_limite_semanal INT DEFAULT 0,
    valor_km_extra DECIMAL(10,2) DEFAULT 0,
    
    -- Configurações de cobrança
    dias_tolerancia INT DEFAULT 3,
    data_proxima_cobranca DATE,
    
    -- Status
    status ENUM('ativo', 'suspenso', 'encerrado', 'cancelado') DEFAULT 'ativo',
    recorrencia_ativa TINYINT(1) DEFAULT 1,
    
    -- Observações
    observacoes TEXT,
    
    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_status (status),
    INDEX idx_tipo_contrato (tipo_contrato),
    INDEX idx_data_proxima_cobranca (data_proxima_cobranca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE FATURAS/COBRANÇAS
-- =====================================================
DROP TABLE IF EXISTS faturas_semanal;
CREATE TABLE faturas_semanal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Relacionamentos
    cliente_id INT NOT NULL,
    contrato_id INT NOT NULL,
    
    -- Dados da fatura
    numero_fatura VARCHAR(30) NOT NULL UNIQUE,
    descricao VARCHAR(255),
    semana_referencia INT NOT NULL,
    data_referencia DATE NOT NULL,
    
    -- Valores
    valor_original DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    valor_desconto DECIMAL(10,2) DEFAULT 0,
    valor_multa DECIMAL(10,2) DEFAULT 0,
    valor_juros DECIMAL(10,2) DEFAULT 0,
    
    -- Datas
    data_emissao DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    
    -- Baixa/Cancelamento
    data_baixa DATE,
    usuario_baixa_id INT,
    motivo_baixa TEXT,
    tipo_baixa ENUM('pagamento', 'cancelamento', 'estorno'),
    
    -- Gateway de pagamento
    gateway VARCHAR(50) DEFAULT 'local',
    gateway_id VARCHAR(100),
    gateway_status VARCHAR(50),
    gateway_url VARCHAR(255),
    forma_pagamento VARCHAR(50),
    
    -- Status
    status ENUM('pendente', 'pago', 'vencido', 'cancelado', 'bloqueado', 'processando') DEFAULT 'pendente',
    
    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (contrato_id) REFERENCES contratos_semanal(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_contrato (contrato_id),
    INDEX idx_status (status),
    INDEX idx_data_vencimento (data_vencimento),
    INDEX idx_numero_fatura (numero_fatura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE DOCUMENTOS DO CLIENTE (ENVIADOS PELO PORTAL)
-- =====================================================
DROP TABLE IF EXISTS clientes_documentos;
CREATE TABLE clientes_documentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    
    -- Tipo de documento
    tipo ENUM('cnh', 'rg', 'cpf', 'comprovante_residencia', 'contrato_social', 'outro') NOT NULL,
    
    -- Arquivo
    arquivo VARCHAR(255) NOT NULL,
    descricao VARCHAR(255),
    
    -- Status de verificação
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    observacao_admin TEXT,
    
    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE CONFIGURAÇÕES
-- =====================================================
DROP TABLE IF EXISTS configuracoes;
CREATE TABLE configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configurações padrão
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('nome_empresa', 'Master Car Locadora', 'Nome da empresa'),
('cnpj_empresa', '', 'CNPJ da empresa'),
('endereco_empresa', '', 'Endereço completo'),
('telefone_empresa', '', 'Telefone de contato'),
('email_empresa', '', 'E-mail de contato'),
('responsavel_empresa', '', 'Nome do responsável'),
('locador_nome', '', 'Nome do locador para contratos'),
('locador_cpf', '', 'CPF do locador'),
('locador_rg', '', 'RG do locador'),
('locador_cnh', '', 'CNH do locador'),
('locador_endereco', '', 'Endereço do locador'),
('pix_chave', '', 'Chave PIX da empresa'),
('dias_tolerancia_padrao', '3', 'Dias de tolerância para pagamento'),
('multa_atraso_percentual', '2', 'Percentual de multa por atraso'),
('juros_atraso_diario', '0.033', 'Juros diários por atraso'),
('gateway_ativo', 'local', 'Gateway de pagamento ativo'),
('gateway_asaas_api_key', '', 'API Key do Asaas'),
('gateway_asaas_ambiente', 'sandbox', 'Ambiente do Asaas'),
('modelo_contrato', '', 'Modelo HTML do contrato padrão');

-- =====================================================
-- TABELA DE MODELOS DE CONTRATO
-- =====================================================
DROP TABLE IF EXISTS modelos_contrato;
CREATE TABLE modelos_contrato (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'padrao',
    descricao TEXT,
    conteudo_html LONGTEXT NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modelos padrão
INSERT INTO modelos_contrato (nome, tipo, descricao, conteudo_html) VALUES
('Contrato Padrão', 'padrao', 'Modelo padrão de contrato de locação', '<!-- Modelo padrão -->'),
('Contrato para Aplicativos', 'aplicativo', 'Contrato específico para uso em aplicativos de transporte (Uber, 99, etc.)', '<!-- Modelo aplicativo -->');

-- =====================================================
-- TABELA DE LOGS DE COBRANÇA
-- =====================================================
DROP TABLE IF EXISTS cobranca_logs;
CREATE TABLE cobranca_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fatura_id INT NULL,
    contrato_id INT NULL,
    cliente_id INT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'sistema',
    descricao TEXT NULL,
    dados_json TEXT NULL,
    ip_address VARCHAR(45) NULL,
    usuario_id INT NULL,
    usuario_tipo VARCHAR(20) DEFAULT 'sistema',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fatura (fatura_id),
    INDEX idx_contrato (contrato_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABELA DE LOGS DE ACESSO
-- =====================================================
DROP TABLE IF EXISTS logs_acesso;
CREATE TABLE logs_acesso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL,
    usuario_tipo ENUM('admin', 'cliente') DEFAULT 'admin',
    usuario_nome VARCHAR(100) NULL,
    acao VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    sucesso TINYINT(1) DEFAULT 1,
    mensagem VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
