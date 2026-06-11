<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<!-- Tabulator JS + CSS (no theme - fully custom dark styling) -->
<link href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.2.5/dist/css/tabulator.min.css" rel="stylesheet" />
<style>
    /* ── Tabulator dark table matching Play2TV layout ── */
    .tabulator {
        background-color: var(--bg-panel, #1a1a2e) !important;
        border: none !important;
        font-size: .875rem;
        color: var(--text-main, #e2e8f0);
    }

    /* Header */
    .tabulator .tabulator-header {
        background-color: var(--bg-panel-strong, #16213e) !important;
        border-bottom: 2px solid #7c3aed !important;
    }
    .tabulator .tabulator-header .tabulator-col {
        background-color: var(--bg-panel-strong, #16213e) !important;
        border-right-color: var(--border-color, #2d2d44) !important;
        color: #a78bfa !important;
        font-size: .78rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: .03em;
    }
    .tabulator .tabulator-header .tabulator-col.tabulator-sortable:hover {
        background-color: rgba(124, 58, 237, .25) !important;
        color: #fff !important;
    }
    .tabulator .tabulator-header .tabulator-col.tabulator-sortable[aria-sort="ascending"] .tabulator-col-content .tabulator-arrow,
    .tabulator .tabulator-header .tabulator-col.tabulator-sortable[aria-sort="descending"] .tabulator-col-content .tabulator-arrow {
        border-bottom-color: #a78bfa !important;
    }

    /* Table holder */
    .tabulator .tabulator-tableholder {
        background-color: var(--bg-panel, #1a1a2e) !important;
    }

    /* Rows */
    .tabulator .tabulator-row {
        background-color: var(--bg-panel, #1a1a2e) !important;
        color: var(--text-main, #e2e8f0) !important;
        border-bottom: 1px solid #1e2035 !important;
        min-height: 42px;
    }
    .tabulator .tabulator-row:hover {
        background-color: rgba(124, 58, 237, .1) !important;
    }
    .tabulator .tabulator-row.tabulator-row-even {
        background-color: rgba(15, 15, 26, .5) !important;
    }
    .tabulator .tabulator-row.tabulator-row-odd {
        background-color: var(--bg-panel, #1a1a2e) !important;
    }
    .tabulator .tabulator-row.tabulator-selected {
        background-color: rgba(124, 58, 237, .2) !important;
    }
    .tabulator .tabulator-row .tabulator-cell {
        border-right-color: transparent !important;
        padding: .55rem .75rem;
    }

    /* Footer / Pagination */
    .tabulator .tabulator-footer {
        background-color: var(--bg-panel-strong, #16213e) !important;
        border-top: 2px solid #7c3aed !important;
        color: #cbd5e1 !important;
        padding: .5rem .75rem;
    }
    .tabulator .tabulator-footer .tabulator-page {
        background: var(--bg-main, #0f0f1a) !important;
        border: 1px solid var(--border-color, #2d2d44) !important;
        color: #cbd5e1 !important;
        border-radius: 6px;
        padding: .3rem .65rem;
        margin: 0 2px;
        font-size: .82rem;
    }
    .tabulator .tabulator-footer .tabulator-page.active {
        background: #7c3aed !important;
        border-color: #7c3aed !important;
        color: #fff !important;
        font-weight: 700;
        box-shadow: 0 0 8px rgba(124, 58, 237, .4);
    }
    .tabulator .tabulator-footer .tabulator-page:hover:not(.active):not(:disabled) {
        background: rgba(124, 58, 237, .25) !important;
        color: #fff !important;
        border-color: #a78bfa !important;
    }
    .tabulator .tabulator-footer .tabulator-page:disabled {
        opacity: .3 !important;
    }
    .tabulator .tabulator-footer .tabulator-page-size {
        background: var(--bg-main, #0f0f1a) !important;
        border: 1px solid #7c3aed !important;
        color: var(--text-main, #e2e8f0) !important;
        border-radius: 6px;
        padding: .25rem .5rem;
        margin-left: .5rem;
    }
    .tabulator .tabulator-footer .tabulator-paginator {
        color: #cbd5e1 !important;
    }

    /* Header filter inputs */
    .tabulator .tabulator-header-filter input {
        background: var(--bg-main, #0f0f1a) !important;
        border: 1px solid var(--border-color, #2d2d44) !important;
        color: var(--text-main, #e2e8f0) !important;
        border-radius: 6px !important;
        padding: .3rem .55rem;
        font-size: .82rem;
        width: 100%;
        box-sizing: border-box;
    }
    .tabulator .tabulator-header-filter input:focus {
        border-color: #7c3aed !important;
        outline: none;
        box-shadow: 0 0 0 .15rem rgba(124, 58, 237, .3);
    }
    .tabulator .tabulator-header-filter input::placeholder {
        color: #64748b;
    }
    .tabulator .tabulator-header-filter select {
        background: var(--bg-main, #0f0f1a) !important;
        border: 1px solid var(--border-color, #2d2d44) !important;
        color: var(--text-main, #e2e8f0) !important;
        border-radius: 6px !important;
        padding: .25rem .4rem;
        font-size: .82rem;
    }
    .tabulator .tabulator-header-filter select:focus {
        border-color: #7c3aed !important;
        outline: none;
    }

    /* Loading spinner */
    .tabulator .tabulator-loader {
        border-top-color: #7c3aed !important;
        border-right-color: #7c3aed !important;
        border-bottom-color: #7c3aed !important;
    }

    /* Empty state */
    .tabulator .tabulator-tableholder .tabulator-placeholder {
        padding: 2.5rem;
        background-color: var(--bg-panel, #1a1a2e) !important;
    }
    .tabulator .tabulator-tableholder .tabulator-placeholder .tabulator-placeholder-contents {
        color: #94a3b8;
        font-size: .9rem;
    }

    /* Row resize handle */
    .tabulator .tabulator-col-resize-handle:hover {
        background-color: #a78bfa !important;
    }

    /* ── Badge & link styles ── */
    .badge-pill { display: inline-block; padding: .25em .7em; font-size: .78rem; border-radius: 999px; font-weight: 600; line-height: 1.4; }
    .badge-success { background: #065f46; color: #6ee7b7; }
    .badge-primary { background: #5b21b6; color: #c4b5fd; }
    .badge-warning { background: #78350f; color: #fcd34d; }
    .badge-danger { background: #7f1d1d; color: #fca5a5; }
    .badge-secondary { background: #1e293b; color: #94a3b8; }

    .user-link { color: #c4b5fd; text-decoration: none; font-weight: 500; }
    .user-link:hover { color: #ddd6fe; text-decoration: underline; }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .7rem;
        border-radius: 6px;
        border: 1px solid #7c3aed;
        background: rgba(124, 58, 237, .15);
        color: #a78bfa;
        font-size: .8rem;
        font-weight: 500;
        text-decoration: none;
        transition: all .15s;
        white-space: nowrap;
    }
    .action-btn:hover {
        background: #7c3aed;
        color: #fff;
        border-color: #a78bfa;
        box-shadow: 0 0 10px rgba(124, 58, 237, .35);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Stats cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['total']) ?></div>
                <div class="stat-label">Totaal</div>
            </div>
            <i class="bi bi-receipt stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['yearly']) ?></div>
                <div class="stat-label">Jaarlijks</div>
            </div>
            <i class="bi bi-calendar-check stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['lifetime']) ?></div>
                <div class="stat-label">Lifetime</div>
            </div>
            <i class="bi bi-infinity stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-value"><?= esc((string) $stats['completed']) ?></div>
                <div class="stat-label">Voltooid</div>
            </div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
</div>

<!-- Tabulator table -->
<div class="card">
    <div class="card-header">
        <strong><i class="bi bi-table me-2"></i>Alle transacties</strong>
    </div>
    <div class="card-body p-2">
        <div id="billing-table"></div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Tabulator JS -->
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.2.5/dist/js/tabulator.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var baseUrl = '<?= base_url() ?>';

    // Clear old cached column layouts from localStorage
    try {
        var keysToRemove = [];
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (key && key.indexOf('admin-billing') === 0) {
                keysToRemove.push(key);
            }
        }
        for (var j = 0; j < keysToRemove.length; j++) {
            localStorage.removeItem(keysToRemove[j]);
        }
    } catch (e) {}

    var table = new Tabulator('#billing-table', {
        ajaxURL: baseUrl + 'admin/billing/data',
        ajaxConfig: {
            method: 'GET',
        },
        ajaxResponse: function (url, params, response) {
            return response;
        },
        pagination: true,
        paginationMode: 'remote',
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100],
        paginationCounter: 'rows',
        filterMode: 'remote',
        sortMode: 'remote',

        persistence: {
            sort: true,
            filter: true,
            page: true,
        },
        persistenceID: 'admin-billing-v3',
        persistenceMode: 'local',

        autoColumns: false,
        layout: 'fitDataStretch',
        height: 'auto',

        columns: [
            {
                title: 'Gebruiker',
                field: 'user_email',
                width: 200,
                sorter: 'string',
                headerFilter: 'input',
                headerFilterPlaceholder: 'Zoek gebruiker...',
                formatter: function (cell) {
                    var row = cell.getRow().getData();
                    var email = cell.getValue() || 'Onbekend';
                    var uid   = row.user_id;
                    return '<a href="' + baseUrl + 'admin/users/' + uid + '" class="user-link">'
                        + email + '</a>'
                        + '<br><small class="text-muted">ID: ' + uid + '</small>';
                },
            },
            {
                title: 'Product ID',
                field: 'product_id',
                width: 180,
                sorter: 'string',
                headerFilter: 'input',
                headerFilterPlaceholder: 'Zoek product...',
                formatter: function (cell) {
                    return '<code>' + (cell.getValue() || '—') + '</code>';
                },
            },
            {
                title: 'Type',
                field: 'plan_type',
                width: 100,
                hozAlign: 'center',
                sorter: 'string',
                headerFilter: 'list',
                headerFilterParams: {
                    values: {
                        'yearly': 'Jaarlijks',
                        'lifetime': 'Lifetime',
                        'monthly': 'Maandelijks',
                        'unknown': 'Onbekend',
                    },
                    clearable: true,
                },
                formatter: function (cell) {
                    var val = cell.getValue() || 'unknown';
                    var map = {
                        'yearly':   '<span class="badge-pill badge-primary">Jaarlijks</span>',
                        'lifetime': '<span class="badge-pill badge-success">Lifetime</span>',
                        'monthly':  '<span class="badge-pill badge-warning">Maandelijks</span>',
                    };
                    return map[val] || '<span class="badge-pill badge-secondary">' + val + '</span>';
                },
            },
            {
                title: 'Bedrag',
                field: 'amount',
                width: 100,
                hozAlign: 'right',
                sorter: 'number',
                formatter: function (cell) {
                    var row = cell.getRow().getData();
                    var amount = row.amount || '—';
                    var currencyDisplay = row.currency_display ? ' <small class="text-muted">' + row.currency_display + '</small>' : '';
                    return amount + currencyDisplay;
                },
            },
            {
                title: 'Status',
                field: 'status',
                width: 110,
                hozAlign: 'center',
                sorter: 'string',
                headerFilter: 'list',
                headerFilterParams: {
                    values: {
                        'completed': 'Voltooid',
                        'refunded': 'Terugbetaald',
                        'cancelled': 'Geannuleerd',
                        'pending':  'In afwachting',
                    },
                    clearable: true,
                },
                formatter: function (cell) {
                    var val = cell.getValue() || 'unknown';
                    var map = {
                        'completed': '<span class="badge-pill badge-success">Voltooid</span>',
                        'refunded':  '<span class="badge-pill badge-warning">Terugbetaald</span>',
                        'cancelled': '<span class="badge-pill badge-danger">Geannuleerd</span>',
                        'pending':   '<span class="badge-pill badge-secondary">In afwachting</span>',
                    };
                    return map[val] || '<span class="badge-pill badge-secondary">' + val + '</span>';
                },
            },
            {
                title: 'Datum',
                field: 'created_at',
                width: 140,
                sorter: 'string',
                formatter: function (cell) {
                    var row = cell.getRow().getData();
                    var raw = row.created_at_raw || '';
                    return '<span title="' + raw + '">' + (cell.getValue() || '—') + '</span>';
                },
            },
            {
                title: 'Acties',
                field: 'id',
                width: 100,
                hozAlign: 'center',
                headerSort: false,
                headerFilter: false,
                formatter: function (cell) {
                    var id = cell.getValue();
                    return '<a href="' + baseUrl + 'admin/billing/' + id + '" class="action-btn">'
                        + '<i class="bi bi-eye"></i> Bekijk</a>';
                },
            },
        ],

        locale: true,
        langs: {
            'nl': {
                'ajax': {
                    'loading': 'Laden...',
                    'error': 'Fout bij ophalen data',
                },
                'pagination': {
                    'page_size': 'Per pagina',
                    'page_title': 'Toon pagina',
                    'first': 'Eerste',
                    'first_title': 'Eerste pagina',
                    'last': 'Laatste',
                    'last_title': 'Laatste pagina',
                    'prev': 'Vorige',
                    'prev_title': 'Vorige pagina',
                    'next': 'Volgende',
                    'next_title': 'Volgende pagina',
                    'all': 'Alles',
                    'counter': {
                        'showing': 'Toont',
                        'of': 'van de',
                        'rows': 'rijen',
                        'pages': 'pagina\'s',
                    },
                },
            },
        },
    });

    table.setLocale('nl');

});
</script>
<?= $this->endSection() ?>