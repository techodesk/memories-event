self.addEventListener('install', event => {
  event.waitUntil(
    caches.open('memories-v1').then(cache => cache.addAll([
      '/assets/theme.css',
      '/manifest.json'
    ]))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(res => res || fetch(event.request))
  );
});
