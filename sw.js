/**
 * Service Worker - Diárias PWA
 * Implementa cache offline e estratégias de carregamento
 */

const CACHE_NAME = 'diarias-v6';
const STATIC_CACHE = 'diarias-static-v6';
const DYNAMIC_CACHE = 'diarias-dynamic-v6';

// Arquivos para cache estático (primeira visita) - caminhos relativos
const STATIC_FILES = [
    './',
    './index.php',
    './login.php',
    './app/index.php',
    './offline.html',
    './assets/css/modern.css',
    './assets/css/style.css',
    './assets/js/app.js',
    './assets/js/auth.js',
    './manifest.json',
    './assets/icons/icon-192.png',
    './assets/icons/icon-512.png',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
];

// Instalar Service Worker
self.addEventListener('install', event => {
    console.log('[Service Worker] Instalando...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[Service Worker] Cacheando arquivos estáticos');
                // Cache individual para não falhar se um arquivo falhar
                return Promise.allSettled(
                    STATIC_FILES.map(url => cache.add(url))
                );
            })
            .then(() => self.skipWaiting())
    );
});

// Ativar Service Worker
self.addEventListener('activate', event => {
    console.log('[Service Worker] Ativando...');
    
    event.waitUntil(
        caches.keys()
            .then(keys => {
                return Promise.all(
                    keys
                        .filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                        .map(key => {
                            console.log('[Service Worker] Removendo cache antigo:', key);
                            return caches.delete(key);
                        })
                );
            })
            .then(() => self.clients.claim())
            .then(() => {
                // Notificar clientes para recarregar
                return self.clients.matchAll({ type: 'window' });
            })
            .then(clients => {
                clients.forEach(client => {
                    client.postMessage({ type: 'SW_UPDATED' });
                });
            })
    );
});

// Interceptar requisições
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignorar requisições não-GET
    if (request.method !== 'GET') {
        return;
    }
    
    // Ignorar requisições para API (sempre online)
    if (url.pathname.includes('/api/')) {
        event.respondWith(networkOnly(request));
        return;
    }
    
    // Ignorar Mapbox (muito grande para cache)
    if (url.hostname.includes('mapbox') || url.hostname.includes('tiles')) {
        event.respondWith(networkOnly(request));
        return;
    }
    
    // Estratégia para arquivos estáticos: Cache First
    if (isStaticFile(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }
    
    // Estratégia para páginas: Network First, fallback para cache
    event.respondWith(networkFirst(request));
});

// Verificar se é arquivo estático
function isStaticFile(url) {
    const extensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2'];
    return extensions.some(ext => url.pathname.endsWith(ext));
}

// Estratégia: Cache First
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        // Retornar página offline se disponível
        return caches.match('./offline.html');
    }
}

// Estratégia: Network First
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Retornar página offline
        return caches.match('./offline.html');
    }
}

// Estratégia: Network Only
async function networkOnly(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return new Response(JSON.stringify({ error: 'Sem conexão' }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Receber mensagens do cliente
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Background Sync (para ações offline)
self.addEventListener('sync', event => {
    console.log('[Service Worker] Background Sync:', event.tag);
    
    if (event.tag === 'sync-candidaturas') {
        event.waitUntil(syncCandidaturas());
    }
});

// Push Notifications
self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    
    const options = {
        body: data.body || 'Nova atualização disponível',
        icon: './assets/icons/icon-192.png',
        badge: './assets/icons/icon-72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || './'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Conect Eventos', options)
    );
});

// Clique na notificação
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});

// Sincronizar candidaturas offline
async function syncCandidaturas() {
    // Buscar candidaturas pendentes do IndexedDB
    // Enviar para o servidor
    console.log('[Service Worker] Sincronizando candidaturas...');
}
