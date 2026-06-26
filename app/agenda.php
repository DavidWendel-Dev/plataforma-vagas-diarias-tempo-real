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

// Buscar candidaturas futuras
$candidaturas = $db->fetchAll(
    "SELECT c.*, d.titulo, d.data_evento, d.horario_inicio, d.horario_fim, 
            d.valor, d.endereco, d.cidade, d.estado, d.funcao, d.codigo_checkin,
            e.razao_social, u.telefone as empresa_telefone
     FROM candidaturas c
     JOIN diarias d ON c.diaria_id = d.id
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE c.prestador_id = :pid 
     AND c.status != 'cancelada'
     AND d.data_evento >= CURDATE()
     ORDER BY d.data_evento ASC, d.horario_inicio ASC",
    ['pid' => $prestador['id']]
);
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
    <title>Agenda - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css">
    <style>
        :root {
            --primary: #6366F1;
            --primary-dark: #4F46E5;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
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
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-content { display: flex; align-items: center; justify-content: space-between; }
        .back-btn { display: flex; align-items: center; gap: 8px; color: white; text-decoration: none; opacity: 0.9; }
        .back-btn svg { width: 20px; height: 20px; }
        .header h1 { font-size: 1.5rem; font-weight: 700; }
        
        .content { padding: 20px; }
        
        .card {
            background: white;
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .card-date {
            background: var(--primary);
            color: white;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .card-body { padding: 16px; }
        
        .card-title { font-size: 1.125rem; font-weight: 600; margin-bottom: 8px; }
        .card-company { color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 16px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
        .info-item { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; }
        .info-item svg { width: 16px; height: 16px; color: var(--primary); }
        
        .status-badge {
            display: inline-flex;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-confirmada { background: var(--success); color: white; }
        .status-checkin_realizado { background: var(--gray-600); color: white; }
        
        .card-footer {
            display: flex;
            gap: 8px;
            padding: 16px;
            background: var(--gray-50);
            border-top: 1px solid var(--border);
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text); }
        .btn-success { background: var(--success); color: white; }
        
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
        <div class="header-content">
            <a href="index.php" class="back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Voltar
            </a>
        </div>
        <h1 style="margin-top: 16px;">Minha Agenda</h1>
    </header>
    
    <main class="content">
        <?php if (empty($candidaturas)): ?>
        <div class="empty">
            <div class="empty-icon">📅</div>
            <h3>Nenhuma diária agendada</h3>
            <p style="color: var(--text-secondary); margin-top: 8px;">
                Garanta vagas nas diárias disponíveis para visualizar aqui.
            </p>
        </div>
        <?php else: ?>
            <?php 
            $dataAtual = '';
            foreach ($candidaturas as $c): 
                $dataFormatada = formatDate($c['data_evento'], 'l, d/m/Y');
                if ($dataFormatada !== $dataAtual):
                    $dataAtual = $dataFormatada;
            ?>
            <div class="card-date"><?php echo $dataFormatada; ?></div>
            <?php endif; ?>
            
            <article class="card">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <h3 class="card-title"><?php echo sanitize($c['titulo']); ?></h3>
                        <span class="status-badge status_<?php echo $c['status']; ?>">
                            <?php echo $c['status'] === 'confirmada' ? 'Confirmada' : 'Check-in OK'; ?>
                        </span>
                    </div>
                    
                    <p class="card-company"><?php echo sanitize($c['razao_social']); ?></p>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span><?php echo formatTime($c['horario_inicio']); ?> - <?php echo formatTime($c['horario_fim']); ?></span>
                        </div>
                        <div class="info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span><?php echo sanitize($c['cidade']); ?></span>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border);">
                        <span style="font-size: 1.25rem; font-weight: 700; color: var(--success);"><?php echo formatMoney($c['valor']); ?></span>
                        <span style="font-size: 0.875rem; color: var(--text-secondary);"><?php echo sanitize($c['funcao']); ?></span>
                    </div>
                </div>
                
                <?php if ($c['status'] === 'confirmada'): ?>
                <div class="card-footer">
                    <a href="checkin.php?id=<?php echo $c['id']; ?>" class="btn btn-success">Fazer Check-in</a>
                    <button class="btn btn-outline" onclick="verDetalhes(<?php echo $c['diaria_id']; ?>)">Detalhes</button>
                </div>
                <?php endif; ?>
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
        <a href="agenda.php" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
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
    
    <!-- Som de notificação -->
    <audio id="somNotificacao" preload="auto">
        <source src="../assets/som/som.mp3" type="audio/mpeg">
    </audio>
    <script src="../assets/js/notificacao-popup.js"></script>
    <script>
    function verDetalhes(id) {
        window.location.href = '../diaria.php?id=' + id;
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
