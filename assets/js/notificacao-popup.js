/**
 * Sistema de Notificação em Tempo Real - Outras Páginas
 * Mesma lógica do index.php: sincroniza com os IDs que já existem no servidor
 * e só notifica quando há ID NOVO.
 */
(function() {
    // Detectar caminho da API
    const path = window.location.pathname;
    let apiPath = 'api/verificar_vagas.php';
    let maxIdPath = 'api/max_id.php';
    
    if (path.includes('/app/')) {
        apiPath = '../api/verificar_vagas.php';
        maxIdPath = '../api/max_id.php';
    } else if (path.includes('/admin/')) {
        apiPath = '../api/verificar_vagas.php';
        maxIdPath = '../api/max_id.php';
    }
    
    // Ignorar se estiver na index do app (ela tem seu próprio sistema inline)
    if (path.includes('/app/index.php') || path.endsWith('/app/')) {
        return;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔔 Iniciando notificações...');
        
        // IGUAL AO INDEX: considerar que TODOS os IDs que existem AGORA já foram vistos
        // Buscar ID máximo atual e SÓ notificar sobre IDs que surgirem DEPOIS
        let ultimaVerificacao = 0;
        let sincronizado = false;
        
        // PRIMEIRO: sincronizar com o estado atual do servidor
        fetch(maxIdPath)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    ultimaVerificacao = data.max_id;
                    sincronizado = true;
                    console.log('📊 Sincronizado. ID máximo atual:', ultimaVerificacao);
                    console.log('📊 Só vou notificar vagas com ID >', ultimaVerificacao);
                }
            })
            .catch(err => console.error('Erro ao sincronizar:', err));
        
        // Verificar a cada 3 segundos
        setInterval(function() {
            if (!sincronizado) return; // Só verificar depois de sincronizado
            
            fetch(apiPath + '?ultimo_id=' + ultimaVerificacao)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.diarias && data.diarias.length > 0) {
                        data.diarias.forEach(function(vaga) {
                            if (vaga.id > ultimaVerificacao) {
                                console.log('🆕 Nova vaga:', vaga.titulo, '(ID:', vaga.id, ')');
                                
                                // IMPORTANTE: atualizar ANTES de tocar, para não tocar de novo
                                ultimaVerificacao = vaga.id;
                                
                                // Tocar som
                                const audio = document.getElementById('somNotificacao');
                                if (audio) {
                                    audio.currentTime = 0;
                                    audio.volume = 1.0;
                                    audio.play().catch(() => {});
                                }
                                
                                // Vibrar
                                if (navigator.vibrate) {
                                    navigator.vibrate([300, 100, 300]);
                                }
                                
                                // Mostrar popup
                                mostrarPopup(vaga);
                            }
                        });
                    }
                })
                .catch(err => console.error('Erro:', err));
        }, 3000);
    });
    
    function mostrarPopup(vaga) {
        const existente = document.getElementById('popupVaga');
        if (existente) existente.remove();
        
        const valor = parseFloat(vaga.valor).toFixed(2).replace('.', ',');
        const data = vaga.data_evento ? vaga.data_evento.split('-').reverse().join('/') : '';
        
        const popup = document.createElement('div');
        popup.id = 'popupVaga';
        popup.style.cssText = 'position:fixed;bottom:90px;left:10px;right:10px;background:linear-gradient(135deg,#6366F1,#4F46E5);border-radius:16px;padding:16px;z-index:9999;box-shadow:0 10px 40px rgba(0,0,0,0.3);display:flex;gap:12px;align-items:center;';
        
        popup.innerHTML = `
            <div style="width:50px;height:50px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">🔔</div>
            <div style="flex:1;color:white;">
                <div style="font-size:13px;opacity:0.8;">🆕 NOVA VAGA</div>
                <div style="font-size:16px;font-weight:700;margin:4px 0;">${vaga.titulo}</div>
                <div style="font-size:12px;opacity:0.9;">${vaga.funcao} • R$ ${valor} • ${data}</div>
            </div>
            <a href="../diaria.php?id=${vaga.id}" style="background:white;color:#6366F1;padding:10px 20px;border-radius:10px;font-weight:600;text-decoration:none;font-size:14px;white-space:nowrap;">Ver</a>
        `;
        
        document.body.appendChild(popup);
        
        setTimeout(() => {
            popup.style.opacity = '0';
            popup.style.transform = 'translateY(100px)';
            popup.style.transition = 'all 0.3s ease';
            setTimeout(() => popup.remove(), 300);
        }, 8000);
        
        popup.addEventListener('click', (e) => {
            if (e.target.tagName !== 'A') popup.remove();
        });
    }
})();
