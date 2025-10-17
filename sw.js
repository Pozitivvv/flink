const CACHE_NAME = "wortly-cache-v1";
const OFFLINE_URL = "/offline.html";

const urlsToCache = [
  "/",
  "/dashboard.php",
  "/dictionary.php",
  "/add_word.php",
  "/assets/main-style.css",
  "/assets/dashboard.css",
  "/assets/dictionary.css",
  "/script/voice.js",
  "/image/icons/icon-192.png",
  "/image/icons/icon-512.png",
  OFFLINE_URL,
];

// 🧱 Установка (кэшируем нужные файлы)
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.all(
        urlsToCache.map((url) =>
          fetch(url)
            .then((response) => {
              if (!response.ok) throw new Error(`Не удалось загрузить: ${url}`);
              return cache.put(url, response);
            })
            .catch((err) => {
              console.warn(`⚠️ Ошибка при кэшировании ${url}:`, err);
            })
        )
      );
    })
  );
  self.skipWaiting();
});

// 🧹 Активация (удаляем старые кэши)
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((names) =>
        Promise.all(
          names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n))
        )
      )
  );
  self.clients.claim();
});

// 🌐 Обработка запросов
self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") return;

  const req = event.request;
  const url = new URL(req.url);

  // Кэшируем только свои файлы (из твоего домена)
  if (url.origin !== self.location.origin) return;

  event.respondWith(
    fetch(req)
      .then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
        return response;
      })
      .catch(() =>
        caches.match(req).then((res) => res || caches.match(OFFLINE_URL))
      )
  );
});
