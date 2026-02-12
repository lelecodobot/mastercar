-- =====================================================
-- ATUALIZAÇÃO DO BANCO - BAIXA/CANCELAMENTO E LOGS
-- Execute no phpMyAdmin
-- =====================================================

-- Adicionar colunas para baixa/cancelamento na faturas_semanal
ALTER TABLE faturas_semanal 
ADD COLUMN IF NOT EXISTS data_baixa DATE NULL AFTER data_pagamento,
ADD COLUMN IF NOT EXISTS usuario_baixa_id INT NULL AFTER data_baixa,
ADD COLUMN IF NOT EXISTS motivo_baixa TEXT NULL AFTER usuario_baixa_id,
ADD COLUMN IF NOT EXISTS tipo_baixa ENUM('pagamento', 'cancelamento', 'estorno') NULL AFTER motivo_baixa;

-- Criar tabela de logs de cobrança (se não existir)
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

SELECT 'Atualização concluída!' AS mensagem;
