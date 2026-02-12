-- =====================================================
-- ATUALIZAÇÃO DO BANCO DE DADOS - MASTER CAR
-- Execute este SQL no phpMyAdmin
-- =====================================================

-- Adicionar coluna 'descricao' na tabela faturas_semanal
ALTER TABLE faturas_semanal 
ADD COLUMN IF NOT EXISTS descricao VARCHAR(255) AFTER numero_fatura;

-- Adicionar coluna 'gateway' na tabela faturas_semanal
ALTER TABLE faturas_semanal 
ADD COLUMN IF NOT EXISTS gateway VARCHAR(50) DEFAULT 'local' AFTER forma_pagamento;

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

-- Verificar se as colunas foram adicionadas
SELECT 'Atualização concluída!' AS mensagem;
