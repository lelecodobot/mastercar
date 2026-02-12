<?php
$usuario = usuarioAtual();
$menuAtivo = basename(dirname($_SERVER['PHP_SELF']));
if ($menuAtivo == 'admin') $menuAtivo = 'dashboard';

// Conta notificações
$notificacoesNaoLidas = DB()->fetch("SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0", [$usuario['id']])['total'] ?? 0;

// Conta inadimplência
$inadimplenciaCount = DB()->fetch("SELECT COUNT(*) as total FROM faturas_semanal WHERE status = 'vencido'")['total'] ?? 0;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">MC</div>
        <div class="sidebar-brand">Master<span>Car</span></div>
    </div>
    
    <nav class="sidebar-menu">
        <div class="menu-category">Principal</div>
        
        <a href="<?php echo BASE_URL; ?>/admin/" class="menu-item <?php echo $menuAtivo == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="menu-category">Cadastros</div>
        
        <a href="<?php echo BASE_URL; ?>/admin/clientes/" class="menu-item <?php echo $menuAtivo == 'clientes' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/veiculos/" class="menu-item <?php echo $menuAtivo == 'veiculos' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i>
            <span>Veículos</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/contratos/" class="menu-item <?php echo $menuAtivo == 'contratos' ? 'active' : ''; ?>">
            <i class="fas fa-file-contract"></i>
            <span>Contratos</span>
        </a>
        
        <div class="menu-category">Financeiro</div>
        
        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/" class="menu-item <?php echo $menuAtivo == 'cobrancas' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <span>Cobranças</span>
            <?php if ($inadimplenciaCount > 0): ?>
                <span class="menu-badge"><?php echo $inadimplenciaCount; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/cobrancas/inadimplencia.php" class="menu-item <?php echo $menuAtivo == 'inadimplencia' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Inadimplência</span>
            <?php if ($inadimplenciaCount > 0): ?>
                <span class="menu-badge"><?php echo $inadimplenciaCount; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/faturas/" class="menu-item <?php echo $menuAtivo == 'faturas' ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i>
            <span>Faturas</span>
        </a>
        
        <div class="menu-category">Relatórios</div>
        
        <a href="<?php echo BASE_URL; ?>/admin/relatorios/financeiro.php" class="menu-item <?php echo $menuAtivo == 'relatorios' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Financeiro</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/relatorios/contratos.php" class="menu-item">
            <i class="fas fa-chart-pie"></i>
            <span>Contratos</span>
        </a>
        
        <div class="menu-category">Configurações</div>
        
        <a href="<?php echo BASE_URL; ?>/admin/configuracoes/" class="menu-item <?php echo $menuAtivo == 'configuracoes' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Configurações</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/configuracoes/pagamento.php" class="menu-item">
            <i class="fas fa-credit-card"></i>
            <span>Pagamento</span>
        </a>
        
        <?php if ($usuario['tipo'] == 'master'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/usuarios/" class="menu-item <?php echo $menuAtivo == 'usuarios' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>Usuários</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/admin/logs/" class="menu-item <?php echo $menuAtivo == 'logs' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Logs do Sistema</span>
            </a>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sair</span>
        </a>
    </nav>
</aside>
