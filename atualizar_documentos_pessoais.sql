-- =====================================================
-- TABELA DE DOCUMENTOS PESSOAIS DO CLIENTE
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
