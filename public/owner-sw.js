// 大圣智慧物业 - 业主端 Service Worker
const CACHE_NAME = 'dasheng-owner-v1';

const PRECACHE_URLS = [
  '/owner.html',
  '/owner-manifest.json'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(PRECACHE_URLS).catch(err => {
        console.warn('[SW Owner] precache partial:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // API 请求：仅网络
  if (url.pathname.startsWith('/api/')) return;

  // 静态资源 & 页面：网络优先 + 缓存回退
  event.respondWith(
    fetch(event.request).then(response => {
      const clone = response.clone();
      if (response.status === 200) {
        caches.open(CACHE_NAME).then(cache => {
          cache.put(event.request, clone);
        });
      }
      return response;
    }).catch(() => caches.match(event.request))
  );
});
