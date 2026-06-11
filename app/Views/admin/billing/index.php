<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<!-- Tabulator CSS (simple base, fully custom styled) -->
<link href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.2.5/dist/css/tabulator_simple.min.css" rel="stylesheet" />
<style>
    /* ── Tabulator fully custom Play2TV dark theme ── */
    :root {
        --tbg-main: #0f0f1a;
        --tbg-panel: #1a1a2e;
        --tbg-panel-strong: #16213e;
        --tborder: #2d2d44;
        --ttext: #e2e8f0;
        --ttext-soft: #cbd5e1;
        --ttext-muted: #94a3b8;
        --tpurple: #7c3aed;
        --tpurple-light: #a78bfa;
        --tpurple-dark: #5b21b6;
    }

    .tabulator {
        background: transparent;
        border: none;
        font-size: .875rem;
        color: var(--ttext);
    }

    /* Header */
    .tabulator .tabulator-header {
        background: var(--tbg-panel-strong);
        border-bottom: 2px solid var(--tpurple);
    }
    .tabulator .tabulator-header .tabulator-col {
        background: var(--tbg-panel-strong);
        border-right-color: var(--tborder);
        color: var(--tpurple-light);
        font-size: .78rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: .03em;
    }
    .tabulator .tabulator-header .tabulator-col.tabulator-sortable:hover {
        background: rgba(124, 58, 237, .2);
        color: #fff;
    }
    .tabulator .tabulator-header .tabulator-col.tabulator-sortable[aria-sort="ascending"] .tabulator-col-content .tabulator-arrow,
    .tabulator .tabulator-header .tabulator-col.tabulator-sortable[aria-sort="descending"] .tabulator-col-content .tabulator-arrow {
        border-bottom-color: var(--tpurple-light);
    }

    /* Rows */
    .tabulator .tabulator-tableholder {
        background: transparent;
    }
    .tabulator .tabulator-row {
        background: transparent;
        color: var(--ttext);
        border-bottom: 1px solid rgba(45, 45, 68, .5);
        min-height: 42px;
    }
    .tabulator .tabulator-row:hover {
        background: rgba(124, 58, 237, .1) !important;
    }
    .tabulator .tabulator-row.tabulator-row-even {
        background: rgba(26, 26, 46, .35);
    }
    .tabulator .tabulator-row .tabulator-cell {
        border-right-color: transparent;
        padding: .55rem .75rem;
    }

    /* Selection highlight */
    .tabulator .tabulator-row.tabulator-selected {
        background: rgba(124, 58, 237, .2) !important;
    }

    /* Footer */
    .tabulator .tabulator-footer {
        background: var(--tbg-panel-strong);
        border-top: 2px solid var(--tpurple);
        color: var(--ttext-soft);
        padding: .5rem .75rem;
    }
    .tabulator .tabulator-footer .tabulator-page {
        background: var(--tbg-main);
        border: 1px solid var(--tborder);
        color: var(--ttext-soft);
        border-radius: 6px;
        padding: .3rem .65rem;
        margin: 0 2px;
        font-size: .82rem;
    }
    .tabulator .tabulator-footer .tabulator-page.active {
        background: var(--tpurple);
        border-color: var(--tpurple);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 0 8px rgba(124, 58, 237, .4);
    }
    .tabulator .tabulator-footer .tabulator-page:hover:not(.active):not(:disabled) {
        background: rgba(124, 58, 237, .25);
        color: #fff;
        border-color: var(--tpurple-light);
    }
    .tabulator .tabulator-footer .tabulator-page:disabled {
        opacity: .3;
        cursor: not-allowed;
    }
    .tabulator .tabulator-footer .tabulator-page-size {
        background: var(--tbg-main);
        border: 1px solid var(--tpurple);
        color: var(--ttext);
        border-radius: 6px;
        padding: .25rem .5rem;
        margin-left: .5rem;
    }
    .tabulator .tabulator-footer .tabulator-page-size:focus {
        border-color: var(--tpurple-light);
        outline: none;
        box-shadow: 0 0 0 .2rem rgba(124, 58, 237, .3);
    }
    .tabulator .tabulator-footer .tabulator-paginator {
        color: var(--ttext-soft);
    }

    /* Header filters (search inputs) */
    .tabulator .tabulator-header-filter input {
        background: var(--tbg-main);
        border: 1px solid var(--tborder);
        color: var(--ttext);
        border-radius: 6px;
        padding: .3rem .55rem;
        font-size: .82rem;
        width: 100%;
        box-sizing: border-box;
    }
    .tabulator .tabulator-header-filter input:focus {
        border-color: var(--tpurple);
        outline: none;
        box-shadow: 0 0 0 .15rem rgba(124, 58, 237, .3);
    }
    .tabulator .tabulator-header-filter input::placeholder {
        color: #64748b;
    }

    /* Header filter select */
    .tabulator .tabulator-header-filter select {
        background: var(--tbg-main);
        border: 1px solid var(--tborder);
        color: var(--ttext);
        border-radius: 6px;
        padding: .25rem .4rem;
        font-size: .82rem;
    }
    .tabulator .tabulator-header-filter select:focus {
        border-color: var(--tpurple);
        outline: none;
        box-shadow: 0 0 0 .15rem rgba(124, 58, 237, .3);
    }

    /* Loading indicator */
    .tabulator .tabulator-loader {
        border-top-color: var(--tpurple) !important;
        border-right-color: var(--tpurple) !important;
        border-bottom-color: var(--tpurple) !important;
    }

    /* Empty state */
    .tabulator .tabulator-tableholder .tabulator-placeholder {
        padding: 2.5rem;
    }
    .tabulator .tabulator-tableholder .tabulator-placeholder .tabulator-placeholder-contents {
        color: var(--ttext-muted);
        font-size: .9rem;
    }

    /* Progress/resize handles */
    .tabulator .tabulator-col-resize-handle:hover {
        background-color: var(--tpurple-light);
    }

    /* ── Badge styles ── */
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
        padding: .3rem .65rem;
        border-radius: 6px;
        border: 1px solid var(--tpurple);
        background: rgba(124, 58, 237, .15);
        color: var(--tpurple-light);
        font-size: .8rem;
        font-weight: 500;
        text-decoration: none;
        transition: all .15s;
        white-space: nowrap;
    }
    .action-btn:hover {
        background: var(--tpurple);
        color: #fff;
        border-color: var(--tpurple-light);
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

    const baseUrl = '<?= base_url() ?>';

    const table = new Tabulator('#billing-table', {
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

        // ── Persistence: onthoud pagina, page size, filters, sortering ──
        persistence: true,
        persistenceID: 'admin-billing-table',
        persistenceMode: 'local',

        layout: 'fitDataStretch',
        height: 'auto',

        columns: [
            {
                title: '#',
                field: 'id',
                visible: false,
            },
            {
                title: 'Gebruiker',
                field: 'user_email',
                width: 200,
                sorter: 'string',
                headerFilter: 'input',
                headerFilterPlaceholder: 'Zoek gebruiker...',
                formatter: function (cell, formatterParams, onRendered) {
                    const row = cell.getRow().getData();
                    const email = cell.getValue() || 'Onbekend';
                    const uid   = row.user_id;
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
                    const val = cell.getValue() || 'unknown';
                    const map = {
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
                    const row = cell.getRow().getData();
                    let amount = row.amount || '—';
                    let currency = row.currency ? ' <small class="text-muted">' + row.currency + '</small>' : '';
                    return amount + currency;
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
                    const val = cell.getValue() || 'unknown';
                    const map = {
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
                    const row = cell.getRow().getData();
                    const raw = row.created_at_raw || '';
                    return '<span title="' + raw + '">' + (cell.getValue() || '—') + '</span>';
                },
            },
            {
                title: 'Acties',
                field: 'id',
                width: 90,
                hozAlign: 'center',
                headerSort: false,
                headerFilter: false,
                formatter: function (cell) {
                    const id = cell.getValue();
                    return '<a href="' + baseUrl + 'admin/billing/' + id + '" class="action-btn">'
                        + '<i class="bi bi-eye"></i> Bekijk</a>';
                },
            },
        ],

        // Nederlandse teksten
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