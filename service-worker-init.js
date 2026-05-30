/**
 * Service Worker Initialization Script
 * Register dan manage service worker lifecycle
 */

if ('serviceWorker' in navigator) {
    // Register service worker
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        }).then(registration => {
            console.log('Service Worker registered successfully:', registration);

            // Check for updates periodically
            setInterval(() => {
                registration.update();
            }, 1000 * 60 * 60); // Check every hour

            // Listen for controller change (new SW activated)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('Service Worker controller changed');
                // Optionally show update notification
                showUpdateNotification();
            });

        }).catch(error => {
            console.error('Service Worker registration failed:', error);
        });
    });

    // Listen for messages from service worker
    navigator.serviceWorker.addEventListener('message', event => {
        const { type, data } = event.data;

        switch (type) {
            case 'SKIP_WAITING':
                console.log('Updating to new version...');
                break;
            case 'SYNC_COMPLETE':
                console.log('Background sync completed:', data);
                showSyncNotification(data);
                break;
            case 'OFFLINE':
                console.log('App is offline');
                showOfflineNotification();
                break;
            case 'ONLINE':
                console.log('App is online');
                showOnlineNotification();
                break;
        }
    });

    // Detect online/offline status
    window.addEventListener('online', () => {
        console.log('Connection restored');
        showOnlineNotification();
        
        // Trigger background sync
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'SYNC_NOW'
            });
        }
    });

    window.addEventListener('offline', () => {
        console.log('Connection lost');
        showOfflineNotification();
    });
}

/**
 * Show update available notification
 */
function showUpdateNotification() {
    const notification = document.createElement('div');
    notification.className = 'update-notification';
    notification.innerHTML = `
        <div style="
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #0066cc;
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            max-width: 300px;
            font-size: 14px;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                <div>
                    <strong>Update Tersedia</strong>
                    <p style="margin: 4px 0 0 0; opacity: 0.9;">Versi baru siap digunakan</p>
                </div>
                <button onclick="location.reload()" style="
                    background: white;
                    color: #0066cc;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 12px;
                ">Perbarui</button>
            </div>
        </div>
    `;
    document.body.appendChild(notification);

    // Auto-remove after 10 seconds
    setTimeout(() => {
        notification.remove();
    }, 10000);
}

/**
 * Show offline notification
 */
function showOfflineNotification() {
    const notification = document.createElement('div');
    notification.className = 'offline-notification';
    notification.innerHTML = `
        <div style="
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            font-size: 13px;
            text-align: center;
        ">
            ⚠️ Anda sedang offline. Data akan di-sync saat online.
        </div>
    `;
    document.body.appendChild(notification);

    // Keep notification visible until online
    const removeNotification = () => {
        notification.remove();
        window.removeEventListener('online', removeNotification);
    };

    window.addEventListener('online', removeNotification);
}

/**
 * Show online notification
 */
function showOnlineNotification() {
    const notification = document.createElement('div');
    notification.className = 'online-notification';
    notification.innerHTML = `
        <div style="
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            font-size: 13px;
            text-align: center;
        ">
            ✓ Koneksi dipulihkan. Melakukan sinkronisasi...
        </div>
    `;
    document.body.appendChild(notification);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Show sync notification
 */
function showSyncNotification(data) {
    const notification = document.createElement('div');
    notification.innerHTML = `
        <div style="
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            font-size: 13px;
            text-align: center;
        ">
            ✓ Data berhasil disinkronisasi
        </div>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Check PWA installation status
 */
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event for later use
    deferredPrompt = e;
    
    // Show install button if not already installed
    const installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.style.display = 'block';
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
            }
        });
    }
});

window.addEventListener('appinstalled', () => {
    console.log('PWA installed successfully');
    deferredPrompt = null;
    
    // Hide install button
    const installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.style.display = 'none';
    }
});

/**
 * Periodic background sync (if supported)
 */
if ('PeriodicSyncManager' in window) {
    navigator.serviceWorker.ready.then(async (registration) => {
        try {
            // Register periodic sync every 24 hours
            await registration.periodicSync.register('sync-kunjungan', {
                minInterval: 24 * 60 * 60 * 1000 // 24 hours
            });
            console.log('Periodic sync registered');
        } catch (error) {
            console.log('Periodic sync registration failed:', error);
        }
    });
}

// Log app launch
console.log('Kunjungan Salesman PWA loaded');
console.log('Online status:', navigator.onLine);
console.log('User Agent:', navigator.userAgent);
