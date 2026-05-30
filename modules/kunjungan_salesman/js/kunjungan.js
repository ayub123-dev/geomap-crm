/**
 * Kunjungan Dashboard Module
 * Handle dashboard initialization dan data loading
 */

class DashboardManager {
    constructor() {
        this.apiBaseUrl = '/api';
        this.user = auth.getUser();
    }

    /**
     * Initialize dashboard
     */
    init() {
        if (!auth.isLoggedIn()) {
            window.location.href = 'login.html';
            return;
        }

        this.setupEventListeners();
        this.loadDashboardData();
        this.updateUserInfo();
        
        // Refresh data setiap 5 menit
        setInterval(() => {
            this.loadDashboardData();
        }, 5 * 60 * 1000);
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        const profileBtn = document.getElementById('profileBtn');
        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                this.showProfileMenu();
            });
        }

        document.getElementById('btnQuickCheckin')?.addEventListener('click', () => {
            window.location.href = 'checkin.html';
        });

        document.getElementById('btnTargets')?.addEventListener('click', () => {
            const targetList = document.getElementById('targetList');
            if (targetList) {
                targetList.scrollIntoView({ behavior: 'smooth' });
            }
        });

        document.getElementById('btnJadwal')?.addEventListener('click', () => {
            window.location.href = 'master-jadwal.html';
        });

        document.getElementById('btnProspek')?.addEventListener('click', () => {
            window.location.href = 'prospek.html';
        });
    }

    /**
     * Update user info di navbar
     */
    updateUserInfo() {
        const salesmanName = document.getElementById('salesmanName');
        if (salesmanName && this.user) {
            salesmanName.textContent = this.user.name || 'Salesman';
        }
    }

    /**
     * Load dashboard data
     */
    async loadDashboardData() {
        try {
            const response = await auth.fetch(
                this.apiBaseUrl + '/salesman_targets.php'
            );
            const data = await response.json();

            if (!data.success) {
                console.error('Failed to load targets:', data.message);
                return;
            }

            const targets = data.targets || [];
            const summary = data.summary || [];

            // Load kunjungan hari ini
            await this.loadTodayKunjungan(targets, summary);

        } catch (error) {
            console.error('Error loading dashboard:', error);
            showAlert('Gagal memuat data dashboard', 'danger');
        }
    }

    /**
     * Load kunjungan hari ini
     */
    async loadTodayKunjungan(targets, summary) {
        try {
            const response = await auth.fetch(
                this.apiBaseUrl + '/visit_checkin.php'
            );
            const kunjunganData = await response.json();

            if (!kunjunganData.success) {
                return;
            }

            const kunjungan = kunjunganData.data || [];

            // Calculate statistics
            const checkin = kunjungan.filter(k => k.status === 'checkin').length;
            const checkout = kunjungan.filter(k => k.status === 'checkout').length;
            const total = targets.length;
            const progress = total > 0 ? Math.round(((checkin + checkout) / total) * 100) : 0;

            // Update stats
            document.getElementById('statTarget').textContent = total;
            document.getElementById('statDone').textContent = checkout;
            document.getElementById('statProgress').textContent = progress + '%';
            document.getElementById('statRemaining').textContent = Math.max(0, total - checkin - checkout);

            // Render target list
            this.renderTargetList(targets, kunjungan);

            // Render map
            if (targets.length > 0) {
                this.renderMiniMap(targets, kunjungan);
            }

        } catch (error) {
            console.error('Error loading kunjungan:', error);
        }
    }

    /**
     * Render target list
     */
    renderTargetList(targets, kunjungan) {
        const container = document.getElementById('targetList');

        if (!container) return;

        if (targets.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted p-3">
                    <i class="bi bi-check-circle"></i>
                    <p>Tidak ada target hari ini</p>
                </div>
            `;
            return;
        }

        // Create map untuk quick lookup
        const kunjunganMap = {};
        kunjungan.forEach(k => {
            kunjunganMap[k.customer_id] = k;
        });

        const html = targets.map((target, index) => {
            const visit = kunjunganMap[target.customer_id];
            const status = visit?.status || 'unchecked';

            const statusClass = {
                'checkin': 'status-checkin',
                'checkout': 'status-checkout',
                'unchecked': 'status-unchecked'
            }[status] || 'status-unchecked';

            const statusText = {
                'checkin': 'Check-In',
                'checkout': 'Selesai',
                'unchecked': 'Belum Dikunjungi'
            }[status] || 'Belum Dikunjungi';

            const initials = target.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);

            return `
                <div class="target-item" onclick="window.location.href='detail.html?id=${target.customer_id}'">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #0066cc; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 600;">
                        ${initials}
                    </div>
                    <div class="target-info">
                        <div class="target-name">${target.name}</div>
                        <div class="target-address">
                            <i class="bi bi-geo-alt" style="margin-right: 4px;"></i>${target.address || '--'}
                        </div>
                    </div>
                    <span class="target-status ${statusClass}">${statusText}</span>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    /**
     * Render mini map
     */
    renderMiniMap(targets, kunjungan) {
        const container = document.getElementById('miniMap');
        if (!container) return;

        // Clear previous map
        if (window.miniMapInstance) {
            window.miniMapInstance.remove();
        }

        try {
            // Initialize map
            const center = targets.length > 0 
                ? [targets[0].latitude, targets[0].longitude]
                : [-6.2, 106.8];

            const map = L.map('miniMap', {
                center: center,
                zoom: 13,
                zoomControl: false,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            // Add salesman location if available
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const { latitude, longitude } = pos.coords;

                    L.circleMarker([latitude, longitude], {
                        radius: 8,
                        fillColor: '#0066cc',
                        color: 'white',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(map)
                    .bindPopup('📍 Lokasi Anda');

                    // Add route to nearest target
                    if (targets.length > 0) {
                        const polyline = L.polyline(
                            [[latitude, longitude], [targets[0].latitude, targets[0].longitude]],
                            { color: '#0066cc', opacity: 0.5 }
                        ).addTo(map);

                        // Auto-fit bounds
                        const group = new L.featureGroup([
                            L.marker([latitude, longitude]),
                            ...targets.map(t => L.marker([t.latitude, t.longitude]))
                        ]);
                        map.fitBounds(group.getBounds().pad(0.1));
                    }
                });
            }

            // Add target markers
            const kunjunganMap = {};
            kunjungan.forEach(k => {
                kunjunganMap[k.customer_id] = k;
            });

            targets.forEach((target, index) => {
                const visit = kunjunganMap[target.customer_id];
                const status = visit?.status || 'unchecked';

                const icon = L.icon({
                    iconUrl: this.getMarkerIcon(status),
                    iconSize: [32, 40],
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    shadowSize: [41, 41],
                    shadowAnchor: [12, 41],
                    popupAnchor: [1, -34]
                });

                L.marker([target.latitude, target.longitude], { icon, title: target.name })
                    .addTo(map)
                    .bindPopup(`
                        <strong>${target.name}</strong><br>
                        ${target.address}<br>
                        <small style="color: #666;">${statusText}</small>
                    `);
            });

            window.miniMapInstance = map;

        } catch (error) {
            console.error('Error rendering map:', error);
        }
    }

    /**
     * Get marker icon based on status
     */
    getMarkerIcon(status) {
        // Using emoji as marker (simple approach)
        // In production, use actual SVG/PNG icons
        const statusEmoji = {
            'checkout': '✓',
            'checkin': '📍',
            'unchecked': '○'
        }[status] || '○';

        return `data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 32"><circle cx="12" cy="12" r="10" fill="%23${status === 'checkout' ? '28a745' : status === 'checkin' ? '0066cc' : 'ddd'}"/><text x="12" y="16" text-anchor="middle" font-size="16" fill="white">${statusEmoji}</text></svg>`;
    }

    /**
     * Show profile menu
     */
    showProfileMenu() {
        if (!this.user) return;

        const choice = confirm(
            `📊 GeoMap CRM\n\n` +
            `Nama: ${this.user.name}\n` +
            `Email: ${this.user.email}\n` +
            `Role: ${this.user.role}\n\n` +
            `Tekan OK untuk logout.`
        );

        if (choice) {
            auth.logout();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (!auth.isLoggedIn()) {
        window.location.href = 'login.html';
        return;
    }

    const dashboard = new DashboardManager();
    dashboard.init();
});
