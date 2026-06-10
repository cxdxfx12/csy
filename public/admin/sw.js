// 大圣物业管理平台 - 管理端 Service Worker
const CACHE_NAME = 'dasheng-admin-v1';

const PRECACHE_URLS = [
  '/admin/',
  '/admin/index.html',
  '/admin/manifest.json'
];

// Install: 预缓存核心资源
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(PRECACHE_URLS).catch(err => {
        console.warn('[SW Admin] precache partial:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Activate: 清理旧缓存
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: 网络优先，失败时回退缓存
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // API 请求：仅网络（不缓存）
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/admin/api/')) {
    return;
  }

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
