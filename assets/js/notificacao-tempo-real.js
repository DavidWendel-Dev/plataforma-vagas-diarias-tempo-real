/**
 * Sistema de Notificação em Tempo Real - App Prestador
 * Funciona em qualquer página do sistema
 */
(function() {
    // Detectar caminho da API baseado na URL atual
    const path = window.location.pathname;
    let apiPath = 'api/verificar_vagas.php';
    
    if (path.includes('/app/')) {
        apiPath = '../api/verificar_vagas.php';
    } else if (path.includes('/admin/')) {
        apiPath = '../api/verificar_vagas.php';
    }
    
    // Iniciar quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', iniciar);
    
    function iniciar() {
        console.log('🔔 Iniciando notificações em tempo real...');
        console.log('📍 API:', apiPath);
        
        // IDs já visíveis
        const cards = document.querySelectorAll('.diaria-card[data-id], article[data-id]');
        let idsDiarias = Array.from(cards).map(c => parseInt(c.dataset.id));
        let ultimaVerificacao = idsDiarias.length > 0 ? Math.max(...idsDiarias) : 0;
        
        console.log('📊 IDs:', idsDiarias);
        console.log('📊 Último:', ultimaVerificacao);
        
        // Verificar a cada 3 segundos
        setInterval(verificar, 3000);
        
        // Primeira verificação em 2s
        setTimeout(verificar, 2000);
        
        function verificar() {
            fetch(apiPath + '?ultimo_id=' + ultimaVerificacao)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.diarias && data.diarias.length > 0) {
                        data.diarias.forEach(vaga => {
                            if (!idsDiarias.includes(vaga.id)) {
                                console.log('🆕 Nova vaga:', vaga.titulo);
                                
                                idsDiarias.push(vaga.id);
                                if (vaga.id > ultimaVerificacao) {
                                    ultimaVerificacao = vaga.id;
                                }
                                
                                // Tocar som
                                const audio = document.getElementById('somNotificacao');
                                if (audio) {
                                    audio.currentTime = 0;
                                    audio.volume = 1.0;
                                    audio.play().then(() => {
                                        console.log('✅ Som!');
                                    }).catch(e => console.log('❌ Som bloqueado'));
                                }
                                
                                // Vibrar
                                if (navigator.vibrate) {
                                    navigator.vibrate([300, 100, 300]);
                                }
                                
                                // Toast
                                if (typeof toast === 'function') {
                                    toast('🔔 Nova vaga: ' + vaga.titulo, 'success');
                                } else if (typeof showToast === 'function') {
                                    showToast('🔔 Nova vaga: ' + vaga.titulo, 'success');
                                } else {
                                    alert('🔔 Nova vaga: ' + vaga.titulo);
                                }
                                
                                // Adicionar card se função existir
                                if (typeof adicionarCardVaga === 'function') {
                                    adicionarCardVaga(vaga);
                                }
                            }
                        });
                    }
                })
                .catch(err => console.error('Erro:', err));
        }
    }
})();
