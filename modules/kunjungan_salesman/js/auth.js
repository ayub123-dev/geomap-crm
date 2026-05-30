/**
 * Authentication Module
 * Handle JWT token, login/logout, auth checks
 */

class Auth {
    constructor() {
        this.tokenKey = 'token';
        this.userKey = 'user';
        // Determine API base relative to project root so module works under subpath (e.g. /geomap-crm)
        // If URL contains '/modules/', use the part before it as the app base.
        const pathBeforeModules = window.location.pathname.split('/modules/')[0];
        this.apiBaseUrl = (pathBeforeModules && pathBeforeModules !== '/' ? pathBeforeModules : '') + '/api';
    }

    /**
     * Login dengan email dan password
     */
    async login(email, password) {
        try {
            const response = await fetch(this.apiBaseUrl + '/access_setup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    email: email,
                    password: password
                })
            });

            const data = await response.json();

            if (data.success && data.token) {
                // Simpan token
                localStorage.setItem(this.tokenKey, data.token);
                // Simpan user hanya jika tersedia valid object, hindari menyimpan 'undefined'
                if (data.user && typeof data.user === 'object') {
                    localStorage.setItem(this.userKey, JSON.stringify(data.user));
                } else {
                    localStorage.removeItem(this.userKey);
                }
                
                // Register offline sync jika tersedia
                if ('serviceWorker' in navigator && 'SyncManager' in window) {
                    try {
                        const registration = await navigator.serviceWorker.ready;
                        await registration.sync.register('sync-kunjungan');
                    } catch (e) {
                        console.log('Background sync not available');
                    }
                }

                return {
                    success: true,
                    user: data.user || null
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Login gagal'
                };
            }
        } catch (error) {
            console.error('Login error:', error);
            return {
                success: false,
                message: 'Terjadi kesalahan: ' + error.message
            };
        }
    }

    /**
     * Logout
     */
    logout() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
        window.location.href = 'login.html';
    }

    /**
     * Get token
     */
    getToken() {
        return localStorage.getItem(this.tokenKey);
    }

    /**
     * Get user data
     */
    getUser() {
        const userJson = localStorage.getItem(this.userKey);
        if (!userJson) return null;
        try {
            return JSON.parse(userJson);
        } catch (e) {
            console.warn('Invalid user data in localStorage, removing corrupted value.');
            localStorage.removeItem(this.userKey);
            return null;
        }
    }

    /**
     * Check apakah user sudah login
     */
    isLoggedIn() {
        return !!this.getToken();
    }

    /**
     * Check apakah user memiliki role tertentu
     */
    hasRole(role) {
        const user = this.getUser();
        return user && user.role === role;
    }

    /**
     * Fetch dengan automatic token handling
     */
    async fetch(url, options = {}) {
        const headers = options.headers || {};
        const token = this.getToken();

        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        return fetch(url, {
            ...options,
            headers
        });
    }
}

// Global instance
const auth = new Auth();

// Auto-redirect to login jika tidak ada token
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const publicPages = ['login.html', 'index.html', ''];

    if (!publicPages.includes(currentPage)) {
        if (!auth.isLoggedIn()) {
            window.location.href = 'login.html';
        }
    }
});

/**
 * Setup page login form
 */
function setupLoginForm() {
    const form = document.getElementById('loginForm');
    const alertContainer = document.getElementById('alertContainer');

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Show loading
        const spinner = document.getElementById('loginSpinner');
        const submitBtn = form.querySelector('button[type="submit"]');
        spinner.style.display = 'inline-block';
        submitBtn.disabled = true;

        // Attempt login
        const result = await auth.login(email, password);

        spinner.style.display = 'none';
        submitBtn.disabled = false;

        if (result.success) {
            // Show success message
            alertContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> Login berhasil! Redirecting...
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            // Redirect to dashboard
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1500);
        } else {
            // Show error message
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> ${result.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    });
}

// Setup login form when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupLoginForm);
} else {
    setupLoginForm();
}

/**
 * Show user profile menu
 */
function showProfileMenu() {
    const user = auth.getUser();
    if (!user) return;

    const choice = confirm(
        `Nama: ${user.name}\nRole: ${user.role}\n\nTekan OK untuk logout.`
    );

    if (choice) {
        auth.logout();
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    let container = document.getElementById('alertContainer');
    if (!container) {
        // Create container if doesn't exist
        container = document.createElement('div');
        container.id = 'alertContainer';
        const nav = document.querySelector('nav');
        if (nav) {
            nav.insertAdjacentElement('afterend', container);
        }
    }

    container.innerHTML = alertHtml;

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}
