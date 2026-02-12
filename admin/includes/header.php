<?php
$usuario = usuarioAtual();
$primeiroNome = explode(' ', $usuario['nome'])[0];

// Notificações
$notificacoes = DB()->fetchAll("
    SELECT * FROM notificacoes 
    WHERE usuario_id = ? OR usuario_id IS NULL
    ORDER BY created_at DESC 
    LIMIT 5
", [$usuario['id']]);

$notificacoesNaoLidas = DB()->fetch("SELECT COUNT(*) as total FROM notificacoes WHERE (usuario_id = ? OR usuario_id IS NULL) AND lida = 0", [$usuario['id']])['total'] ?? 0;
?>
<header class="header">
    <div class="header-left">
        <button class="toggle-sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="header-title">Painel Administrativo</h2>
    </div>
    
    <div class="header-right">
        <!-- Notificações -->
        <div class="header-icon" onclick="toggleNotificacoes()">
            <i class="fas fa-bell"></i>
            <?php if ($notificacoesNaoLidas > 0): ?>
                <span class="badge"><?php echo $notificacoesNaoLidas; ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Menu do Usuário -->
        <div class="user-menu" onclick="toggleUserMenu()">
            <div class="user-avatar">
                <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo $primeiroNome; ?></div>
                <div class="user-role"><?php echo $usuario['tipo']; ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size: 12px; color: var(--gray);"></i>
        </div>
    </div>
</header>

<!-- Dropdown de Notificações -->
<div id="notificacoes-dropdown" style="display: none; position: absolute; top: 70px; right: 100px; width: 350px; background: white; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1000;">
    <div style="padding: 15px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
        <strong>Notificações</strong>
        <a href="<?php echo BASE_URL; ?>/admin/notificacoes.php" style="font-size: 12px;">Ver todas</a>
    </div>
    <div style="max-height: 300px; overflow-y: auto;">
        <?php if (empty($notificacoes)): ?>
            <div style="padding: 20px; text-align: center; color: var(--gray);">
                <i class="fas fa-bell-slash" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p style="font-size: 13px;">Nenhuma notificação</p>
            </div>
        <?php else: ?>
            <?php foreach ($notificacoes as $notif): ?>
                <div style="padding: 12px 15px; border-bottom: 1px solid var(--gray-light); <?php echo $notif['lida'] ? '' : 'background: #eff6ff;'; ?>">
                    <div style="font-size: 13px; font-weight: 500;"><?php echo $notif['titulo']; ?></div>
                    <div style="font-size: 12px; color: var(--secondary); margin-top: 3px;">
                        <?php echo substr($notif['mensagem'], 0, 60) . '...'; ?>
                    </div>
                    <div style="font-size: 11px; color: var(--gray); margin-top: 5px;">
                        <?php echo formatarDataHora($notif['created_at']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Dropdown do Usuário -->
<div id="user-dropdown" style="display: none; position: absolute; top: 70px; right: 25px; width: 200px; background: white; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 1000;">
    <div style="padding: 15px; border-bottom: 1px solid var(--gray-light);">
        <div style="font-weight: 600;"><?php echo $usuario['nome']; ?></div>
        <div style="font-size: 12px; color: var(--gray);"><?php echo $usuario['email']; ?></div>
    </div>
    <a href="<?php echo BASE_URL; ?>/admin/perfil.php" style="display: block; padding: 12px 15px; color: var(--dark); font-size: 13px; border-bottom: 1px solid var(--gray-light);">
        <i class="fas fa-user" style="width: 20px;"></i> Meu Perfil
    </a>
    <a href="<?php echo BASE_URL; ?>/admin/configuracoes/" style="display: block; padding: 12px 15px; color: var(--dark); font-size: 13px; border-bottom: 1px solid var(--gray-light);">
        <i class="fas fa-cog" style="width: 20px;"></i> Configurações
    </a>
    <a href="<?php echo BASE_URL; ?>/admin/logout.php" style="display: block; padding: 12px 15px; color: var(--danger); font-size: 13px;">
        <i class="fas fa-sign-out-alt" style="width: 20px;"></i> Sair
    </a>
</div>

<script>
function toggleNotificacoes() {
    const dropdown = document.getElementById('notificacoes-dropdown');
    const userDropdown = document.getElementById('user-dropdown');
    
    userDropdown.style.display = 'none';
    
    if (dropdown.style.display === 'none') {
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    const notifDropdown = document.getElementById('notificacoes-dropdown');
    
    notifDropdown.style.display = 'none';
    
    if (dropdown.style.display === 'none') {
        dropdown.style.display = 'block';
    } else {
        dropdown.style.display = 'none';
    }
}

// Fecha dropdowns ao clicar fora
document.addEventListener('click', function(e) {
    const notifDropdown = document.getElementById('notificacoes-dropdown');
    const userDropdown = document.getElementById('user-dropdown');
    const notifIcon = document.querySelector('.header-icon');
    const userMenu = document.querySelector('.user-menu');
    
    if (!notifIcon.contains(e.target) && !notifDropdown.contains(e.target)) {
        notifDropdown.style.display = 'none';
    }
    
    if (!userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
        userDropdown.style.display = 'none';
    }
});
</script>
