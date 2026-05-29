(function (window, $) {
    "use strict";

    var moduleEl = document.getElementById('customerExistingModule');
    if (!moduleEl) return;

    var apiBase = window.GeoMapCRM.apiBase;
    var $table = $('#customerExistingTableBody');
    var $search = $('#searchCustomerExisting');
    var $pagination = $('#customerExistingPagination');
    var $summary = $('#customerExistingSummary');
    var modal = new bootstrap.Modal(document.getElementById('customerExistingModal'));
    var importModal = new bootstrap.Modal(document.getElementById('customerExistingImportModal'));
    var cache = [];
    var state = { page: 1, pageSize: 10, total: 0, search: '' };
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
                '<td>' + (r.kode_existing || '-') + '</td>' +
                '<td>' + (r.nama_toko || '-') + '</td>' +
                '<td>' + (r.brand_kompetitor || '-') + '</td>' +
                '<td class="small">' + (r.alamat || '-') + '</td>' +
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
        $.get(apiBase + '/customer_existing.php', {
            search: state.search,
            limit: state.pageSize,
            offset: (state.page - 1) * state.pageSize
        }).done(function (r) {
            cache = r.data || [];
            state.total = r.meta && r.meta.total ? Number(r.meta.total) : 0;
            renderRows(cache);
            renderPagination();
        }).fail(function () {
            $table.html('<tr><td colspan="6" class="text-center text-danger py-4">Gagal memuat data.</td></tr>');
            $summary.text('Gagal memuat data.');
        });
    }

    function resetForm() {
        $('#customerExistingForm')[0].reset();
        $('#customerExistingId').val('');
        $('#customerExistingSource').val('Internal');
    }

    function openCreate() {
        resetForm();
        document.querySelector('#customerExistingModal .modal-title').textContent = 'Tambah Customer Existing';
        modal.show();
    }

    function openEdit(id) {
        var it = cache.find(function (c) { return String(c.id) === String(id); });
        if (!it) return window.GeoMapCRM.notify('warning', 'Data tidak ditemukan.');
        $('#customerExistingId').val(it.id);
        $('#customerExistingCode').val(it.kode_existing || '');
        $('#customerExistingName').val(it.nama_toko || '');
        $('#customerExistingBrand').val(it.brand_kompetitor || '');
        $('#customerExistingAddress').val(it.alamat || '');
        $('#customerExistingLat').val(it.lat || '');
        $('#customerExistingLng').val(it.lng || '');
        $('#customerExistingSource').val(it.sumber_data || 'Internal');
        document.querySelector('#customerExistingModal .modal-title').textContent = 'Edit Customer Existing';
        modal.show();
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

    $('#btnAddCustomerExisting').on('click', openCreate);
    $('#btnSearchCustomerExisting').on('click', applySearch);
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

    $('#customerExistingTableBody').on('click', '.btn-edit', function () { openEdit($(this).data('id')); });
    $('#customerExistingTableBody').on('click', '.btn-delete', function () {
        if (!confirm('Yakin ingin menghapus?')) return;
        var id = $(this).data('id');
        $.ajax({ url: apiBase + '/customer_existing_detail.php?id=' + encodeURIComponent(id), method: 'DELETE' })
            .done(function (r) { window.GeoMapCRM.notify('success', r.message || 'Terhapus'); fetchList(state.page); })
            .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
    });

    $('#customerExistingForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#customerExistingId').val();
        var payload = {
            kode_existing: $('#customerExistingCode').val().trim(),
            nama_toko: $('#customerExistingName').val().trim(),
            brand_kompetitor: $('#customerExistingBrand').val().trim(),
            alamat: $('#customerExistingAddress').val().trim(),
            lat: $('#customerExistingLat').val() === '' ? null : Number($('#customerExistingLat').val()),
            lng: $('#customerExistingLng').val() === '' ? null : Number($('#customerExistingLng').val()),
            sumber_data: $('#customerExistingSource').val(),
        };

        if (String(id).trim() === '') {
            $.ajax({ url: apiBase + '/customer_existing.php', method: 'POST', contentType: 'application/json', data: JSON.stringify(payload) })
                .done(function (r) { modal.hide(); window.GeoMapCRM.notify('success', r.message || 'Tersimpan'); fetchList(1); })
                .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
        } else {
            $.ajax({ url: apiBase + '/customer_existing_detail.php?id=' + encodeURIComponent(id), method: 'PUT', contentType: 'application/json', data: JSON.stringify(payload) })
                .done(function (r) { modal.hide(); window.GeoMapCRM.notify('success', r.message || 'Diupdate'); fetchList(state.page); })
                .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
        }
    });

    $('#btnImportCustomerExisting').on('click', function () { $('#customerExistingImportPreview').html(''); $('#customerExistingImportFile').val(''); importModal.show(); });
    $('#customerExistingImportForm').on('submit', function (e) { e.preventDefault(); });
    $('#customerExistingImportCommit').on('click', function () {
        var file = $('#customerExistingImportFile')[0].files[0];
        if (!file) return window.GeoMapCRM.notify('warning', 'Pilih file.');
        var form = new FormData(); form.append('file', file); form.append('mode', 'commit');
        window.GeoMapCRM.appendCsrfToFormData(form);
        $.ajax({ url: apiBase + '/customer_existing_import.php', method: 'POST', data: form, processData: false, contentType: false })
            .done(function (r) { importModal.hide(); window.GeoMapCRM.notify('success', r.message || 'Import selesai'); fetchList(1); })
            .fail(function (xhr) { window.GeoMapCRM.notify('danger', xhr.responseJSON ? xhr.responseJSON.message : 'Gagal'); });
    });

    $('#customerExistingImportFile').on('change', function () {
        var file = this.files[0]; if (!file) return;
        var form = new FormData(); form.append('file', file); form.append('mode', 'preview');
        window.GeoMapCRM.appendCsrfToFormData(form);
        $('#customerExistingImportPreview').html('Memproses...');
        $.ajax({ url: apiBase + '/customer_existing_import.php', method: 'POST', data: form, processData: false, contentType: false })
            .done(function (r) {
                var html = '<div class="alert alert-secondary">Preview: ' + (r.data.summary.total || 0) + ' baris, siap: ' + (r.data.summary.success || 0) + ', gagal: ' + (r.data.summary.failed || 0) + '</div>';
                html += '<div style="max-height:300px;overflow:auto;"><pre style="white-space:pre-wrap;">' + JSON.stringify(r.data.rows.slice(0, 50), null, 2) + '</pre></div>';
                $('#customerExistingImportPreview').html(html);
            }).fail(function (xhr) { $('#customerExistingImportPreview').html('<div class="text-danger">' + (xhr.responseJSON ? xhr.responseJSON.message : 'Gagal') + '</div>'); });
    });

    $('#btnExportCustomerExisting').on('click', function () {
        var url = apiBase + '/customer_existing_export.php?format=excel&search=' + encodeURIComponent($search.val() || '');
        window.location = url;
    });

    fetchList(1);
})(window, jQuery);
