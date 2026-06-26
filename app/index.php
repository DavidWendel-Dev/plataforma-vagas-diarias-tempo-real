<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireLogin();

if (!isLoggedIn() || userTipo() !== 'prestador') {
    redirect(APP_URL . '/login.php');
}

$db = Database::getInstance();

// Obter dados do prestador
$prestador = $db->fetch(
    "SELECT p.*, u.nome, u.email, u.foto, u.telefone 
     FROM prestadores p 
     JOIN usuarios u ON p.usuario_id = u.id 
     WHERE u.id = :id",
    ['id' => userId()]
);

// Verificar se encontrou o prestador
if (!$prestador) {
    redirect(APP_URL . '/login.php');
}

// Verificar se está aprovado
if ($prestador['status'] !== 'aprovado') {
    include 'pendente.php';
    exit;
}

// Obter diárias disponíveis
$diarias = $db->fetchAll(
    "SELECT d.*, e.razao_social, u.nome as empresa_nome,
            (d.vagas_total - d.vagas_preenchidas) as vagas_restantes,
            (SELECT COUNT(*) FROM candidaturas c WHERE c.diaria_id = d.id AND c.prestador_id = :pid AND c.status != 'cancelada') as inscrito
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE d.status = 'ativa' 
     AND d.data_evento >= CURDATE()
     HAVING vagas_restantes > 0
     ORDER BY d.data_evento ASC, d.horario_inicio ASC
     LIMIT 20",
    ['pid' => $prestador['id']]
);

