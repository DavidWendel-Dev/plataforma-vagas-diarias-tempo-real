<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireLogin();

if (!isLoggedIn() || userTipo() !== 'prestador') {
    redirect(APP_URL . '/login.php');
}

$db = Database::getInstance();

$prestador = $db->fetch(
    "SELECT p.*, u.nome, u.email, u.foto, u.telefone 
     FROM prestadores p 
     JOIN usuarios u ON p.usuario_id = u.id 
     WHERE u.id = :id",
    ['id' => userId()]
);

$stats = [
    'total' => $db->fetch(
        "SELECT COUNT(*) as total FROM candidaturas WHERE prestador_id = :pid AND status = 'checkin_realizado'",
        ['pid' => $prestador['id']]
    )['total'] ?? 0,
    'media' => $db->fetch(
        "SELECT AVG(d.nota_prestador) as media FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE c.prestador_id = :pid AND d.nota_prestador IS NOT NULL",
        ['pid' => $prestador['id']]
    )['media'] ?? 0,
    'ganho_total' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor), 0) as total FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE c.prestador_id = :pid AND c.status = 'checkin_realizado'",
        ['pid' => $prestador['id']]
    )['total'] ?? 0
];

$funcoes = $prestador['funcoes'] ? json_decode($prestador['funcoes'], true) : [];
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
    <title>Perfil - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            padding: 48px 20px 80px;
            text-align: center;
        }
        .header h1 { font-size: 1.25rem; font-weight: 600; }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            margin: -60px 20px 20px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 16px;
            overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 4px; }
        .profile-email { color: var(--gray-500); font-size: 0.875rem; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .stat { text-align: center; }
        .stat-value { font-size: 1.25rem; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 0.75rem; color: var(--gray-500); margin-top: 4px; }
        
        .content { padding: 20px; }
        .section { background: white; border-radius: 16px; padding: 16px; margin-bottom: 16px; }
        .section-title { font-size: 0.875rem; font-weight: 600; color: var(--gray-600); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--gray-100); }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--gray-600); font-size: 0.9375rem; }
        .info-value { font-weight: 500; text-align: right; }
        
        .funcoes-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .funcao-tag {
            padding: 6px 14px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 12px;
        }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text); }
        
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
        <h1>Meu Perfil</h1>
    </header>
    
    <div class="profile-card">
        <div class="avatar">
            <?php if ($prestador['foto']): ?>
            <img src="../uploads/prestadores/<?php echo $prestador['foto']; ?>" alt="">
            <?php else: ?>
            <?php echo substr($prestador['nome'], 0, 1); ?>
            <?php endif; ?>
        </div>
        <h2 class="profile-name"><?php echo sanitize($prestador['nome']); ?></h2>
        <p class="profile-email"><?php echo sanitize($prestador['email']); ?></p>
        
        <div class="stats-grid">
            <div class="stat">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Diárias</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $stats['media'] ? number_format($stats['media'], 1) : '-'; ?></div>
                <div class="stat-label">Nota</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo formatMoney($stats['ganho_total']); ?></div>
                <div class="stat-label">Ganho</div>
            </div>
        </div>
    </div>
    
    <main class="content">
        <div class="section">
            <h3 class="section-title">Informações</h3>
            
            <div class="info-row">
                <span class="info-label">Telefone</span>
                <span class="info-value"><?php echo $prestador['telefone'] ?: 'Não informado'; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Nascimento</span>
                <span class="info-value"><?php echo $prestador['data_nascimento'] ? formatDate($prestador['data_nascimento']) : '-'; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value" style="color: var(--success);">✓ Aprovado</span>
            </div>
        </div>
        
        <?php if (!empty($funcoes)): ?>
        <div class="section">
            <h3 class="section-title">Funções</h3>
            <div class="funcoes-list">
                <?php foreach ($funcoes as $f): ?>
                <span class="funcao-tag"><?php echo sanitize($f); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h3 class="section-title">Conta</h3>
            <a href="../api/auth.php?action=logout" class="btn btn-danger">Sair da Conta</a>
        </div>
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
        <a href="historico.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Histórico</span>
        </a>
        <a href="perfil.php" class="nav-item active">
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
