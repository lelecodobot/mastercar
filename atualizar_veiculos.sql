-- =====================================================
-- ATUALIZAÇÃO: Adicionar colunas faltantes na tabela veiculos
-- Execute este SQL no phpMyAdmin
-- =====================================================

-- Adicionar coluna combustivel
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS combustivel ENUM('gasolina', 'alcool', 'flex', 'diesel', 'eletrico', 'hibrido') DEFAULT 'flex' AFTER renavam;

-- Adicionar coluna categoria (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS categoria ENUM('economico', 'intermediario', 'suv', 'luxo', 'utilitario') DEFAULT 'economico' AFTER cor;

-- Adicionar coluna km_atual (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS km_atual INT DEFAULT 0 AFTER combustivel;

-- Adicionar coluna km_ultima_revisao (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS km_ultima_revisao INT DEFAULT 0 AFTER km_atual;

-- Adicionar coluna valor_diaria (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS valor_diaria DECIMAL(10,2) DEFAULT 0 AFTER valor_semanal;

-- Adicionar coluna valor_mensal (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS valor_mensal DECIMAL(10,2) DEFAULT 0 AFTER valor_diaria;

-- Adicionar coluna seguradora (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS seguradora VARCHAR(50) AFTER status;

-- Adicionar coluna apolice (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS apolice VARCHAR(50) AFTER seguradora;

-- Adicionar coluna vencimento_seguro (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS vencimento_seguro DATE AFTER apolice;

-- Adicionar coluna observacoes (se não existir)
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS observacoes TEXT AFTER vencimento_seguro;

-- Verificar estrutura final
DESCRIBE veiculos;
