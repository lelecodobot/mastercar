-- =====================================================
-- ATUALIZAÇÃO FINAL - Múltiplos Contratos e Tipo de Contrato
-- Execute no phpMyAdmin
-- =====================================================

-- Adicionar colunas na tabela contratos_semanal
ALTER TABLE contratos_semanal 
ADD COLUMN IF NOT EXISTS tipo_contrato VARCHAR(50) DEFAULT 'padrao' AFTER numero_contrato,
ADD COLUMN IF NOT EXISTS km_limite_semanal INT DEFAULT 0 AFTER valor_semanal,
ADD COLUMN IF NOT EXISTS valor_km_extra DECIMAL(10,2) DEFAULT 0.00 AFTER km_limite_semanal;

-- Adicionar colunas na tabela faturas_semanal (se ainda não existirem)
ALTER TABLE faturas_semanal 
ADD COLUMN IF NOT EXISTS descricao VARCHAR(255) AFTER numero_fatura,
ADD COLUMN IF NOT EXISTS gateway VARCHAR(50) DEFAULT 'local' AFTER forma_pagamento,
ADD COLUMN IF NOT EXISTS data_baixa DATE NULL AFTER data_pagamento,
ADD COLUMN IF NOT EXISTS usuario_baixa_id INT NULL AFTER data_baixa,
ADD COLUMN IF NOT EXISTS motivo_baixa TEXT NULL AFTER usuario_baixa_id,
ADD COLUMN IF NOT EXISTS tipo_baixa ENUM('pagamento', 'cancelamento', 'estorno') NULL AFTER motivo_baixa;

-- Criar tabela de modelos de contrato
CREATE TABLE IF NOT EXISTS modelos_contrato (
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

-- Inserir modelos padrão
INSERT INTO modelos_contrato (nome, tipo, descricao, conteudo_html) VALUES
('Contrato Padrão', 'padrao', 'Modelo padrão de contrato de locação', '<!-- Modelo padrão -->'),
('Contrato para Aplicativos', 'aplicativo', 'Contrato específico para uso em aplicativos de transporte (Uber, 99, etc.)', '<!-- Modelo aplicativo -->')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Criar tabela de documentos dos veículos
CREATE TABLE IF NOT EXISTS veiculos_documentos (
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

-- Criar tabela de fotos dos veículos
CREATE TABLE IF NOT EXISTS veiculos_fotos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    descricao VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo_foto (veiculo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela de logs de cobrança
CREATE TABLE IF NOT EXISTS cobranca_logs (
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

SELECT 'Atualização concluída com sucesso!' AS mensagem;
