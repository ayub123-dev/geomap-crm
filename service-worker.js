// Service Worker untuk GeoMap CRM Kunjungan Salesman PWA
const CACHE_NAME = 'geomap-kunjungan-v1';
const RUNTIME_CACHE = 'geomap-kunjungan-runtime-v1';
const API_CACHE = 'geomap-kunjungan-api-v1';

const urlsToCache = [
  '/',
  '/modules/kunjungan_salesman/dashboard.html',
  '/modules/kunjungan_salesman/checkin.html',
  '/modules/kunjungan_salesman/detail.html',
  '/modules/kunjungan_salesman/master-jadwal.html',
  '/modules/kunjungan_salesman/prospek.html',
  '/modules/kunjungan_salesman/login.html',
  
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/js/kunjungan.js',
  '/assets/js/checkin.js',
  '/assets/js/master-jadwal.js',
  
  // Leaflet
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  
  // Bootstrap
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Install event - cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching app shell');
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.log('Service Worker: Cache install failed', error);
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE && cacheName !== API_CACHE) {
            console.log('Service Worker: Deleting old cache', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle API requests
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstApi(request));
    return;
  }

  // Handle image requests
  if (request.destination === 'image') {
    event.respondWith(cacheFirstImage(request));
    return;
  }

  // Handle other requests (HTML, CSS, JS)
  event.respondWith(cacheFirstWithNetwork(request));
});

/**
 * Network first strategy untuk API
 * Coba network dulu, jika offline gunakan cache
 */
async function networkFirstApi(request) {
  try {
    const response = await fetch(request);
    
    // Cache successful API responses
    if (response.ok) {
      const cache = await caches.open(API_CACHE);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.log('Service Worker: Network request failed, trying cache', request.url);
    
    // Try cache
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }
    
    // Return offline response
    return new Response(JSON.stringify({
      success: false,
      message: 'Offline: Data not available',
      offline: true
    }), {
      status: 503,
      statusText: 'Service Unavailable',
      headers: new Headers({ 'Content-Type': 'application/json' })
    });
  }
}

/**
 * Cache first strategy dengan network fallback
 */
async function cacheFirstWithNetwork(request) {
  const cached = await caches.match(request);
  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);
    
    if (response.ok) {
      const cache = await caches.open(RUNTIME_CACHE);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.log('Service Worker: Failed to fetch', request.url);
    
    // Return offline page jika tersedia
    if (request.destination === 'document') {
      return caches.match('/offline.html') || 
             new Response('Offline - Please check your connection', { status: 503 });
    }
    
    return new Response('Service unavailable', { status: 503 });
  }
}

/**
 * Cache first strategy untuk images
 */
async function cacheFirstImage(request) {
  const cached = await caches.match(request);
  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);
    
    if (response.ok) {
      const cache = await caches.open(RUNTIME_CACHE);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    // Return placeholder image
    return new Response(
      '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">' +
      '<rect fill="#ddd" width="200" height="200"/>' +
      '<text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-size="16">Image not available</text>' +
      '</svg>',
      { headers: { 'Content-Type': 'image/svg+xml' } }
    );
  }
}

// Handle background sync untuk offline checkin
self.addEventListener('sync', event => {
  if (event.tag === 'sync-kunjungan') {
    event.waitUntil(syncKunjungan());
  }
});

async function syncKunjungan() {
  try {
    const db = await openIndexedDB();
    const pendingRequests = await getPendingRequests(db);
    
    for (const request of pendingRequests) {
      try {
        const response = await fetch(request.url, {
          method: request.method,
          headers: request.headers,
          body: request.body ? JSON.parse(request.body) : undefined
        });
        
        if (response.ok) {
          await removePendingRequest(db, request.id);
        }
      } catch (error) {
        console.log('Sync failed for request:', request.id);
        // Will retry on next sync
      }
    }
  } catch (error) {
    console.log('Sync error:', error);
    throw error;
  }
}

// IndexedDB helper untuk offline queue
function openIndexedDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('geomapCRM', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pendingRequests')) {
        db.createObjectStore('pendingRequests', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

function getPendingRequests(db) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pendingRequests'], 'readonly');
    const store = transaction.objectStore('pendingRequests');
    const request = store.getAll();
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}

function removePendingRequest(db, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pendingRequests'], 'readwrite');
    const store = transaction.objectStore('pendingRequests');
    const request = store.delete(id);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve();
  });
}

// Message handler untuk communicating dengan clients
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
