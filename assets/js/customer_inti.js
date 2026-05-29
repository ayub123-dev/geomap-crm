(function (window, $) {
    "use strict";

    var moduleEl = document.getElementById("customerIntiModule");
    if (!moduleEl) return;

    var apiBase = window.GeoMapCRM.apiBase;
    var $table = $("#customerIntiTableBody");
    var $search = $("#searchCustomerInti");
    var $pagination = $("#customerIntiPagination");
    var $summary = $("#customerIntiSummary");
    var modal = new bootstrap.Modal(document.getElementById('customerIntiModal'));
    var importModal = new bootstrap.Modal(document.getElementById('customerIntiImportModal'));
    var cache = [];
    var state = { page: 1, pageSize: 10, total: 0, search: ''}; 
    var searchTimer = null;

    function formatCoord(item) {
        if (item.lat === null || item.lng === null) return '-';
        return Number(item.lat).toFixed(5) + ', ' + Number(item.lng).toFixed(5);
    }

    function renderRows(rows) {
        if (!rows.length) {
            $table.html('<tr><td colspan="6" class="text-center text-muted py-4">Data belum tersedia.</td></tr>');
            return;
        }

        var html = '';
        rows.forEach(function (r) {
            html += '<tr>' +
                '<td>' + (r.kode_customer || '-') + '</td>' +
                '<td>' + (r.nama_toko || '-') + '</td>' +
                '<td>' + (r.kategori_toko || '-') + '</td>' +
                '<td>' + (r.no_hp || '-') + '</td>' +
                '<td class="small">' + formatCoord(r) + '</td>' +
                '<td class="text-center">' +
                '<div class="d-inline-flex gap-1 align-items-center">' +
                '<button class="btn btn-sm btn-outline-primary btn-edit" type="button" data-id="' + r.id + '" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square"></i></button>' +
                '<button class="btn btn-sm btn-outline-danger btn-delete" type="button" data-id="' + r.id + '" title="Hapus" aria-label="Hapus"><i class="bi bi-trash"></i></button>' +
                '</div>' +
                '</td></tr>';
        });
        $table.html(html);
    }

    function renderPagination() {
        var totalPages = Math.max(1, Math.ceil(state.total / state.pageSize));
        if (state.page > totalPages) {
            state.page = totalPages;
            fetchList(state.page);
            return;
        }

        var start = state.total === 0 ? 0 : ((state.page - 1) * state.pageSize) + 1;
        var end = Math.min(state.total, state.page * state.pageSize);
        $summary.text('Menampilkan ' + start + ' - ' + end + ' dari ' + state.total + ' data');

        var html = '<div class="btn-group btn-group-sm" role="group">' +
            '<button class="btn btn-outline-secondary" type="button" data-page="prev" ' + (state.page === 1 ? 'disabled' : '') + '>Prev</button>' +
            '<button class="btn btn-outline-secondary" type="button" disabled>' + state.page + ' / ' + totalPages + '</button>' +
            '<button class="btn btn-outline-secondary" type="button" data-page="next" ' + (state.page >= totalPages ? 'disabled' : '') + '>Next</button>' +
            '</div>';

        $pagination.html(html);
    }

    function fetchList(page) {
        if (page) {
            state.page = page;
        }

        $table.html('<tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>');
        $.get(apiBase + '/customer_inti.php', {
            search: state.search,
            limit: state.pageSize,
            offset: (state.page - 1) * state.pageSize
        }).done(function (resp) {
            cache = resp.data || [];
            state.total = resp.meta && resp.meta.total ? Number(resp.meta.total) : 0;
            renderRows(cache);
            renderPagination();
        }).fail(function () {
            $table.html('<tr><td colspan="6" class="text-center text-danger py-4">Gagal memuat data.</td></tr>');
            $summary.text('Gagal memuat data.');
        });
    }

    function resetForm() {
        $('#customerIntiForm')[0].reset();
        $('#customerIntiId').val('');
        $('#customerIntiStatus').val('Aktif');
    }

    function openCreate() {
        resetForm();
        document.querySelector('#customerIntiModal .modal-title').textContent = 'Tambah Customer Inti';
        modal.show();
    }

    function openEdit(id) {
        var item = cache.find(function (c) { return String(c.id) === String(id); });
        if (!item) return window.GeoMapCRM.notify('warning', 'Data tidak ditemukan.');
        $('#customerIntiId').val(item.id);
        $('#customerIntiCode').val(item.kode_customer || '');
        $('#customerIntiName').val(item.nama_toko || '');
        $('#customerIntiOwner').val(item.pemilik || '');
        $('#customerIntiPhone').val(item.no_hp || '');
        $('#customerIntiAddress').val(item.alamat || '');
        $('#customerIntiLat').val(item.lat || '');
        $('#customerIntiLng').val(item.lng || '');
        $('#customerIntiStatus').val(item.status || 'Aktif');
        document.querySelector('#customerIntiModal .modal-title').textContent = 'Edit Customer Inti';
        modal.show();
    }

    // Fungsi untuk mengumpulkan data dari form sebelum dikirim ke server
    function collectPayload() {
        return {
            kode_customer: $('#customerIntiCode').val().trim(),
            nama_toko: $('#customerIntiName').val().trim(),
            pemilik: $('#customerIntiOwner').val().trim(),
            no_hp: $('#customerIntiPhone').val().trim(),
            alamat: $('#customerIntiAddress').val().trim(),
            kelurahan: $('#customerIntiKelurahan').val() || '',
            kecamatan: $('#customerIntiKecamatan').val() || '',
            kota: $('#customerIntiKota').val() || '',
            provinsi: $('#customerIntiProvinsi').val() || '',
            lat: $('#customerIntiLat').val() === '' ? null : Number($('#customerIntiLat').val()),
            lng: $('#customerIntiLng').val() === '' ? null : Number($('#customerIntiLng').val()),
            kategori_toko: $('#customerIntiKategori').val() || '',
            omzet_estimasi: $('#customerIntiOmzet').val() || 0,
            salesman_id: $('#customerIntiSalesman').val() || null,
            status: $('#customerIntiStatus').val(),
            foto_toko: $('#customerIntiPhotoUrl').val() || '',
        };
    }

    function applySearch() {
        state.search = $search.val().trim();
        state.page = 1;
        fetchList(1);
    }

    function scheduleSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applySearch, 250);
    }

    $('#btnAddCustomerInti').on('click', openCreate);
    $('#btnSearchCustomerInti').on('click', applySearch);
    $search.on('input', scheduleSearch);
    $search.on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            applySearch();
        }
    });

    $pagination.on('click', 'button[data-page]', function () {
        var action = $(this).data('page');
        if (action === 'prev' && state.page > 1) {
            fetchList(state.page - 1);
        }
        if (action === 'next') {
            fetchList(state.page + 1);
        }
    });

    $('#customerIntiTableBody').on('click', '.btn-edit', function () { openEdit($(this).data('id')); });
    $('#customerIntiTableBody').on('click', '.btn-delete', function () {
        if (!confirm('Yakin ingin menghapus data ini?')) return;
        var id = $(this).data('id');
        $.ajax({ url: apiBase + '/customer_inti_detail.php?id=' + encodeURIComponent(id), method: 'DELETE' }).done(function (r) {
            window.GeoMapCRM.notify('success', r.message || 'Terhapus'); fetchList(state.page);
        }).fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
    });

    $('#customerIntiForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#customerIntiId').val();
        var payload = collectPayload();
        if (String(id).trim() === '') {
            $.ajax({ url: apiBase + '/customer_inti.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(payload) })
                .done(function (r) { modal.hide(); window.GeoMapCRM.notify('success', r.message || 'Tersimpan'); fetchList(1); })
                .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
        } else {
            $.ajax({ url: apiBase + '/customer_inti_detail.php?id=' + encodeURIComponent(id), method: 'PUT', contentType: 'application/json', data: JSON.stringify(payload) })
                .done(function (r) { modal.hide(); window.GeoMapCRM.notify('success', r.message || 'Diupdate'); fetchList(state.page); })
                .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
        }
    });

    $('#btnImportCustomerInti').on('click', function () { $('#customerIntiImportPreview').html(''); $('#customerIntiImportFile').val(''); importModal.show(); });
    $('#customerIntiImportForm').on('submit', function (e) { e.preventDefault(); });
    $('#customerIntiImportCommit').on('click', function () {
        var file = $('#customerIntiImportFile')[0].files[0];
        if (!file) return window.GeoMapCRM.notify('warning', 'Pilih file terlebih dahulu.');
        var form = new FormData(); form.append('file', file); form.append('mode', 'commit');
        window.GeoMapCRM.appendCsrfToFormData(form);
        $.ajax({ url: apiBase + '/customer_inti_import.php', method: 'POST', data: form, processData: false, contentType: false })
            .done(function (r) { importModal.hide(); window.GeoMapCRM.notify('success', r.message || 'Import selesai'); fetchList(1); })
            .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
    });

    $('#customerIntiImportFile').on('change', function () {
        var file = this.files[0]; if (!file) return;
        var form = new FormData(); form.append('file', file); form.append('mode', 'preview');
        window.GeoMapCRM.appendCsrfToFormData(form);
        $('#customerIntiImportPreview').html('Memproses...');
        $.ajax({ url: apiBase + '/customer_inti_import.php', method: 'POST', data: form, processData: false, contentType: false })
            .done(function (r) {
                var html = '<div class="alert alert-secondary">Preview: ' + (r.data.summary.total || 0) + ' baris, siap: ' + (r.data.summary.success || 0) + ', gagal: ' + (r.data.summary.failed || 0) + '</div>';
                html += '<div style="max-height:300px;overflow:auto;"><pre style="white-space:pre-wrap;">' + JSON.stringify(r.data.rows.slice(0, 50), null, 2) + '</pre></div>';
                $('#customerIntiImportPreview').html(html);
            }).fail(function (xhr) { $('#customerIntiImportPreview').html('<div class="text-danger">' + (xhr.responseJSON ? xhr.responseJSON.message : 'Gagal') + '</div>'); });
    });

    $('#btnExportCustomerInti').on('click', function () {
        var url = apiBase + '/customer_inti_export.php?format=excel&search=' + encodeURIComponent($search.val() || '');
        window.location = url;
    });

    fetchList(1);
})(window, jQuery);
