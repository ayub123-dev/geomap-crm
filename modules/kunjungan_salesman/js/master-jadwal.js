/**
 * Master Jadwal Kunjungan Module
 * Handle schedule creation, editing, deletion
 */

class MasterJadwalManager {
    constructor() {
        this.apiBaseUrl = '/api';
        this.currentDay = 'Senin';
        this.schedules = {};
        this.salesmans = [];
        this.customers = [];
        this.modal = null;
    }

    /**
     * Initialize
     */
    init() {
        // Check auth - hanya admin dan supervisor
        if (!['admin', 'supervisor'].includes(auth.getUser().role)) {
            showAlert('Akses ditolak. Hanya admin dan supervisor yang bisa mengakses halaman ini.', 'danger');
            setTimeout(() => window.history.back(), 2000);
            return;
        }

        this.modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
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
        document.getElementById('btnAddSchedule').addEventListener('click', () => {
            this.showAddScheduleModal();
        });

        // Save schedule button
        document.getElementById('btnSaveSchedule').addEventListener('click', () => {
            this.saveSchedule();
        });

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

            // Load customers
            const customersResponse = await auth.fetch(
                this.apiBaseUrl + '/customers.php'
            );
            const customersData = await customersResponse.json();

            if (customersData.success) {
                this.customers = customersData.data || [];
                this.populateCustomerSelect();
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
        const html = this.salesmans.map(s => `
            <option value="${s.id}">${s.name}</option>
        `).join('');

        select.innerHTML = '<option value="">-- Pilih Salesman --</option>' + html;
    }

    /**
     * Populate customer select
     */
    populateCustomerSelect() {
        const select = document.getElementById('formCustomer');
        const html = this.customers.map(c => `
            <option value="${c.id}">${c.name}</option>
        `).join('');

        select.innerHTML = '<option value="">-- Pilih Pelanggan --</option>' + html;
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
        document.getElementById('modalTitle').textContent = 'Tambah Jadwal';
        document.getElementById('scheduleForm').reset();
        document.getElementById('formDay').value = this.currentDay;
        document.getElementById('weekGroup').style.display = 'none';
        document.getElementById('freqEveryWeek').checked = true;

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

// Global instance
let jadwalManager = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (!auth.isLoggedIn()) {
        window.location.href = 'login.html';
        return;
    }

    jadwalManager = new MasterJadwalManager();
    jadwalManager.init();
});
