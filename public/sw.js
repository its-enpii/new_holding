const CACHE_NAME = 'holding-core-v1';
const CORE_ASSETS = [
    '/manifest.webmanifest',
    '/offline.html',
    '/favicon.ico',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(cacheNames.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(networkFirstNavigation(event.request));
        return;
    }

    event.respondWith(cacheFirstRequest(event.request));
});

async function networkFirstNavigation(request) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);

        return cachedResponse ?? caches.match('/offline.html');
    }
}

async function cacheFirstRequest(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const response = await fetch(request);

        if (response.ok && isCoreAsset(new URL(request.url))) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        return new Response('', { status: 504, statusText: 'Offline' });
    }
}

function isCoreAsset(url) {
    return url.origin === self.location.origin && CORE_ASSETS.includes(`${url.pathname}${url.search}`);
}
