<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireLogin();

if (!isLoggedIn() || userTipo() !== 'prestador') {
    redirect(APP_URL . '/login.php');
}

$db = Database::getInstance();

$prestador = $db->fetch(
    "SELECT * FROM prestadores WHERE usuario_id = :uid",
    ['uid' => userId()]
);

// Buscar histórico (diárias passadas ou check-in realizado)
$historico = $db->fetchAll(
    "SELECT c.*, d.titulo, d.data_evento, d.horario_inicio, d.horario_fim, 
            d.valor, d.cidade, d.funcao, d.nota_prestador, d.comentario_empresa, d.avaliacao_empresa,
            e.razao_social
     FROM candidaturas c
     JOIN diarias d ON c.diaria_id = d.id
     JOIN empresas e ON d.empresa_id = e.id
     WHERE c.prestador_id = :pid 
     AND c.status = 'checkin_realizado'
     ORDER BY d.data_evento DESC
     LIMIT 50",
    ['pid' => $prestador['id']]
);

// Totais
$totalGanho = array_sum(array_column($historico, 'valor'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#6366F1">
    <style>
        * { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; -webkit-tap-highlight-color: transparent !important; }
        input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
        html { overscroll-behavior-y: none !important; }
        body { overscroll-behavior: none !important; overscroll-behavior-y: none !important; touch-action: pan-y !important; }
    </style>
    <title>Histórico - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366F1;
            --primary-dark: #4F46E5;
            --success: #10B981;
            --warning: #F59E0B;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --bg: #F3F4F6;
            --card: #FFFFFF;
            --text: #111827;
            --text-secondary: #6B7280;
            --border: #E5E7EB;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding-bottom: 80px; }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 48px 20px 24px;
        }
        .header h1 { font-size: 1.5rem; font-weight: 700; }
        
        .stats-bar {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        .stat {
            flex: 1;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }
        .stat-value { font-size: 1.25rem; font-weight: 700; }
        .stat-label { font-size: 0.75rem; opacity: 0.9; }
        
        .content { padding: 20px; }
        .section-title { font-size: 1rem; font-weight: 600; margin-bottom: 12px; color: var(--gray-600); }
        
        .card {
            background: white;
            border-radius: 16px;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-body { padding: 16px; }
        .card-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 4px; }
        .card-date { font-size: 0.875rem; color: var(--gray-500); margin-bottom: 8px; }
        .card-company { font-size: 0.875rem; color: var(--text-secondary); }
        .card-value { font-size: 1.125rem; font-weight: 700; color: var(--success); text-align: right; }
        .card-funcao { font-size: 0.75rem; color: var(--gray-500); text-align: right; }
        
        .rating { display: flex; gap: 4px; margin-top: 8px; }
        .star { color: var(--warning); }
        .star.empty { color: var(--gray-300); }
        
        .empty { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 64px; margin-bottom: 16px; }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 8px 0 16px;
            box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.1);
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.75rem;
        }
        .nav-item.active { color: var(--primary); }
        .nav-item svg { width: 24px; height: 24px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Histórico</h1>
        <div class="stats-bar">
            <div class="stat">
                <div class="stat-value"><?php echo count($historico); ?></div>
                <div class="stat-label">Diárias</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo formatMoney($totalGanho); ?></div>
                <div class="stat-label">Total Ganho</div>
            </div>
        </div>
    </header>
    
    <main class="content">
        <h2 class="section-title">Diárias Realizadas</h2>
        
        <?php if (empty($historico)): ?>
        <div class="empty">
            <div class="empty-icon">📊</div>
            <h3>Nenhum histórico ainda</h3>
            <p style="color: var(--text-secondary); margin-top: 8px;">
                Suas diárias realizadas aparecerão aqui.
            </p>
        </div>
        <?php else: ?>
            <?php foreach ($historico as $h): ?>
            <article class="card">
                <div class="card-body">
                    <div class="card-row">
                        <div>
                            <h3 class="card-title"><?php echo sanitize($h['titulo']); ?></h3>
                            <p class="card-date"><?php echo formatDate($h['data_evento']); ?></p>
                            <p class="card-company"><?php echo sanitize($h['razao_social']); ?></p>
                        </div>
                        <div>
                            <div class="card-value"><?php echo formatMoney($h['valor']); ?></div>
                            <div class="card-funcao"><?php echo sanitize($h['funcao']); ?></div>
                        </div>
                    </div>
                    <?php if ($h['nota_prestador']): ?>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $h['nota_prestador'] ? '' : 'empty'; ?>">★</span>
                        <?php endfor; ?>
                        <span style="font-size: 0.75rem; color: var(--gray-500); margin-left: 8px;">
                            <?php echo number_format($h['nota_prestador'], 1); ?>/5
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
            <span>Início</span>
        </a>
        <a href="agenda.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
            </svg>
            <span>Agenda</span>
        </a>
        <a href="historico.php" class="nav-item active">
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
    
    <!-- Som de notificação -->
    <audio id="somNotificacao" preload="auto">
        <source src="../assets/som/som.mp3" type="audio/mpeg">
    </audio>
    <script src="../assets/js/notificacao-popup.js"></script>
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
