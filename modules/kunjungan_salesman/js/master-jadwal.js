/**
 * Master Jadwal Kunjungan Module
 * Handle schedule creation, editing, deletion
 */

class MasterJadwalManager {
    constructor() {
        // Use auth module's apiBaseUrl so module works under subpaths (e.g. /geomap-crm)
        this.apiBaseUrl = (typeof auth !== 'undefined' && auth.apiBaseUrl) ? auth.apiBaseUrl : '/api';
        this.currentDay = 'Senin';
        this.schedules = {};
        this.salesmans = [];
        this.customers = [];
        this.modal = null;

        // optional debug logs (guarded)
        if (typeof auth !== 'undefined') {
            console.log(auth.getUser && auth.getUser());
            console.log(auth.hasRole && auth.hasRole('supervisor'));
            console.log(auth.hasRole && auth.hasRole('admin'));
        }
    }

    /**
     * Initialize
     */
    init() {
        // Check auth - hanya admin dan supervisor
        if (!(auth.hasRole('admin') || auth.hasRole('supervisor'))) {
            showAlert('Akses ditolak. Hanya admin dan supervisor yang bisa mengakses halaman ini.', 'danger');
            setTimeout(() => window.history.back(), 2000);
            return;
        }

        const modalElement = document.getElementById('scheduleModal');

        if (!modalElement) {
            console.error('scheduleModal tidak ditemukan');
            showAlert('Modal jadwal tidak ditemukan', 'danger');
            return;
        }

        this.modal = new bootstrap.Modal(modalElement);
        this.setupEventListeners();
        this.loadData();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Day tabs
        document.querySelectorAll('.day-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchDay(e.target.dataset.day);
            });
        });

        // Add schedule button
        // Add schedule button intentionally removed from UI; do not log missing element
        const btnAdd = document.getElementById('btnAddSchedule');
        if (btnAdd) {
            btnAdd.addEventListener('click', () => {
                this.showAddScheduleModal();
            });
        }

        // Save schedule button
        const btnSave = document.getElementById('btnSaveSchedule');
        if (btnSave) {
            btnSave.addEventListener('click', () => {
                this.saveSchedule();
            });
        } else {
            console.error('btnSaveSchedule tidak ditemukan');
        }

        // Frequency radio buttons
        document.querySelectorAll('input[name="frequency"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const weekGroup = document.getElementById('weekGroup');
                weekGroup.style.display = e.target.value === '1' ? 'block' : 'none';
            });
        });
    }

    /**
     * Load data dari API
     */
    async loadData() {
        try {
            // Load salesmans
            const salesmansResponse = await auth.fetch(
                this.apiBaseUrl + '/salesman.php'
            );
            const salesmansData = await salesmansResponse.json();

            if (salesmansData.success) {
                this.salesmans = salesmansData.data || [];
                this.populateSalesmanSelect();
            }

            // Customers will be loaded per-salesman when a salesman is selected. Initial customer list remains empty until a salesman is chosen.
            this.customers = [];
            this.populateCustomerSelect();

            // When salesman selection changes, load customers for that salesman's database
            const formSalesmanEl = document.getElementById('formSalesman');
            if (formSalesmanEl) {
                formSalesmanEl.addEventListener('change', (e) => {
                    const sid = e.target.value;
                    if (sid) {
                        this.loadCustomersForSalesman(parseInt(sid, 10));
                    } else {
                        // reset to empty
                        this.customers = [];
                        this.populateCustomerSelect();
                    }
                });
            }

            // Load all schedules
            await this.loadSchedules();

        } catch (error) {
            console.error('Error loading data:', error);
            showAlert('Gagal memuat data', 'danger');
        }
    }

    /**
     * Load schedules
     */
    async loadSchedules() {
        try {
            const response = await auth.fetch(
                this.apiBaseUrl + '/jadwal_kunjungan.php?salesman_id=0'
            );
            const data = await response.json();

            if (data.success) {
                // Group by day
                const daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

                daysOfWeek.forEach(day => {
                    this.schedules[day] = data.data.filter(s => s.hari_dalam_minggu === day);
                });

                this.renderSchedules();
            }
        } catch (error) {
            console.error('Error loading schedules:', error);
        }
    }

    /**
     * Populate salesman select
     */
    populateSalesmanSelect() {
        const select = document.getElementById('formSalesman');
        const html = this.salesmans.map(s => {
            let label = s.nama || s.name || 'Salesman ' + s.id;
            // Add user name if available
            if (s.user_name) {
                label += ` (${s.user_name})`;
            }
            // Add phone if available
            if (s.no_hp) {
                label += ` - ${s.no_hp}`;
            }
            return `<option value="${s.id}">${label}</option>`;
        }).join('');

        select.innerHTML = '<option value="">-- Pilih Salesman --</option>' + html;
    }

    /**
     * Populate customer select
     */
    populateCustomerSelect() {
        const select = document.getElementById('formCustomer');
        const html = (this.customers || []).map(c => `
            <option value="${c.id}">${c.nama_toko || c.name || c.kode_customer || 'Customer ' + c.id}</option>
        `).join('');

        select.innerHTML = '<option value="">-- Pilih Pelanggan --</option>' + html;
    }

    /**
     * Load customers from the salesman's database (if defined) or default DB     */
    async loadCustomersForSalesman(salesmanId) {
        try {
            const salesman = this.salesmans.find(s => String(s.id) === String(salesmanId));
            let databaseAlias = '';
            if (salesman && salesman.profile_json) {
                try {
                    const decoded = typeof salesman.profile_json === 'string' ? JSON.parse(salesman.profile_json) : salesman.profile_json;
                    databaseAlias = decoded && decoded.database_alias ? decoded.database_alias : '';
                } catch (e) {
                    console.warn('Invalid profile_json for salesman', e);
                }
            }

            const url = this.apiBaseUrl + '/customers.php' + (databaseAlias ? '?database_alias=' + encodeURIComponent(databaseAlias) : '');
            const resp = await auth.fetch(url);
            if (!resp.ok) {
                // try plain json parse to show error
                let txt = await resp.text();
                try { txt = JSON.parse(txt); } catch (e) { /* keep raw text */ }
                throw new Error('Failed to load customers: ' + (resp.status + ' ' + resp.statusText));
            }

            const data = await resp.json();
            if (data.success) {
                this.customers = data.data || [];
                this.populateCustomerSelect();
            } else {
                console.warn('Customers API returned failure', data.message);
                this.customers = [];
                this.populateCustomerSelect();
            }
        } catch (err) {
            console.error('Error loading customers for salesman:', err);
            this.customers = [];
            this.populateCustomerSelect();
        }
    }

    /**
     * Switch day tab
     */
    switchDay(day) {
        this.currentDay = day;

        // Update tab active state
        document.querySelectorAll('.day-tab').forEach(tab => {
            tab.classList.remove('active');
            if (tab.dataset.day === day) {
                tab.classList.add('active');
            }
        });

        // Update title
        document.getElementById('dayTitle').textContent = `Jadwal ${day}`;

        // Render schedules
        this.renderSchedules();
    }

    /**
     * Render schedules untuk hari saat ini
     */
    renderSchedules() {
        const container = document.getElementById('scheduleList');
        const schedules = this.schedules[this.currentDay] || [];

        if (schedules.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>Belum ada jadwal untuk hari ${this.currentDay}</p>
                </div>
            `;
            return;
        }

        const html = schedules.map(schedule => `
            <div class="schedule-item">
                <div class="schedule-info">
                    <div class="customer-name">${schedule.customer_name}</div>
                    <div class="customer-salesman">
                        <i class="bi bi-person"></i> ${schedule.salesman_name || 'Unknown Salesman'}
                    </div>
                </div>
                <div class="schedule-frequency">
                    ${schedule.minggu_ke ? 
                        `<span class="freq-badge freq-specific">Minggu ${schedule.minggu_ke}</span>` :
                        `<span class="freq-badge freq-weekly">Setiap Minggu</span>`
                    }
                </div>
                <div class="schedule-actions">
                    <button class="btn-action btn-edit" onclick="jadwalManager.editSchedule(${schedule.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="jadwalManager.deleteSchedule(${schedule.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    /**
     * Show add schedule modal
     */
    showAddScheduleModal() {
        if (!this.modal) {
            console.error('Bootstrap modal belum terinisialisasi');
            showAlert('Modal belum tersedia', 'danger');
            return;
        }

        const modalTitle = document.getElementById('modalTitle');
        const scheduleForm = document.getElementById('scheduleForm');
        const formDay = document.getElementById('formDay');
        const weekGroup = document.getElementById('weekGroup');
        const freqEveryWeek = document.getElementById('freqEveryWeek');

        if (!modalTitle || !scheduleForm || !formDay) {
            console.error('Komponen modal tidak ditemukan');
            showAlert('Komponen form jadwal tidak lengkap', 'danger');
            return;
        }

        modalTitle.textContent = 'Tambah Jadwal';
        scheduleForm.reset();
        formDay.value = this.currentDay;

        if (weekGroup) {
            weekGroup.style.display = 'none';
        }

        if (freqEveryWeek) {
            freqEveryWeek.checked = true;
        }

        this.modal.show();
    }

    /**
     * Edit schedule
     */
    async editSchedule(scheduleId) {
        // Implementation untuk edit
        alert('Edit functionality coming soon');
    }

    /**
     * Delete schedule
     */
    async deleteSchedule(scheduleId) {
        if (!confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
            return;
        }

        try {
            const response = await auth.fetch(
                `${this.apiBaseUrl}/jadwal_kunjungan.php?id=${scheduleId}`,
                { method: 'DELETE' }
            );

            const data = await response.json();

            if (data.success) {
                showAlert('Jadwal berhasil dihapus', 'success');
                await this.loadSchedules();
            } else {
                showAlert(data.message || 'Gagal menghapus jadwal', 'danger');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showAlert('Terjadi kesalahan', 'danger');
        }
    }

    /**
     * Save schedule
     */
    async saveSchedule() {
        const salesman_id = document.getElementById('formSalesman').value;
        const customer_id = document.getElementById('formCustomer').value;
        const hari = document.getElementById('formDay').value;
        const frequency = document.querySelector('input[name="frequency"]:checked').value;

        if (!salesman_id || !customer_id || !hari) {
            showAlert('Silakan isi semua field yang diperlukan', 'warning');
            return;
        }

        try {
            const btn = document.getElementById('btnSaveSchedule');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';

            // Jika frequency specific, buat multiple schedules untuk setiap minggu yang dipilih
            let schedulesToCreate = [];

            if (frequency === '0') {
                // Setiap minggu
                schedulesToCreate.push({
                    salesman_id,
                    customer_id,
                    hari_dalam_minggu: hari,
                    minggu_ke: null,
                    is_active: 1
                });
            } else {
                // Minggu tertentu
                const weeks = [];
                for (let i = 1; i <= 4; i++) {
                    if (document.getElementById(`week${i}`)?.checked) {
                        weeks.push(i);
                    }
                }

                if (weeks.length === 0) {
                    showAlert('Pilih minimal satu minggu', 'warning');
                    btn.disabled = false;
                    btn.innerHTML = 'Simpan';
                    return;
                }

                weeks.forEach(week => {
                    schedulesToCreate.push({
                        salesman_id,
                        customer_id,
                        hari_dalam_minggu: hari,
                        minggu_ke: week,
                        is_active: 1
                    });
                });
            }

            // Create all schedules
            for (const schedule of schedulesToCreate) {
                const response = await auth.fetch(
                    this.apiBaseUrl + '/jadwal_kunjungan.php',
                    {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(schedule)
                    }
                );

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Gagal membuat jadwal');
                }
            }

            showAlert('Jadwal berhasil dibuat', 'success');
            this.modal.hide();
            await this.loadSchedules();

            btn.disabled = false;
            btn.innerHTML = 'Simpan';

        } catch (error) {
            console.error('Save error:', error);
            showAlert('Terjadi kesalahan: ' + error.message, 'danger');
            
            const btn = document.getElementById('btnSaveSchedule');
            btn.disabled = false;
            btn.innerHTML = 'Simpan';
        }
    }
}

/// Global instance
let jadwalManager = null;

window.addEventListener('DOMContentLoaded', () => {

    try {

        if (typeof auth === 'undefined') {
            console.error('auth.js belum termuat');
            alert('auth.js belum termuat');
            return;
        }

        if (!auth.isLoggedIn()) {
            window.location.href = 'login.html';
            return;
        }

        jadwalManager = new MasterJadwalManager();

        console.log('User:', auth.getUser());
        console.log('Role Admin:', auth.hasRole('admin'));
        console.log('Role Supervisor:', auth.hasRole('supervisor'));

        jadwalManager.init();

        console.log('Master Jadwal berhasil diinisialisasi');

    } catch (err) {

        console.error('Init Master Jadwal Error:', err);

        alert(
            'Terjadi error saat membuka Master Jadwal.\n\n' +
            err.message
        );

    }

});
