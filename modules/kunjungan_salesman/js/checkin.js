/**
 * Check-in Module
 * Handle GPS location, checkin, photo upload
 */

class CheckinManager {
    constructor() {
        this.currentPosition = null;
        this.maxDistanceMeters = 200;
        this.apiBaseUrl = '/api';
        this.watchId = null;
    }

    /**
     * Inisialisasi check-in page
     */
    init() {
        this.setupEventListeners();
        this.loadTargetCustomers();
        this.startGPSTracking();
        this.setupPhotoUpload();
        this.setupRating();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        document.getElementById('checkinForm').addEventListener('submit', (e) => {
            this.handleSubmit(e);
        });

        document.getElementById('btnRefreshGPS').addEventListener('click', () => {
            this.refreshLocation();
        });

        document.getElementById('customerSelect').addEventListener('change', (e) => {
            this.onCustomerChange(e);
        });
    }

    /**
     * Load target customers dari API
     */
    async loadTargetCustomers() {
        try {
            const response = await auth.fetch(
                this.apiBaseUrl + '/salesman_targets.php'
            );
            const data = await response.json();

            if (data.success && data.targets) {
                this.populateCustomerSelect(data.targets);
            } else {
                showAlert('Gagal memuat data pelanggan target', 'warning');
            }
        } catch (error) {
            console.error('Error loading customers:', error);
        }
    }

    /**
     * Populate customer select dropdown
     */
    populateCustomerSelect(targets) {
        const select = document.getElementById('customerSelect');
        const html = targets.map(target => `
            <option value="${target.customer_id}" data-lat="${target.latitude}" data-lng="${target.longitude}">
                ${target.name}
            </option>
        `).join('');

        select.innerHTML = '<option value="">-- Pilih Pelanggan --</option>' + html;
    }

    /**
     * Mulai tracking GPS
     */
    startGPSTracking() {
        if (!navigator.geolocation) {
            showAlert('Geolocation tidak didukung di browser ini', 'danger');
            return;
        }

        // First time get position
        navigator.geolocation.getCurrentPosition(
            (position) => this.updatePosition(position),
            (error) => this.handleGPSError(error),
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );

        // Watch position continuously
        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.updatePosition(position),
            (error) => console.log('GPS watch error:', error),
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    }

    /**
     * Update position display
     */
    updatePosition(position) {
        const { latitude, longitude, accuracy } = position.coords;

        this.currentPosition = {
            latitude,
            longitude,
            accuracy
        };

        // Update UI
        document.getElementById('myLatitude').value = latitude.toFixed(6);
        document.getElementById('myLongitude').value = longitude.toFixed(6);

        const accuracyPercent = Math.max(0, Math.min(100, 100 - (accuracy / 50)));
        document.getElementById('gpsAccuracy').style.width = accuracyPercent + '%';
        document.getElementById('gpsAccuracy').textContent = Math.round(accuracy) + 'm';

        // Update GPS status
        const statusDiv = document.getElementById('gpsStatus');
        statusDiv.className = 'gps-status success';
        statusDiv.innerHTML = `
            <i class="bi bi-check-circle"></i>
            <span>GPS Terhubung (Akurasi: ${Math.round(accuracy)}m)</span>
        `;

        // Validate distance jika customer sudah dipilih
        const customerSelect = document.getElementById('customerSelect');
        if (customerSelect.value) {
            this.validateDistance();
        }
    }

    /**
     * Handle GPS error
     */
    handleGPSError(error) {
        const statusDiv = document.getElementById('gpsStatus');
        statusDiv.className = 'gps-status error';

        let message = 'GPS Error';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Izin GPS ditolak. Aktifkan lokasi di pengaturan.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Lokasi tidak tersedia. Coba di tempat terbuka.';
                break;
            case error.TIMEOUT:
                message = 'Waktu GPS timeout. Coba lagi.';
                break;
        }

        statusDiv.innerHTML = `
            <i class="bi bi-exclamation-circle"></i>
            <span>${message}</span>
        `;

