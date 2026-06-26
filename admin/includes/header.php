<?php
$user = (new Auth())->getUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitles = [
    'dashboard.php' => 'Dashboard',
    'diarias.php' => 'Diárias',
    'diaria-nova.php' => 'Nova Diária',
    'diaria-editar.php' => 'Editar Diária',
    'prestadores.php' => 'Prestadores',
    'empresas.php' => 'Empresas',
    'moderacao.php' => 'Moderação',
    'pagamentos.php' => 'Pagamentos',
    'relatorios.php' => 'Relatórios',
    'configuracoes.php' => 'Configurações'
];
$title = $pageTitles[$currentPage] ?? 'Administração';
?>
<header class="admin-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <h1 class="page-title"><?php echo $title; ?></h1>
    </div>
    
    <div class="header-right">
        <div class="header-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" placeholder="Buscar..." id="globalSearch">
        </div>
        
        <!-- Notificações -->
        <div class="notif-wrapper" style="position:relative;">
            <button id="btnNotif" style="background:transparent;border:none;color:inherit;cursor:pointer;padding:8px;border-radius:50%;position:relative;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span id="badgeNotif" style="position:absolute;top:2px;right:2px;background:#EF4444;color:white;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0 4px;">0</span>
            </button>
            <div id="dropdownNotif" style="display:none;position:absolute;top:50px;right:0;width:340px;max-height:480px;overflow-y:auto;background:white;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.15);z-index:1000;border:1px solid #E5E7EB;">
                <div style="padding:14px 16px;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center;background:#F9FAFB;border-radius:12px 12px 0 0;">
                    <strong style="color:#111827;">Notificações</strong>
                    <button id="btnMarcarTodas" style="background:transparent;border:none;color:#4F46E5;font-size:12px;cursor:pointer;font-weight:600;">Marcar todas lidas</button>
                </div>
                <div id="listaNotif"></div>
            </div>
        </div>
        
        <div class="user-menu" id="userMenu">
            <div class="user-avatar">
                <?php if ($user['foto']): ?>
                <img src="../uploads/prestadores/<?php echo $user['foto']; ?>" alt="">
                <?php else: ?>
                <?php echo substr($user['nome'], 0, 1); ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <strong><?php echo sanitize($user['nome']); ?></strong>
                <span>Administrador</span>
            </div>
        </div>
    </div>
</header>

<!-- Som e notificação de nova candidatura -->
<audio id="somCandidatura" preload="auto">
    <source src="../assets/som/som.mp3" type="audio/mpeg">
</audio>
<div id="toastCandidatura" style="position:fixed;top:80px;right:20px;background:#10B981;color:white;padding:14px 20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;display:none;animation:slideUp 0.3s ease;">
    <div style="display:flex;align-items:center;gap:10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
            <strong id="toastTitulo">Novo candidato!</strong>
            <div id="toastMsg" style="font-size:0.8125rem;opacity:0.9;margin-top:2px;"></div>
        </div>
    </div>
