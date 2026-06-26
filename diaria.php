<?php
require_once __DIR__ . '/app.php';

$db = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect(url('buscar.php'));
}

// Buscar diária
$diaria = $db->fetch(
    "SELECT d.*, e.razao_social, e.cnpj, e.contato_nome, u.nome as empresa_nome, u.telefone as empresa_telefone,
            (d.vagas_total - d.vagas_preenchidas) as vagas_restantes
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE d.id = :id",
    ['id' => $id]
);

if (!$diaria) {
    redirect(url('buscar.php'));
}

// Verificar se usuário já está inscrito
$inscrito = false;
$candidatura = null;
if (isLoggedIn() && userTipo() === 'prestador') {
    $prestador = $db->fetch(
        "SELECT id FROM prestadores WHERE usuario_id = :uid AND status = 'aprovado'",
        ['uid' => userId()]
    );
    
    if ($prestador) {
        $candidatura = $db->fetch(
            "SELECT * FROM candidaturas WHERE diaria_id = :did AND prestador_id = :pid AND status != 'cancelada'",
            ['did' => $id, 'pid' => $prestador['id']]
        );
        $inscrito = $candidatura !== false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($diaria['titulo']); ?> - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <?php if ($diaria['latitude'] && $diaria['longitude']): ?>
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    <?php endif; ?>
    
    <link rel="stylesheet" href="assets/css/modern.css">
    <style>
        .detail-page { padding-top: 92px; min-height: 100vh; background: var(--bg-secondary); }
        .detail-container { max-width: 900px; margin: 0 auto; padding: 0 24px 40px; }
        
        .detail-header { background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
        .detail-badge-row { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .detail-title { font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .detail-company { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .company-avatar-lg { width: 48px; height: 48px; background: linear-gradient(135deg, var(--primary-400), var(--primary-600)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem; }
        
        .detail-price-row { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; background: var(--success-50); border-radius: 12px; }
        .price-label { font-size: 14px; color: var(--gray-600); }
        .price-value { font-size: 2.5rem; font-weight: 800; color: var(--success-500); }
        .vagas-badge { padding: 8px 16px; background: var(--primary-100); border-radius: 8px; text-align: center; }
        .vagas-badge span { display: block; font-size: 1.5rem; font-weight: 700; color: var(--primary-700); }
        .vagas-badge small { font-size: 12px; color: var(--primary-600); }
        
        .detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        
        .info-card { background: white; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); }
        .info-card h3 { font-size: 1rem; font-weight: 600; color: var(--gray-900); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        
        .info-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gray-100); }
        .info-row:last-child { border-bottom: none; }
        .info-icon { width: 40px; height: 40px; background: var(--primary-50); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .info-icon svg { width: 20px; height: 20px; color: var(--primary-600); }
        .info-content { flex: 1; }
        .info-label { font-size: 12px; color: var(--gray-500); margin-bottom: 2px; }
        .info-value { font-weight: 500; color: var(--gray-900); }
        
        .action-card { position: sticky; top: 100px; }
        .action-card.inscrito { background: var(--success-50); border: 2px solid var(--success-500); }
        
        #map { height: 200px; border-radius: 12px; margin-top: 16px; }
        
        .btn-full { width: 100%; padding: 16px; font-size: 1rem; }
        
        @media (max-width: 768px) {
            .detail-grid { grid-template-columns: 1fr; }
            .action-card { position: static; }
            .detail-price-row { flex-direction: column; gap: 16px; text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo url(); ?>" class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="nav-actions">
                <a href="<?php echo url('login.php'); ?>" class="btn btn-ghost">Entrar</a>
                <a href="<?php echo url('login.php'); ?>" class="btn btn-primary">Cadastrar</a>
            </div>
            
            <button class="mobile-menu-btn" aria-label="Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </nav>

    <div class="detail-page">
        <div class="detail-container">
            <a href="<?php echo url('buscar.php'); ?>" class="btn btn-ghost" style="margin-bottom: 16px;">
                ← Voltar para busca
            </a>
            
            <!-- Header -->
            <div class="detail-header">
                <div class="detail-badge-row">
                    <span class="funcao-badge"><?php echo sanitize($diaria['funcao']); ?></span>
                    <?php if ($diaria['forma_pagamento'] === 'na_hora'): ?>
                    <span class="diaria-badge badge-success">⚡ Pagamento na hora</span>
                    <?php else: ?>
                    <span class="diaria-badge badge-default">Pagamento posterior</span>
                    <?php endif; ?>
                </div>
                
                <h1 class="detail-title"><?php echo sanitize($diaria['titulo']); ?></h1>
                
                <div class="detail-company">
                    <div class="company-avatar-lg">
                        <?php echo substr($diaria['razao_social'] ?: $diaria['empresa_nome'], 0, 1); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo sanitize($diaria['razao_social'] ?: $diaria['empresa_nome']); ?></div>
                        <div style="font-size: 14px; color: var(--gray-500);">Empresa contratante</div>
                    </div>
                </div>
                
                <div class="detail-price-row">
                    <div>
                        <span class="price-label">Valor por diária</span>
                        <div class="price-value"><?php echo formatMoney($diaria['valor']); ?></div>
                    </div>
                    <div class="vagas-badge">
                        <span><?php echo $diaria['vagas_restantes']; ?></span>
                        <small>vagas disponíveis</small>
                    </div>
                </div>
            </div>
            
            <div class="detail-grid">
                <div>
                    <!-- Informações -->
                    <div class="info-card">
                        <h3>Informações do Evento</h3>
                        
                        <div class="info-row">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Data</span>
                                <span class="info-value"><?php echo formatDate($diaria['data_evento'], 'd/m/Y'); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Horário</span>
                                <span class="info-value"><?php echo formatTime($diaria['horario_inicio']); ?> às <?php echo formatTime($diaria['horario_fim']); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Local</span>
                                <span class="info-value"><?php echo sanitize($diaria['endereco']); ?><br>
                                <?php if ($diaria['cidade']): ?>
                                <small style="color: var(--gray-500);"><?php echo sanitize($diaria['cidade']); ?><?php if ($diaria['estado']): ?> - <?php echo sanitize($diaria['estado']); ?><?php endif; ?></small>
                                <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($diaria['descricao']): ?>
                        <div class="info-row">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Descrição</span>
                                <span class="info-value"><?php echo nl2br(sanitize($diaria['descricao'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($diaria['observacoes']): ?>
                        <div class="info-row">
                            <div class="info-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                            </div>
                            <div class="info-content">
                                <span class="info-label">Observações</span>
                                <span class="info-value"><?php echo nl2br(sanitize($diaria['observacoes'])); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($diaria['latitude'] && $diaria['longitude']): ?>
                        <div id="map"></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Card -->
                <div class="info-card action-card <?php echo $inscrito ? 'inscrito' : ''; ?>">
                    <?php if ($inscrito): ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <div style="font-size: 48px; margin-bottom: 16px;">✓</div>
                        <h3 style="color: var(--success-600); margin-bottom: 8px;">Você está inscrito!</h3>
                        <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 16px;">
                            Aguarde a data do evento para comparecer.
                        </p>
                        <a href="<?php echo url('app/index.php'); ?>" class="btn btn-primary btn-full">
                            Ver minha agenda
                        </a>
                    </div>
                    <?php elseif ($diaria['vagas_restantes'] <= 0): ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <div style="font-size: 48px; margin-bottom: 16px;">😢</div>
                        <h3 style="color: var(--gray-900); margin-bottom: 8px;">Vagas esgotadas</h3>
                        <p style="color: var(--gray-600); font-size: 14px;">
                            Todas as vagas foram preenchidas.
                        </p>
                    </div>
                    <?php elseif (!isLoggedIn()): ?>
                    <h3>Interessado nesta vaga?</h3>
                    <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 16px;">
                        Crie sua conta gratuita para se candidatar e garantir sua vaga.
                    </p>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-full">
                        Criar conta e me candidatar
                    </a>
                    <p style="text-align: center; margin-top: 12px;">
                        <a href="<?php echo url('login.php'); ?>" style="color: var(--primary-600); font-size: 14px;">Já tenho conta</a>
                    </p>
                    <?php elseif (userTipo() !== 'prestador'): ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <p style="color: var(--gray-600);">
                            Esta vaga é disponível apenas para prestadores.
                        </p>
                    </div>
                    <?php else: ?>
                    <h3>Garantir minha vaga</h3>
                    <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 16px;">
                        Ao clicar, você estará se candidatando a esta diária.
                    </p>
                    <button class="btn btn-primary btn-full" onclick="garantirVaga(<?php echo $id; ?>)">
                        Garantir Vaga
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
    <?php if ($diaria['latitude'] && $diaria['longitude']): ?>
    mapboxgl.accessToken = '<?php echo MAPBOX_TOKEN; ?>';
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [<?php echo $diaria['longitude']; ?>, <?php echo $diaria['latitude']; ?>],
        zoom: 14
    });
    new mapboxgl.Marker().setLngLat([<?php echo $diaria['longitude']; ?>, <?php echo $diaria['latitude']; ?>]).addTo(map);
    map.addControl(new mapboxgl.NavigationControl());
    <?php endif; ?>
    
    function garantirVaga(diariaId) {
        mostrarConfirmacao('Garantir Vaga', 'Deseja garantir sua vaga nesta diária?', function() {
            const btn = document.querySelector('.btn-primary.btn-full');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Processando...';
            }
            
            const formData = new FormData();
            formData.append('action', 'garantir');
            formData.append('diaria_id', diariaId);
            formData.append('prestador_id', <?php echo $prestador ? $prestador['id'] : 0; ?>);
            
            fetch('api/candidaturas.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('✅ Vaga garantida com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.error || 'Erro ao garantir vaga', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'Garantir Vaga';
                    }
                }
            })
            .catch(() => showToast('Erro de conexão', 'error'));
        });
    }
    
    function mostrarConfirmacao(titulo, mensagem, onConfirm) {
        const existente = document.getElementById('modalConfirmacao');
        if (existente) existente.remove();
        
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
        
        document.getElementById('btnCancelar').onclick = function() {
            modal.style.animation = 'fadeOut 0.2s ease';
            setTimeout(function() { modal.remove(); }, 200);
        };
        
        document.getElementById('btnConfirmar').onclick = function() {
            modal.remove();
            if (onConfirm) onConfirm();
        };
        
        modal.onclick = function(e) {
            if (e.target === modal) modal.remove();
        };
    }
    </script>
    
    <!-- Som de notificação -->
    <audio id="somNotificacao" preload="auto">
        <source src="assets/som/som.mp3" type="audio/mpeg">
    </audio>
    <script src="assets/js/notificacao-popup.js"></script>
    
    <?php if (isLoggedIn() && userTipo() === 'prestador'): ?>
    <!-- Menu Inferior (só para prestadores logados) -->
    <style>
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; display: flex; justify-content: space-around; padding: 8px 0; box-shadow: 0 -4px 6px -1px rgba(0,0,0,0.1); z-index: 100; }
        .bottom-nav .nav-item { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px 16px; color: #9CA3AF; text-decoration: none; font-size: 0.75rem; }
        .bottom-nav .nav-item.active { color: #6366F1; }
        .bottom-nav .nav-item svg { width: 24px; height: 24px; }
        body { padding-bottom: 80px; }
    </style>
    <nav class="bottom-nav">
        <a href="app/index.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Início</span>
        </a>
        <a href="app/agenda.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
            </svg>
            <span>Agenda</span>
        </a>
        <a href="app/historico.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Histórico</span>
        </a>
        <a href="app/perfil.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Perfil</span>
        </a>
    </nav>
    <?php endif; ?>
    
    <style>
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        #modalConfirmacao button:active { opacity: 0.8; }
    </style>
</body>
</html>