        console.error('GPS Error:', error);
    }

    /**
     * Refresh location
     */
    refreshLocation() {
        const btn = document.getElementById('btnRefreshGPS');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Mencari...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.updatePosition(position);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Refresh Lokasi';
            },
            (error) => {
                this.handleGPSError(error);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Refresh Lokasi';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    /**
     * Validate distance saat customer dipilih
     */
    onCustomerChange(event) {
        if (event.target.value) {
            this.validateDistance();
        } else {
            document.getElementById('distanceAlert').innerHTML = '';
        }
    }

    /**
     * Validate jarak GPS vs customer
     */
    validateDistance() {
        if (!this.currentPosition) {
            showAlert('Lokasi Anda belum terdeteksi', 'warning');
            return;
        }

        const customerSelect = document.getElementById('customerSelect');
        const selected = customerSelect.options[customerSelect.selectedIndex];

        const customerLat = parseFloat(selected.dataset.lat);
        const customerLng = parseFloat(selected.dataset.lng);

        const distance = this.calculateDistance(
            this.currentPosition.latitude,
            this.currentPosition.longitude,
            customerLat,
            customerLng
        );

        const alertDiv = document.getElementById('distanceAlert');

        if (distance <= this.maxDistanceMeters) {
            alertDiv.className = 'distance-alert distance-valid';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i>
                <strong>Jarak Valid!</strong> Anda ${distance}m dari lokasi pelanggan.
            `;
        } else {
            alertDiv.className = 'distance-alert distance-invalid';
            alertDiv.innerHTML = `
                <i class="bi bi-exclamation-circle"></i>
                <strong>Jarak Tidak Valid!</strong> Anda ${distance}m dari pelanggan (max 200m).
                Pastikan Anda berada di lokasi pelanggan sebelum check-in.
            `;
        }
    }

    /**
     * Calculate distance antara 2 koordinat (Haversine formula)
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Radius bumi dalam meter

        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return Math.round(R * c);
    }

    /**
     * Setup photo upload
     */
    setupPhotoUpload() {
        const photoUpload = document.getElementById('photoUpload');
        const photoInput = document.getElementById('photoInput');

        photoUpload.addEventListener('click', () => {
            photoInput.click();
        });

        photoInput.addEventListener('change', (e) => {
            this.handlePhotoSelect(e);
        });
    }

    /**
     * Handle photo selection
     */
    handlePhotoSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.match('image.*')) {
            showAlert('Pilih file gambar yang valid', 'danger');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('Ukuran file terlalu besar (max 5MB)', 'danger');
            return;
        }

        // Preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = `
                <div class="photo-preview">
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-photo" onclick="document.getElementById('photoInput').value = ''; document.getElementById('photoPreview').innerHTML = '';">
                        ✕
                    </button>
                </div>
            `;

            // Update upload UI
            document.getElementById('photoPrompt').style.display = 'none';
            document.getElementById('photoUpload').classList.add('active');
        };

        reader.readAsDataURL(file);
    }

    /**
     * Setup rating stars
     */
    setupRating() {
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.rating;
                ratingInput.value = rating;

                stars.forEach(s => {
                    s.classList.remove('active');
                    if (s.dataset.rating <= rating) {
                        s.classList.add('active');
                    }
                });
            });
        });
    }

    /**
     * Handle form submit
     */
    async handleSubmit(e) {
        e.preventDefault();

        if (!this.currentPosition) {
            showAlert('Lokasi Anda belum terdeteksi', 'danger');
            return;
        }

        const customerSelect = document.getElementById('customerSelect');
        const customerId = customerSelect.value;

        if (!customerId) {
            showAlert('Pilih pelanggan terlebih dahulu', 'danger');
            return;
        }

        // Validate distance
        const selected = customerSelect.options[customerSelect.selectedIndex];
        const customerLat = parseFloat(selected.dataset.lat);
        const customerLng = parseFloat(selected.dataset.lng);

        const distance = this.calculateDistance(
            this.currentPosition.latitude,
            this.currentPosition.longitude,
            customerLat,
            customerLng
        );

        if (distance > this.maxDistanceMeters) {
            showAlert(`Jarak tidak valid (${distance}m > ${this.maxDistanceMeters}m)`, 'danger');
            return;
        }

        const submitBtn = document.getElementById('btnSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

        try {
            // Step 1: Checkin
            const checkinResponse = await auth.fetch(
                this.apiBaseUrl + '/visit_checkin.php',
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: customerId,
                        gps_latitude: this.currentPosition.latitude,
                        gps_longitude: this.currentPosition.longitude,
                        customer_latitude: customerLat,
                        customer_longitude: customerLng
                    })
                }
            );

            const checkinData = await checkinResponse.json();

            if (!checkinData.success) {
                showAlert(checkinData.message || 'Checkin gagal', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Simpan Check-In';
                return;
            }

            const kunjunganId = checkinData.kunjungan_id;

            // Step 2: Update kunjungan data
            const kondisiToko = document.getElementById('storeCondition').value;
            const potensi = document.getElementById('potensi').value;
            const catatan = document.getElementById('notes').value;
            const rating = document.getElementById('rating').value;

            if (kondisiToko || potensi || catatan || rating) {
                await auth.fetch(
                    `${this.apiBaseUrl}/visit_checkin.php`,
                    {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id: kunjunganId,
                            kondisi_toko: kondisiToko,
                            potensi_penjualan: potensi,
                            catatan: catatan,
                            rating: rating
                        })
                    }
                );
            }

            // Step 3: Upload photo jika ada
            const photoInput = document.getElementById('photoInput');
            if (photoInput.files.length > 0) {
                const formData = new FormData();
                formData.append('kunjungan_id', kunjunganId);
                formData.append('photo', photoInput.files[0]);

                await auth.fetch(
                    this.apiBaseUrl + '/visit_upload_photo.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );
            }

            // Success
            showAlert('Check-in berhasil! Jarak: ' + distance + 'm', 'success');

            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 2000);

        } catch (error) {
            console.error('Submit error:', error);
            showAlert('Terjadi kesalahan: ' + error.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Simpan Check-In';
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (!auth.isLoggedIn()) {
        window.location.href = 'login.html';
        return;
    }

    const manager = new CheckinManager();
    manager.init();
});
