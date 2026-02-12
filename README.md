# Master Car - Sistema de Gestão de Locadora de Veículos

Sistema completo de gestão de locadora de veículos com cobrança automática semanal, integração com gateways de pagamento e controle financeiro.

## Requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Apache com mod_rewrite
- Extensões PHP: PDO, PDO_MySQL, cURL, JSON

## Instalação

### 1. Extraia os arquivos

Extraia o conteúdo do arquivo para a pasta do seu servidor web:
```
C:\xampp\htdocs\mastercar\
```

### 2. Acesse o instalador

Abra o navegador e acesse:
```
http://localhost/mastercar/install/
```

### 3. Siga os passos do instalador

1. **Verificação de requisitos** - O sistema verificará se todos os requisitos estão atendidos
2. **Configuração do banco de dados** - Informe os dados de conexão com o MySQL
3. **Criação do administrador** - Crie o usuário master do sistema

### 4. Acesse o sistema

Após a instalação, acesse:
```
http://localhost/mastercar/admin/
```

## Configuração do CRON

Para que as cobranças sejam geradas automaticamente, configure o CRON do servidor:

### Windows (Agendador de Tarefas)

1. Abra o Agendador de Tarefas do Windows
2. Crie uma nova tarefa básica
3. Configure para executar diariamente às 06:00
4. Ação: Iniciar programa
5. Programa: `C:\xampp\php\php.exe`
6. Argumentos: `C:\xampp\htdocs\mastercar\cron\cobranca_semanal.php`

### Linux (CRON)

```bash
0 6 * * * /usr/bin/php /var/www/mastercar/cron/cobranca_semanal.php
```

## Configuração de Pagamento

### Asaas

1. Acesse **Configurações > Pagamento** no painel administrativo
2. Selecione o gateway "Asaas"
3. Informe sua API Key
4. Escolha o ambiente (Sandbox para testes, Produção para real)
5. Salve as configurações

### Webhook

Configure o webhook no seu gateway de pagamento para:
```
http://localhost/mastercar/api/webhook.php
```

## Estrutura de Pastas

```
mastercar/
├── admin/              # Painel administrativo
│   ├── clientes/       # Gestão de clientes
│   ├── veiculos/       # Gestão de veículos
│   ├── contratos/      # Gestão de contratos
│   ├── cobrancas/      # Gestão de cobranças
│   └── includes/       # Includes do admin
├── api/                # APIs e webhooks
├── assets/             # CSS, JS, imagens
├── cliente/            # Área do cliente
├── cron/               # Scripts de automação
├── includes/           # Classes e funções
├── install/            # Instalador
├── uploads/            # Arquivos enviados
└── logs/               # Logs do sistema
```

## Funcionalidades

### Administrativo

- Dashboard com resumo financeiro
- Cadastro de clientes (com upload de documentos)
- Cadastro de veículos (com fotos)
- Contratos semanais com recorrência automática
- Geração automática de cobranças
- Integração com boleto e PIX
- Painel de inadimplência
- Controle de bloqueios
- Relatórios financeiros

### Cliente

- Visualização de contratos
- Acompanhamento de faturas
- Download de boletos
- Pagamento via PIX
- Histórico financeiro
- Upload de documentos

## Cobrança Semanal Automática

O sistema gera cobranças automaticamente a cada 7 dias para contratos ativos:

1. O CRON verifica contratos com recorrência ativa
2. Gera faturas na data da próxima cobrança
3. Calcula vencimento com base nos dias de tolerância
4. Envia notificações ao cliente
5. Aplica multa e juros em caso de atraso
6. Bloqueia cliente e veículo após período de tolerância

## Status das Faturas

- **Pendente** - Aguardando pagamento
- **Pago** - Pagamento confirmado
- **Vencido** - Passou da data de vencimento
- **Bloqueado** - Cliente bloqueado por inadimplência
- **Cancelado** - Fatura cancelada

## Suporte

Em caso de dúvidas ou problemas, consulte os logs em:
```
/logs/error.log
/logs/cron.log
/logs/webhook.log
```

## Licença

Este sistema é de uso exclusivo da empresa contratante.

---

**Master Car** - Sistema de Gestão de Locadora de Veículos v1.0.0
