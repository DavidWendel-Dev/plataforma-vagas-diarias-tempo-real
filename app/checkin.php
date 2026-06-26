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
    "SELECT p.*, u.nome, u.foto 
     FROM prestadores p 
     JOIN usuarios u ON p.usuario_id = u.id 
     WHERE u.id = :id",
    ['id' => userId()]
);

if (!$prestador || $prestador['status'] !== 'aprovado') {
    redirect(APP_URL . '/app/index.php');
}

// ID da candidatura (não da diária)
$candidaturaId = (int)($_GET['id'] ?? 0);

if ($candidaturaId <= 0) {
    redirect(APP_URL . '/app/agenda.php');
}

// Buscar candidatura
$candidatura = $db->fetch(
    "SELECT c.*, d.titulo, d.data_evento, d.horario_inicio, d.horario_fim, 
            d.valor, d.endereco, d.cidade, d.estado, d.funcao, d.codigo_checkin,
            d.latitude, d.longitude, d.empresa_id,
            e.razao_social, u.nome as empresa_nome, u.telefone as empresa_telefone
     FROM candidaturas c
     JOIN diarias d ON c.diaria_id = d.id
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE c.id = :id AND c.prestador_id = :pid AND c.status != 'cancelada'",
    ['id' => $candidaturaId, 'pid' => $prestador['id']]
);

if (!$candidatura) {
    redirect(APP_URL . '/app/agenda.php');
}

// Verificar se check-in já foi feito
$checkinFeito = ($candidatura['status'] === 'checkin_realizado');

