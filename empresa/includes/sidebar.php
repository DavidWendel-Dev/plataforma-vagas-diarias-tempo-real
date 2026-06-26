<?php
$user = (new Auth())->getUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitles = [
    'dashboard.php' => 'Dashboard',
    'eventos.php' => 'Eventos',
    'historico.php' => 'Histórico',
    'relatorios.php' => 'Relatórios',
    'pagamentos.php' => 'Faturamento',
    'perfil.php' => 'Meu Perfil'
];
$title = $pageTitles[$currentPage] ?? 'Painel';
?>
<aside class="admin-sidebar empresa-sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo" style="background: rgba(255,255,255,0.2);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
        </div>
        <span class="sidebar-title">DiáriasApp</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title" style="color: rgba(255,255,255,0.6);">Menu</span>
            <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="eventos.php" class="nav-link <?php echo strpos($currentPage, 'evento') !== false ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                Eventos
            </a>
            <a href="historico.php" class="nav-link <?php echo $currentPage === 'historico.php' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Histórico
            </a>
            <a href="pagamentos.php" class="nav-link <?php echo $currentPage === 'pagamentos.php' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Faturamento
            </a>
            <a href="relatorios.php" class="nav-link <?php echo $currentPage === 'relatorios.php' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Relatórios
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-title" style="color: rgba(255,255,255,0.6);">Conta</span>
            <a href="perfil.php" class="nav-link <?php echo $currentPage === 'perfil.php' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
                Perfil
            </a>
            <a href="../api/auth.php?action=logout" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sair
            </a>
        </div>
    </nav>
</aside>

<style>
.empresa-sidebar { background: linear-gradient(180deg, #10B981, #059669); }
.empresa-sidebar .nav-link { color: white; }
.empresa-sidebar .nav-link:hover { background: rgba(255,255,255,0.15); }
.empresa-sidebar .nav-link.active { background: rgba(255,255,255,0.2); }
</style>
