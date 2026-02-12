-- =====================================================
-- ATUALIZAÇÃO: Múltiplos Contratos e Tipo de Contrato
-- Execute no phpMyAdmin
-- =====================================================

-- Adicionar campo tipo_contrato na tabela contratos_semanal
ALTER TABLE contratos_semanal 
ADD COLUMN IF NOT EXISTS tipo_contrato VARCHAR(50) DEFAULT 'padrao' AFTER numero_contrato,
ADD COLUMN IF NOT EXISTS km_limite_semanal INT DEFAULT 0 AFTER valor_semanal,
ADD COLUMN IF NOT EXISTS valor_km_extra DECIMAL(10,2) DEFAULT 0.00 AFTER km_limite_semanal;

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

-- Inserir modelo padrão
INSERT INTO modelos_contrato (nome, tipo, descricao, conteudo_html) VALUES
('Contrato Padrão', 'padrao', 'Modelo padrão de contrato de locação', '<!-- Modelo padrão -->'),
('Contrato para Aplicativos', 'aplicativo', 'Contrato específico para uso em aplicativos de transporte (Uber, 99, etc.)', '<!-- Modelo aplicativo -->')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

SELECT 'Atualização de contratos concluída!' AS mensagem;
