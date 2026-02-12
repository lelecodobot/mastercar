<?php
/**
 * Master Car - Script de Atualização do Banco de Dados
 * Execute este arquivo para adicionar as colunas necessárias
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h1>Atualização do Banco de Dados - Master Car</h1>";
echo "<hr>";

try {
    // Adicionar coluna descricao na tabela faturas_semanal
    echo "<p>Verificando coluna 'descricao' na tabela faturas_semanal...</p>";
    
    $colunas = DB()->fetchAll("SHOW COLUMNS FROM faturas_semanal");
    $temDescricao = false;
    foreach ($colunas as $col) {
        if ($col['Field'] == 'descricao') {
            $temDescricao = true;
            break;
        }
    }
    
    if (!$temDescricao) {
        DB()->query("ALTER TABLE faturas_semanal ADD COLUMN descricao VARCHAR(255) AFTER numero_fatura");
        echo "<p style='color: green;'>✓ Coluna 'descricao' adicionada com sucesso!</p>";
    } else {
        echo "<p style='color: blue;'>→ Coluna 'descricao' já existe.</p>";
    }
    
    // Verificar tabela veiculos_documentos
    echo "<p>Verificando tabela 'veiculos_documentos'...</p>";
    $tabelas = DB()->fetchAll("SHOW TABLES");
    $temTabelaDoc = false;
    foreach ($tabelas as $tabela) {
        $nome = array_values($tabela)[0];
        if ($nome == 'veiculos_documentos') {
            $temTabelaDoc = true;
            break;
        }
    }
    
    if (!$temTabelaDoc) {
        DB()->query("CREATE TABLE IF NOT EXISTS veiculos_documentos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p style='color: green;'>✓ Tabela 'veiculos_documentos' criada com sucesso!</p>";
    } else {
        echo "<p style='color: blue;'>→ Tabela 'veiculos_documentos' já existe.</p>";
    }
    
    // Verificar tabela veiculos_fotos
    echo "<p>Verificando tabela 'veiculos_fotos'...</p>";
    $temTabelaFotos = false;
    foreach ($tabelas as $tabela) {
        $nome = array_values($tabela)[0];
        if ($nome == 'veiculos_fotos') {
            $temTabelaFotos = true;
            break;
        }
    }
    
    if (!$temTabelaFotos) {
        DB()->query("CREATE TABLE IF NOT EXISTS veiculos_fotos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            veiculo_id INT NOT NULL,
            arquivo VARCHAR(255) NOT NULL,
            descricao VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
            INDEX idx_veiculo_foto (veiculo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "<p style='color: green;'>✓ Tabela 'veiculos_fotos' criada com sucesso!</p>";
    } else {
        echo "<p style='color: blue;'>→ Tabela 'veiculos_fotos' já existe.</p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Atualização concluída com sucesso!</h2>";
    echo "<p><a href='admin/'>Ir para o Painel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Erro na atualização:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
