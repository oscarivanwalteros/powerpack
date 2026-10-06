// Service Worker para Power Pack - Suite Comercial PWA
const CACHE_NAME = 'powerpack-shell-v1';
const STATIC_ASSETS = [
    './manifest.json',
    './assets/logo-power-pack.png',
    './assets/logo-blanco.png',
    './assets/icon-192.png',
    './assets/icon-512.png',
    './assets/icon-maskable-192.png',
    './assets/icon-maskable-512.png',
    './assets/apple-touch-icon.png'
];

// Instalación: cachear assets esenciales
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activación: limpiar caches obsoletos
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Estrategia de Fetch: Network-First para páginas dinámicas y Cache-First para imágenes
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo procesar peticiones HTTP/HTTPS y GET
    if (request.method !== 'GET' || !url.protocol.startsWith('http')) {
        return;
    }

    // Para activos estáticos (imágenes en assets/): Cache first
    if (url.pathname.includes('/assets/')) {
        event.respondWith(
            caches.match(request).then(cached => {
                return cached || fetch(request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
                    }
                    return networkResponse;
                });
            })
        );
        return;
    }

    // Para páginas PHP y datos dinámicos: Siempre Network First (nunca datos desactualizados)
    event.respondWith(
        fetch(request)
            .then(networkResponse => {
                return networkResponse;
            })
            .catch(() => {
                return caches.match(request).then(cached => {
                    if (cached) return cached;
                    // Si está offline y no hay cache, devolver página informativa básica si es navegación
                    if (request.mode === 'navigate') {
                        return new Response(
                            `<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Power Pack - Sin conexión</title><style>body{font-family:sans-serif;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px}h1{color:#2c60a4}button{background:#2c60a4;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:bold;cursor:pointer;margin-top:16px}</style></head><body><div><h1>Power Pack</h1><p>Parece que no tienes conexión a internet en este momento.</p><button onclick="location.reload()">Reintentar</button></div></body></html>`,
                            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                        );
                    }
                });
            })
    );
});