</div>
<style>@keyframes slideUp{from{transform:translateY(-20px);opacity:0}to{transform:translateY(0);opacity:1}}</style>
<script>
(function(){
    let ultimoId = 0;
    let audioLiberado = false;
    let ultimoSomTocado = 0;
    
    // Libera áudio após primeira interação do usuário (navegadores bloqueiam sem interação)
    function liberarAudio() {
        if (audioLiberado) return;
        audioLiberado = true;
        const audio = document.getElementById('somCandidatura');
        audio.volume = 0;
        audio.play().then(() => { audio.pause(); audio.volume = 1; audio.currentTime = 0; }).catch(() => {});
        console.log('🔔 Áudio liberado pelo usuário');
    }
    document.addEventListener('click', liberarAudio);
    document.addEventListener('touchstart', liberarAudio);
    document.addEventListener('keydown', liberarAudio);
    
    // Inicializar - pegar ID atual sem tocar som
    fetch('../api/verificar_candidaturas.php?ultimo_id=0')
        .then(r => r.json())
        .then(d => { 
            if (d.ultimo_id !== undefined) ultimoId = d.ultimo_id; 
            console.log('🔔 Som admin inicializado. Último ID:', ultimoId, '| Ativo:', d.ativo);
        })
        .catch(err => console.log('Erro ao inicializar som:', err));
    
    // Verificar a cada 5 segundos
    setInterval(() => {
        fetch('../api/verificar_candidaturas.php?ultimo_id=' + ultimoId)
            .then(r => r.json())
            .then(d => {
                if (d.ultimo_id !== undefined) ultimoId = d.ultimo_id;
                
                console.log('🔔 Verificação - som:', d.som, '| candidaturas:', d.candidaturas?.length, '| ultimoId:', ultimoId);
                
                if (d.som && d.candidaturas && d.candidaturas.length > 0) {
                    // Evitar tocar o som repetidas vezes para mesma candidatura
                    const novoMaxId = d.candidaturas[d.candidaturas.length - 1].id;
                    console.log('🔔 Novo candidato detectado! ID:', novoMaxId);
                    if (Date.now() - ultimoSomTocado < 3000) {
                        console.log('🔔 Som ignorado (muito rápido)');
                        return;
                    }
                    ultimoSomTocado = Date.now();
                    
                    // Tocar som
                    const audio = document.getElementById('somCandidatura');
                    audio.currentTime = 0;
                    const playPromise = audio.play();
                    if (playPromise) {
                        playPromise.then(() => console.log('🔔 Som tocando!')).catch(e => console.log('🔔 Navegador bloqueou som:', e));
                    }
                    
                    // Mostrar toast para cada novo candidato
                    d.candidaturas.forEach(c => {
                        const toast = document.getElementById('toastCandidatura');
                        document.getElementById('toastTitulo').textContent = '✓ Novo candidato!';
                        document.getElementById('toastMsg').textContent = c.prestador_nome + ' aceitou: ' + c.titulo;
                        toast.style.display = 'block';
                        toast.style.animation = 'slideUp 0.3s ease';
                        setTimeout(() => { toast.style.display = 'none'; }, 6000);
                    });
                }
            })
            .catch(err => console.log('🔔 Erro na verificação:', err));
    }, 5000);
})();