// Estatísticas do prestador
$stats = [
    'total_diarias' => $db->fetch(
        "SELECT COUNT(*) as total FROM candidaturas WHERE prestador_id = :pid AND status = 'checkin_realizado'",
        ['pid' => $prestador['id']]
    )['total'] ?? 0,
    
    'proximas' => $db->fetch(
        "SELECT COUNT(*) as total FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE c.prestador_id = :pid 
         AND c.status = 'confirmada' 
         AND d.data_evento >= CURDATE()",
        ['pid' => $prestador['id']]
    )['total'] ?? 0,
    
    'ganhos_mes' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor), 0) as total 
         FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE c.prestador_id = :pid 
         AND c.status = 'checkin_realizado' 
         AND MONTH(d.data_evento) = MONTH(CURDATE()) 
         AND YEAR(d.data_evento) = YEAR(CURDATE())",
        ['pid' => $prestador['id']]
    )['total'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#6366F1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
        * { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; -webkit-tap-highlight-color: transparent !important; }
        input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
        html { overscroll-behavior-y: none !important; }
        body { overscroll-behavior: none !important; overscroll-behavior-y: none !important; touch-action: pan-y !important; }
    </style>
    
    <title><?php echo APP_NAME; ?></title>
    
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366F1;
            --primary-dark: #4F46E5;
            --primary-light: #818CF8;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --bg: #F3F4F6;
            --card: #FFFFFF;
            --text: #111827;
            --text-secondary: #6B7280;
            --border: #E5E7EB;
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
            --safe-area-top: env(safe-area-inset-top, 0px);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: var(--text);
            background: var(--bg);
            overflow-x: clip;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* App Container */
        .app {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: calc(70px + var(--safe-area-bottom));
        }
        
        /* Header */
        .app-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: calc(12px + var(--safe-area-top)) 16px 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon svg { width: 18px; height: 18px; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Stats Cards */
        .stats-row {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 0 20px;
            margin-top: 20px;
            padding-bottom: 4px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 100px;
            background: white;
            border-radius: 16px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            background: white;
            padding: 8px;
            margin: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab.active {
            background: var(--primary);
            color: white;
        }
        
        /* Content */
        .content {
            flex: 1;
            padding: 0 20px;
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin: 20px 0 16px;
        }
        
        /* Diária Card */
        .diaria-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .diaria-card:active {
            transform: scale(0.98);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .card-company {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .company-avatar {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .company-name {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .card-badge {
            padding: 4px 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .card-badge.instant {
            background: rgba(16, 185, 129, 0.9);
        }
        
        .card-body {
            padding: 16px;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .card-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .meta-icon {
            width: 28px;
            height: 28px;
            background: var(--gray-100);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .meta-icon svg { width: 14px; height: 14px; color: var(--primary); }
        
        .meta-text {
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }
        
        .meta-value {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--success);
        }
        
        .price-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .vagas-badge {
            text-align: center;
            padding: 8px 16px;
            background: var(--gray-100);
            border-radius: 8px;
        }
        
        .vagas-count {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .vagas-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
            padding: 16px;
            background: var(--gray-50);
        }
        
        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:active {
            transform: scale(0.98);
        }
        
        .btn-secondary {
            background: white;
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #DC2626);
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        /* Função Badge */
        .funcao-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--primary-light);
            color: white;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            padding-bottom: calc(8px + var(--safe-area-bottom));
            box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 16px;
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.75rem;
            transition: color 0.2s;
        }
        
        .nav-item.active {
            color: var(--primary);
        }
        
        .nav-item svg {
            width: 24px;
            height: 24px;
        }
        
        /* Empty State */
        .empty {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        
        .empty h3 {
            font-size: 1.125rem;
            margin-bottom: 8px;
        }
        
        .empty p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        /* Toast */
        .toast-container {
            position: fixed;
            bottom: calc(90px + var(--safe-area-bottom));
            left: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .toast {
            background: var(--gray-800);
            color: white;
            padding: 14px 20px;
            border-radius: 12px;
            margin-top: 8px;
            animation: slideUp 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 0; }
        
        /* Responsive */
        @media (min-width: 768px) {
            .app { max-width: 480px; margin: 0 auto; }
            .bottom-nav { max-width: 480px; left: 50%; transform: translateX(-50%); }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="app-header">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <span><?php echo APP_NAME; ?></span>
                </div>
                <a href="perfil.php" class="user-avatar">
                    <?php if ($prestador['foto']): ?>
                    <img src="../uploads/prestadores/<?php echo $prestador['foto']; ?>" alt="">
                    <?php else: ?>
                    <?php echo substr($prestador['nome'], 0, 1); ?>
                    <?php endif; ?>
                </a>
            </div>
        </header>
        
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['proximas']; ?></div>
                <div class="stat-label">Próximas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_diarias']; ?></div>
                <div class="stat-label">Realizadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo formatMoney($stats['ganhos_mes']); ?></div>
                <div class="stat-label">Este mês</div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" data-tab="lista">Lista</button>
            <button class="tab" data-tab="mapa">Mapa</button>
        </div>
        
        <!-- Content -->
        <main class="content">
            <!-- Lista Tab -->
            <div id="tab-lista" class="tab-content active">
                <h2 class="section-title">Diárias Disponíveis</h2>
                
                <?php if (empty($diarias)): ?>
                <div class="empty">
                    <div class="empty-icon">📅</div>
                    <h3>Nenhuma diária disponível</h3>
                    <p>Novas oportunidades aparecerão em breve!</p>
                </div>
                <?php else: ?>
                <?php foreach ($diarias as $d): ?>
                <article class="diaria-card" data-id="<?php echo $d['id']; ?>">
                    <div class="card-header">
                        <div class="card-company">
                            <div class="company-avatar">
                                <?php echo substr($d['razao_social'] ?: 'E', 0, 1); ?>
                            </div>
                            <span class="company-name"><?php echo sanitize($d['razao_social'] ?: $d['empresa_nome']); ?></span>
                        </div>
                        <?php if ($d['forma_pagamento'] === 'na_hora'): ?>
                        <span class="card-badge instant">⚡ Na hora</span>
                        <?php else: ?>
                        <span class="card-badge">Posterior</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="card-title"><?php echo sanitize($d['titulo']); ?></h3>
                        
                        <div class="card-meta">
                            <div class="meta-item">
                                <div class="meta-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                    </svg>
                                </div>
                                <span class="meta-text">Data</span>
                                <span class="meta-value"><?php echo formatDate($d['data_evento'], 'd/m'); ?></span>
                            </div>
                            <div class="meta-item">
                                <div class="meta-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                </div>
                                <span class="meta-text">Horário</span>
                                <span class="meta-value"><?php echo formatTime($d['horario_inicio']); ?></span>
                            </div>
                            <div class="meta-item">
                                <div class="meta-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <span class="meta-text">Local</span>
                                <span class="meta-value"><?php echo sanitize($d['cidade'] ?: '-'); ?></span>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <div>
                                <div class="price"><?php echo formatMoney($d['valor']); ?></div>
                                <div class="price-label">por diária</div>
                            </div>
                            <div class="vagas-badge">
                                <span class="vagas-count"><?php echo $d['vagas_restantes']; ?></span>
                                <span class="vagas-label">vaga(s)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <?php if ($d['inscrito'] > 0): ?>
                        <button class="btn btn-success" disabled>✓ Inscrito</button>
                        <button class="btn btn-danger" onclick="cancelar(<?php echo $d['id']; ?>)">Cancelar</button>
                        <?php else: ?>
                        <button class="btn btn-secondary" onclick="detalhes(<?php echo $d['id']; ?>)">Detalhes</button>
                        <button class="btn btn-primary" onclick="garantir(<?php echo $d['id']; ?>)">Garantir Vaga</button>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Mapa Tab -->
            <div id="tab-mapa" class="tab-content" style="display:none;">
                <div id="map" style="height:60vh;border-radius:12px;"></div>
            </div>
        </main>
        
        <!-- Bottom Nav -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Início</span>
            </a>
            <a href="agenda.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Agenda</span>
            </a>
            <a href="historico.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span>Histórico</span>
            </a>
            <a href="perfil.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Perfil</span>
            </a>
        </nav>
    </div>
    
    <div class="toast-container" id="toasts"></div>

    <!-- Mapbox -->
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    
    <!-- Som de notificação -->
    <audio id="somNotificacao" preload="auto">
        <source src="../assets/som/som.mp3" type="audio/mpeg">
    </audio>
    
    <script>
    // ====== SISTEMA DE NOTIFICAÇÃO EM TEMPO REAL ======
    const APP_URL = '<?php echo APP_URL; ?>';
    const MAPBOX_TOKEN = '<?php echo MAPBOX_TOKEN; ?>';
    let map = null;
    
    // IDs das diárias que já estão na tela
    let idsDiarias = [<?php echo implode(',', array_column($diarias ?? [], 'id')); ?>];
    
    // ID da última verificação (começa com as que já existem)
    let ultimaVerificacao = <?php echo !empty($diarias) ? max(array_column($diarias, 'id')) : 0; ?>;
    
    // Iniciar verificação automática
    console.log('🚀 Iniciando sistema de notificação...');
    console.log('📊 IDs já carregados:', idsDiarias);
    console.log('📊 Último ID:', ultimaVerificacao);
    
    // Verificar a cada 3 segundos
    setInterval(function() {
        verificarNovasVagas();
    }, 3000);
    
    // Primeira verificação em 2 segundos
    setTimeout(function() {
        verificarNovasVagas();
    }, 2000);
    
    function verificarNovasVagas() {
        const url = '../api/verificar_vagas.php?ultimo_id=' + ultimaVerificacao;
        
        fetch(url)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.diarias && data.diarias.length > 0) {
                    // Processar cada nova vaga
                    data.diarias.forEach(function(vaga) {
                        // Verificar se já não está na lista
                        if (!idsDiarias.includes(vaga.id)) {
                            console.log('🆕 Nova vaga:', vaga.titulo);
                            
                            // Adicionar à lista
                            idsDiarias.push(vaga.id);
                            
                            // Atualizar último ID
                            if (vaga.id > ultimaVerificacao) {
                                ultimaVerificacao = vaga.id;
                            }
                            
                            // Adicionar card na tela
                            adicionarCardVaga(vaga);
                            
                            // Tocar som
                            tocarNotificacao();
                            
                            // Mostrar toast
                            toast('🔔 Nova vaga: ' + vaga.titulo, 'success');
                        }
                    });
                }
            })
            .catch(function(error) {
                console.error('Erro ao verificar:', error);
            });
    }
    
    function tocarNotificacao() {
        console.log('🔊 Tentando tocar som...');
        
        const audio = document.getElementById('somNotificacao');
        if (audio) {
            audio.currentTime = 0;
            audio.volume = 1.0;
            audio.play()
                .then(function() {
                    console.log('✅ SOM TOCOU!');
                })
                .catch(function(e) {
                    console.log('❌ Erro no som:', e.message);
                });
        } else {
            console.log('❌ Elemento de áudio não encontrado');
        }
        
        // Vibração
        if (navigator.vibrate) {
            navigator.vibrate([300, 100, 300]);
        }
    }
    
    function adicionarCardVaga(vaga) {
        const container = document.querySelector('#tab-lista');
        const emptyDiv = container.querySelector('.empty');
        if (emptyDiv) emptyDiv.remove();
        
        // Criar card HTML completo igual ao original
        const card = document.createElement('article');
        card.className = 'diaria-card nova';
        card.dataset.id = vaga.id;
        card.style.animation = 'slideIn 0.5s ease';
        
        const valor = parseFloat(vaga.valor).toFixed(2).replace('.', ',');
        const vagasRestantes = vaga.vagas_total - vaga.vagas_preenchidas;
        const data = vaga.data_evento ? vaga.data_evento.split('-').reverse().join('/').substring(0, 5) : '';
        const horario = vaga.horario_inicio ? vaga.horario_inicio.substring(0, 5) : '';
        const empresa = vaga.razao_social || vaga.empresa_nome || 'Empresa';
        const cidade = vaga.cidade || '';
        
        card.innerHTML = `
            <div class="card-header">
                <div class="card-company">
                    <div class="company-avatar">${empresa.charAt(0)}</div>
                    <span class="company-name">${empresa}</span>
                </div>
                <span class="card-badge">🆕 NOVA</span>
            </div>
            <div class="card-body">
                <h3 class="card-title">${vaga.titulo}</h3>
                <div class="card-meta">
                    <div class="meta-item">
                        <div class="meta-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                            </svg>
                        </div>
                        <span class="meta-text">Data</span>
                        <span class="meta-value">${data}</span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <span class="meta-text">Horário</span>
                        <span class="meta-value">${horario}</span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <span class="meta-text">Local</span>
                        <span class="meta-value">${cidade}</span>
                    </div>
                </div>
                <div class="card-footer">
                    <div>
                        <div class="price">R$ ${valor}</div>
                        <div class="price-label">por diária</div>
                    </div>
                    <div class="vagas-badge">
                        <span class="vagas-count">${vagasRestantes}</span>
                        <span class="vagas-label">vaga(s)</span>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn btn-secondary" onclick="detalhes(${vaga.id})">Detalhes</button>
                <button class="btn btn-primary" onclick="garantir(${vaga.id})">Garantir Vaga</button>
            </div>
        `;
        
        // Inserir no topo
        const firstCard = container.querySelector('.diaria-card');
        if (firstCard) {
            container.insertBefore(card, firstCard);
        } else {
            container.appendChild(card);
        }
    }
    
    // Adicionar CSS para animação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .diaria-card.nova { border: 2px solid #10B981; }
    `;
    document.head.appendChild(style);
    
    // Tabs
    document.querySelectorAll('.tab').forEach(t => {
        t.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            
            const tab = this.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.getElementById('tab-' + tab).style.display = 'block';
            
            if (tab === 'mapa' && !map) initMap();
            if (tab === 'mapa' && map) setTimeout(() => map.resize(), 100);
        });
    });
    
    // Map
    function initMap() {
        mapboxgl.accessToken = MAPBOX_TOKEN;
        
        navigator.geolocation?.getCurrentPosition(pos => {
            createMap(pos.coords.longitude, pos.coords.latitude);
        }, () => createMap(-46.6333, -23.5505));
    }
    
    function createMap(lng, lat) {
        map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [lng, lat],
            zoom: 12
        });
        
        map.addControl(new mapboxgl.NavigationControl());
        
        // User marker
        new mapboxgl.Marker({ color: '#6366F1' }).setLngLat([lng, lat]).addTo(map);
        
        // Load diarias
        fetch('../api/diarias.php?action=mapa')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.diarias) {
                    data.diarias.forEach(d => {
                        if (d.latitude && d.longitude) {
                            new mapboxgl.Marker({ color: '#10B981' })
                                .setLngLat([d.longitude, d.latitude])
                                .setPopup(new mapboxgl.Popup().setHTML(
                                    `<strong>${d.titulo}</strong><br>
                                    <small>${d.funcao} - ${formatMoney(d.valor)}</small>`
                                ))
                                .addTo(map);
                        }
                    });
                }
            });
    }
    
    // Garantir vaga
    function garantir(id) {
        mostrarConfirmacao('Garantir Vaga', 'Deseja garantir sua vaga nesta diária?', function() {
            const btn = document.querySelector(`.diaria-card[data-id="${id}"] .btn-primary`);
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Processando...';
            }
            
            const formData = new FormData();
            formData.append('action', 'garantir');
            formData.append('diaria_id', id);
            formData.append('prestador_id', <?php echo $prestador['id']; ?>);
            
            fetch('../api/candidaturas.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    toast('✅ Vaga garantida com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toast(d.error || 'Erro ao garantir vaga', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Garantir Vaga';
                    }
                }
            });
        });
    }
    
    // Cancelar
    function cancelar(id) {
        mostrarConfirmacao('Cancelar Inscrição', 'Tem certeza que deseja cancelar sua inscrição?', function() {
            const formData = new FormData();
            formData.append('action', 'cancelar');
            formData.append('diaria_id', id);
            formData.append('prestador_id', <?php echo $prestador['id']; ?>);
            
            fetch('../api/candidaturas.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    toast('Inscrição cancelada', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toast(d.error || 'Erro', 'error');
                }
            });
        });
    }
    
    // Detalhes
    function detalhes(id) {
        window.location.href = '../diaria.php?id=' + id;
    }
    
    // Modal de Confirmação Customizado
    function mostrarConfirmacao(titulo, mensagem, onConfirm) {
        // Remover modal existente se houver
        const existente = document.getElementById('modalConfirmacao');
        if (existente) existente.remove();
        
        // Criar modal
        const modal = document.createElement('div');
        modal.id = 'modalConfirmacao';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px;animation:fadeIn 0.2s ease;';
        
        modal.innerHTML = `
            <div style="background:white;border-radius:20px;width:100%;max-width:340px;overflow:hidden;animation:slideUp 0.3s ease;">
                <div style="padding:24px;text-align:center;">
                    <div style="width:60px;height:60px;background:linear-gradient(135deg,#6366F1,#4F46E5);border-radius:50%;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <h3 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#111827;">${titulo}</h3>
                    <p style="margin:0;color:#6B7280;font-size:15px;">${mensagem}</p>
                </div>
                <div style="display:flex;border-top:1px solid #E5E7EB;">
                    <button id="btnCancelar" style="flex:1;padding:16px;border:none;background:#F9FAFB;color:#6B7280;font-size:16px;font-weight:600;cursor:pointer;border-right:1px solid #E5E7EB;">Cancelar</button>
                    <button id="btnConfirmar" style="flex:1;padding:16px;border:none;background:linear-gradient(135deg,#6366F1,#4F46E5);color:white;font-size:16px;font-weight:600;cursor:pointer;">Confirmar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Eventos
        document.getElementById('btnCancelar').onclick = function() {
            modal.style.animation = 'fadeOut 0.2s ease';
            setTimeout(function() { modal.remove(); }, 200);
        };
        
        document.getElementById('btnConfirmar').onclick = function() {
            modal.remove();
            if (onConfirm) onConfirm();
        };
        
        // Fechar clicando fora
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        };
    }
    
    // CSS para animações
    const styleModal = document.createElement('style');
    styleModal.textContent = `
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        #modalConfirmacao button:active { opacity: 0.8; }
    `;
    document.head.appendChild(styleModal);
    // Toast
    function toast(msg, type = 'info') {
        const el = document.createElement('div');
        el.className = 'toast ' + type;
        el.textContent = msg;
        document.getElementById('toasts').appendChild(el);
        setTimeout(() => el.remove(), 3000);
    }
    
    // Format
    function formatMoney(v) {
        return 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    }
    </script>
<script>
// Bloquear copiar, colar, menu de contexto
document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
document.addEventListener('selectstart', function(e) { if(e.target.tagName!=='INPUT'&&e.target.tagName!=='TEXTAREA') { e.preventDefault(); return false; } });
document.addEventListener('dragstart', function(e) { e.preventDefault(); return false; });
document.addEventListener('copy', function(e) { e.preventDefault(); return false; });
document.addEventListener('cut', function(e) { e.preventDefault(); return false; });
document.addEventListener('paste', function(e) { if(e.target.tagName!=='INPUT'&&e.target.tagName!=='TEXTAREA') { e.preventDefault(); return false; } });
</script>
</body>
</html>