// Dados para o template
$dataEvento = formatDate($candidatura['data_evento'], 'd/m/Y');
$horarioInicio = formatTime($candidatura['horario_inicio']);
$horarioFim = formatTime($candidatura['horario_fim']);
$valor = formatMoney($candidatura['valor']);
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
    
    <title>Check-in - <?php echo APP_NAME; ?></title>
    
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <?php if ($candidatura['latitude'] && $candidatura['longitude']): ?>
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <?php endif; ?>
    
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
        
        .app {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: calc(80px + var(--safe-area-bottom));
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: calc(12px + var(--safe-area-top)) 16px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.875rem;
        }
        
        .back-btn svg { width: 18px; height: 18px; }
        
        .header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            flex: 1;
        }
        
        /* Conteúdo */
        .content {
            flex: 1;
            padding: 20px;
        }
        
        /* Card da diária */
        .diaria-info {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .diaria-titulo {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .diaria-empresa {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .info-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 0.9375rem;
            font-weight: 600;
        }
        
        .info-value.price {
            color: var(--success);
            font-size: 1.25rem;
            font-weight: 800;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .status-badge.confirmada {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .status-badge.checkin_realizado {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        /* Check-in Card */
        .checkin-card {
            background: white;
            border-radius: 16px;
            padding: 24px 20px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .checkin-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        
        .checkin-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        
        .checkin-icon.success {
            background: linear-gradient(135deg, var(--success), #059669);
        }
        
        .checkin-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .checkin-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 20px;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1.125rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 4px;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-input::placeholder {
            color: var(--gray-300);
            letter-spacing: 2px;
        }
        
        /* Botões */
        .btn {
            width: 100%;
            padding: 16px;
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
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }
        
        .btn-outline {
            background: white;
            border: 2px solid var(--border);
            color: var(--text);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn:active:not(:disabled) {
            transform: scale(0.98);
        }
        
        /* Mapa */
        #map {
            height: 200px;
            border-radius: 12px;
            margin-top: 16px;
        }
        
        /* Link Empresa */
        .empresa-contato {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: var(--gray-50);
            border-radius: 10px;
            margin-top: 16px;
            text-decoration: none;
            color: var(--text);
        }
        
        .empresa-contato svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
        }
        
        .empresa-contato-info {
            flex: 1;
        }
        
        .empresa-contato-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .empresa-contato-value {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: calc(90px + var(--safe-area-bottom));
            left: 20px;
            right: 20px;
            padding: 14px 20px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            font-size: 0.875rem;
            z-index: 1000;
            animation: slideUp 0.3s ease;
            text-align: center;
        }
        
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
        
        .nav-item.active { color: var(--primary); }
        
        .nav-item svg { width: 24px; height: 24px; }
        
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
        <header class="header">
            <div class="header-content">
                <a href="agenda.php" class="back-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Voltar
                </a>
                <h1>Check-in</h1>
            </div>
        </header>
        
        <!-- Conteúdo -->
        <main class="content">
            <!-- Info da Diária -->
            <div class="diaria-info">
                <span class="status-badge <?php echo $checkinFeito ? 'checkin_realizado' : 'confirmada'; ?>">
                    <?php if ($checkinFeito): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Check-in Realizado
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Confirmada
                    <?php endif; ?>
                </span>
                
                <h2 class="diaria-titulo"><?php echo sanitize($candidatura['titulo']); ?></h2>
                <p class="diaria-empresa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                        <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/>
                    </svg>
                    <?php echo sanitize($candidatura['razao_social']); ?>
                </p>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Data</span>
                        <span class="info-value"><?php echo $dataEvento; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Horário</span>
                        <span class="info-value"><?php echo $horarioInicio; ?> - <?php echo $horarioFim; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Função</span>
                        <span class="info-value"><?php echo sanitize($candidatura['funcao']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Valor</span>
                        <span class="info-value price"><?php echo $valor; ?></span>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <span class="info-label">Endereço</span>
                        <span class="info-value"><?php echo sanitize($candidatura['endereco']); ?> - <?php echo sanitize($candidatura['cidade'] . '/' . $candidatura['estado']); ?></span>
                    </div>
                </div>
                
                <?php if ($candidatura['empresa_telefone']): ?>
                <a href="tel:<?php echo sanitize($candidatura['empresa_telefone']); ?>" class="empresa-contato">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.36 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.34 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="empresa-contato-info">
                        <div class="empresa-contato-label">Contato da Empresa</div>
                        <div class="empresa-contato-value"><?php echo sanitize($candidatura['empresa_telefone']); ?></div>
                    </div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ($candidatura['latitude'] && $candidatura['longitude']): ?>
                <div id="map"></div>
                <?php endif; ?>
            </div>
            
            <!-- Check-in -->
            <div class="checkin-card">
                <?php if ($checkinFeito): ?>
                    <div class="checkin-icon success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3 class="checkin-title">Check-in Realizado!</h3>
                    <p class="checkin-text">
                        Seu check-in foi confirmado em <?php echo formatDate($candidatura['data_checkin'], 'd/m/Y H:i'); ?>.<br>
                        Bom trabalho!
                    </p>
                    <a href="historico.php" class="btn btn-outline">Ver Histórico</a>
                    
                <?php else: ?>
                    <div class="checkin-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <h3 class="checkin-title">Fazer Check-in</h3>
                    <p class="checkin-text">
                        Digite o código de check-in fornecido pela empresa no local do evento.
                    </p>
                    
                    <form id="formCheckin" onsubmit="return false;">
                        <div class="form-group">
                            <label class="form-label">Código de Check-in</label>
                            <input 
                                type="text" 
                                id="codigoCheckin" 
                                class="form-input" 
                                placeholder="ABC123" 
                                maxlength="6" 
                                autocomplete="off"
                                required
                            >
                        </div>
                        
                        <button type="submit" class="btn btn-success" id="btnCheckin">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
                                <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                            Confirmar Check-in
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </main>
        
        <!-- Bottom Nav -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Início</span>
            </a>
            <a href="agenda.php" class="nav-item active">
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
    
    <!-- Som de notificação -->
    <audio id="somNotificacao" preload="auto">
        <source src="../assets/som/som.mp3" type="audio/mpeg">
    </audio>
    <script src="../assets/js/notificacao-popup.js"></script>
    
    <script>
    const PRESTADOR_ID = <?php echo $prestador['id']; ?>;
    const CANDIDATURA_ID = <?php echo $candidaturaId; ?>;
    const MAPBOX_TOKEN = '<?php echo MAPBOX_TOKEN; ?>';
    
    // Mapa
    <?php if ($candidatura['latitude'] && $candidatura['longitude']): ?>
    mapboxgl.accessToken = MAPBOX_TOKEN;
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [<?php echo $candidatura['longitude']; ?>, <?php echo $candidatura['latitude']; ?>],
        zoom: 14
    });
    new mapboxgl.Marker().setLngLat([<?php echo $candidatura['longitude']; ?>, <?php echo $candidatura['latitude']; ?>]).addTo(map);
    map.addControl(new mapboxgl.NavigationControl());
    <?php endif; ?>
    
    // Check-in
    const form = document.getElementById('formCheckin');
    const btn = document.getElementById('btnCheckin');
    const input = document.getElementById('codigoCheckin');
    
    if (form) {
        // Auto-uppercase
        input.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const codigo = input.value.trim();
            
            if (!codigo) {
                mostrarToast('Digite o código de check-in', 'error');
                return;
            }
            
            if (codigo.length < 4) {
                mostrarToast('Código muito curto', 'error');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = 'Processando...';
            
            const formData = new FormData();
            formData.append('action', 'checkin');
            formData.append('candidatura_id', CANDIDATURA_ID);
            formData.append('codigo', codigo);
            formData.append('prestador_id', PRESTADOR_ID);
            
            fetch('../api/candidaturas.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    mostrarToast('✅ Check-in realizado com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarToast(d.error || 'Erro no check-in', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Confirmar Check-in';
                }
            })
            .catch(() => {
                mostrarToast('Erro de conexão', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Confirmar Check-in';
            });
        });
    }
    
    function mostrarToast(msg, tipo) {
        const t = document.createElement('div');
        t.className = 'toast ' + tipo;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3000);
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