// Notificações (sino)
(function(){
    const btnNotif = document.getElementById('btnNotif');
    const dropdown = document.getElementById('dropdownNotif');
    const lista = document.getElementById('listaNotif');
    const badge = document.getElementById('badgeNotif');
    const btnMarcarTodas = document.getElementById('btnMarcarTodas');
    
    function tempoRelativo(minutos) {
        if (minutos < 1) return 'agora';
        if (minutos < 60) return minutos + ' min';
        if (minutos < 1440) return Math.floor(minutos / 60) + ' h';
        return Math.floor(minutos / 1440) + ' d';
    }
    
    function carregarNotificacoes() {
        fetch('../api/notificacoes.php?action=listar')
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                
                // Atualizar badge (sempre visível, mostra 0 quando não tem)
                badge.textContent = d.nao_lidas > 99 ? '99+' : d.nao_lidas;
                badge.style.display = 'flex';
                if (d.nao_lidas == 0) {
                    badge.style.background = '#9CA3AF';
                } else {
                    badge.style.background = '#EF4444';
                }
                
                // Renderizar lista
                if (d.notificacoes.length === 0) {
                    lista.innerHTML = '<div style="padding:40px 20px;text-align:center;color:#6B7280;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="2" style="margin:0 auto 8px;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><p>Sem notificações</p></div>';
                } else {
                    lista.innerHTML = d.notificacoes.map(n => `
                        <div data-id="${n.id}" data-link="${n.link || ''}" style="padding:12px 16px;border-bottom:1px solid #F3F4F6;cursor:pointer;background:${n.lida == 0 ? '#F0F9FF' : 'white'};" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='${n.lida == 0 ? '#F0F9FF' : 'white'}'" >
                            <div style="display:flex;gap:10px;align-items:flex-start;">
                                <div style="width:8px;height:8px;border-radius:50%;background:${n.lida == 0 ? '#4F46E5' : 'transparent'};margin-top:6px;flex-shrink:0;"></div>
                                <div style="flex:1;min-width:0;">
                                    <strong style="color:#111827;font-size:14px;">${n.titulo}</strong>
                                    <div style="color:#4B5563;font-size:13px;margin-top:2px;">${n.mensagem || ''}</div>
                                    <small style="color:#9CA3AF;font-size:11px;">há ${tempoRelativo(n.minutos)}</small>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    // Marcar como lida ao clicar
                    lista.querySelectorAll('[data-id]').forEach(el => {
                        el.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            const link = this.getAttribute('data-link');
                            fetch('../api/notificacoes.php?action=marcar_lida&id=' + id);
                            if (link) {
                                window.location.href = link;
                            } else {
                                this.style.background = 'white';
                                this.querySelector('div > div').style.background = 'transparent';
                            }
                        });
                    });
                }
            })
            .catch(() => {});
    }
    
    // Toggle dropdown
    btnNotif.addEventListener('click', function(e) {
        e.stopPropagation();
        if (dropdown.style.display === 'none') {
            dropdown.style.display = 'block';
            carregarNotificacoes();
        } else {
            dropdown.style.display = 'none';
        }
    });
    
    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !btnNotif.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Marcar todas como lidas
    btnMarcarTodas.addEventListener('click', function() {
        fetch('../api/notificacoes.php?action=marcar_todas_lidas')
            .then(r => r.json())
            .then(() => {
                carregarNotificacoes();
            });
    });
    
    // Atualizar a cada 2 segundos (tempo real)
    setInterval(() => {
        fetch('../api/notificacoes.php?action=listar')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    // Atualizar badge sempre visível com contagem
                    badge.textContent = d.nao_lidas > 99 ? '99+' : d.nao_lidas;
                    badge.style.display = 'flex';
                    badge.style.background = d.nao_lidas > 0 ? '#EF4444' : '#9CA3AF';
                    
                    // Atualizar lista também se o dropdown estiver aberto
                    if (dropdown.style.display === 'block') {
                        atualizarLista(d);
                    }
                }
            })
            .catch(() => {});
    }, 2000);
    
    // Atualizar lista (sem refazer fetch)
    function atualizarLista(d) {
        if (d.notificacoes.length === 0) {
            lista.innerHTML = '<div style="padding:40px 20px;text-align:center;color:#6B7280;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="2" style="margin:0 auto 8px;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><p>Sem notificações</p></div>';
        } else {
            lista.innerHTML = d.notificacoes.map(n => `
                <div data-id="${n.id}" data-link="${n.link || ''}" style="padding:12px 16px;border-bottom:1px solid #F3F4F6;cursor:pointer;background:${n.lida == 0 ? '#F0F9FF' : 'white'};" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='${n.lida == 0 ? '#F0F9FF' : 'white'}'" >
                    <div style="display:flex;gap:10px;align-items:flex-start;">
                        <div style="width:8px;height:8px;border-radius:50%;background:${n.lida == 0 ? '#4F46E5' : 'transparent'};margin-top:6px;flex-shrink:0;"></div>
                        <div style="flex:1;min-width:0;">
                            <strong style="color:#111827;font-size:14px;">${n.titulo}</strong>
                            <div style="color:#4B5563;font-size:13px;margin-top:2px;">${n.mensagem || ''}</div>
                            <small style="color:#9CA3AF;font-size:11px;">há ${tempoRelativo(n.minutos)}</small>
                        </div>
                    </div>
                </div>
            `).join('');
            lista.querySelectorAll('[data-id]').forEach(el => {
                el.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const link = this.getAttribute('data-link');
                    fetch('../api/notificacoes.php?action=marcar_lida&id=' + id);
                    if (link) {
                        window.location.href = link;
                    } else {
                        this.style.background = 'white';
                        this.querySelector('div > div').style.background = 'transparent';
                    }
                });
            });
        }
    }
    
    // Carregar notificações imediatamente ao abrir a página
    carregarNotificacoes();
})();
</script>
