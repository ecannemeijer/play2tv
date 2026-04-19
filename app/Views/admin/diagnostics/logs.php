<?= $this->extend('admin/layout') ?>

<?= $this->section('head') ?>
<style>
    .log-primary-filters {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: .9rem;
    }
    .log-viewer-shell {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }
    .log-meta-grid {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: minmax(132px, 1fr);
        gap: .6rem;
        overflow-x: auto;
        padding-bottom: .15rem;
    }
    .log-meta-card {
        background: #121528;
        border: 1px solid #242945;
        border-radius: 12px;
        padding: .65rem .8rem;
        min-height: 78px;
    }
    .log-meta-label {
        color: #94a3b8;
        font-size: .66rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .log-meta-value {
        color: #f8fafc;
        font-weight: 600;
        margin-top: .22rem;
        font-size: .92rem;
        line-height: 1.25;
        word-break: break-word;
    }
    .log-viewer {
        background: linear-gradient(180deg, #0f172a, #111827);
        border: 1px solid #27324b;
        border-radius: 16px;
        overflow: hidden;
    }
    .log-modal .modal-content {
        background: #111827;
        border: 1px solid #27324b;
        color: #e2e8f0;
    }
    .log-modal .modal-header,
    .log-modal .modal-footer {
        border-color: #27324b;
    }
    .log-modal .btn-close {
        filter: invert(1) grayscale(1);
    }
    .log-modal-filter {
        display: flex;
        gap: .75rem;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .log-modal-filter .form-control {
        min-width: 320px;
    }
    .log-modal-list {
        max-height: 70vh;
        overflow: auto;
        display: grid;
        gap: .65rem;
    }
    .log-modal-item {
        display: flex;
        justify-content: space-between;
        gap: .9rem;
        padding: .8rem .9rem;
        border: 1px solid #28344d;
        border-radius: 14px;
        background: rgba(15, 23, 42, .8);
    }
    .log-modal-item.active {
        border-color: rgba(167, 139, 250, .55);
        box-shadow: inset 0 0 0 1px rgba(167, 139, 250, .25);
    }
    .log-modal-main {
        min-width: 0;
        flex: 1;
    }
    .log-modal-title {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
        margin-bottom: .4rem;
    }
    .log-file-name {
        font-weight: 700;
        color: #f8fafc;
        word-break: break-word;
    }
    .log-file-badges {
        display: flex;
        gap: .35rem;
        flex-wrap: wrap;
    }
    .log-file-badge {
        display: inline-flex;
        align-items: center;
        padding: .16rem .45rem;
        border-radius: 999px;
        background: rgba(51, 65, 85, .8);
        border: 1px solid #334155;
        color: #cbd5e1;
        font-size: .72rem;
        line-height: 1.1;
    }
    .log-file-badge-accent {
        background: rgba(76, 29, 149, .28);
        border-color: rgba(167, 139, 250, .35);
        color: #ddd6fe;
    }
    .log-file-meta {
        display: flex;
        gap: .8rem;
        flex-wrap: wrap;
        font-size: .8rem;
        color: #94a3b8;
    }
    .log-modal-actions {
        display: flex;
        gap: .45rem;
        align-items: flex-start;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .log-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: .9rem 1.1rem;
        border-bottom: 1px solid #27324b;
        background: rgba(15, 23, 42, .85);
    }
    .log-secondary-filters {
        display: flex;
        gap: .65rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .log-filter-field {
        min-width: 160px;
    }
    .log-lines {
        max-height: calc(100vh - 245px);
        overflow: auto;
        padding: 1rem;
        background: radial-gradient(circle at top, rgba(30, 41, 59, .6), rgba(15, 23, 42, .98));
    }
    .log-header-block {
        background: rgba(15, 23, 42, .82);
        border: 1px solid #24314a;
        border-radius: 14px;
        padding: 1rem 1.1rem;
        margin-bottom: 1rem;
    }
    .log-header-line {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        padding: .2rem 0;
        font-family: Consolas, 'Cascadia Code', monospace;
        font-size: .9rem;
    }
    .log-header-key { color: #f9a8d4; font-weight: 700; }
    .log-header-value { color: #e2e8f0; }
    .log-entry-list {
        display: grid;
        gap: .75rem;
    }
    .log-entry-card {
        border: 1px solid #29344d;
        border-left-width: 5px;
        border-radius: 14px;
        padding: .85rem .95rem;
        background: rgba(15, 23, 42, .84);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.02);
    }
    .log-entry-neutral { border-left-color: #818cf8; }
    .log-entry-warning { border-left-color: #f59e0b; background: rgba(120, 53, 15, .14); }
    .log-entry-error { border-left-color: #ef4444; background: rgba(127, 29, 29, .16); }
    .log-entry-success { border-left-color: #10b981; background: rgba(6, 78, 59, .16); }
    .log-entry-debug { border-left-color: #fbbf24; background: rgba(120, 53, 15, .11); }
    .log-entry-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: .6rem;
    }
    .log-entry-title { min-width: 0; }
    .log-entry-event {
        font-size: .95rem;
        font-weight: 800;
        color: #f8fafc;
        letter-spacing: .01em;
    }
    .log-entry-detail {
        margin-top: .2rem;
        color: #d5dfeb;
        font-size: .89rem;
        line-height: 1.35;
    }
    .log-entry-time {
        font-family: Consolas, 'Cascadia Code', monospace;
        color: #93c5fd;
        background: rgba(37, 99, 235, .12);
        border: 1px solid rgba(96, 165, 250, .18);
        border-radius: 999px;
        padding: .25rem .6rem;
        white-space: nowrap;
    }
    .log-entry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: .55rem;
    }
    .log-chip {
        background: rgba(30, 41, 59, .72);
        border: 1px solid #2a3955;
        border-radius: 10px;
        padding: .55rem .7rem;
        min-width: 0;
    }
    .log-chip-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: .3rem;
    }
    .log-chip-value {
        font-family: Consolas, 'Cascadia Code', monospace;
        color: #dbeafe;
        font-size: .82rem;
        line-height: 1.35;
        word-break: break-word;
    }
    .log-results-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(30, 41, 59, .72);
        border: 1px solid #314056;
        color: #dbeafe;
        border-radius: 999px;
        padding: .3rem .7rem;
        font-size: .8rem;
    }
    .log-footer-note {
        margin-top: 1rem;
        padding: .9rem 1rem;
        border-radius: 12px;
        background: rgba(71, 85, 105, .18);
        border: 1px solid #334155;
        color: #cbd5e1;
        font-family: Consolas, 'Cascadia Code', monospace;
        font-size: .88rem;
    }
    .log-empty-state {
        padding: 3rem 2rem;
        text-align: center;
        color: #94a3b8;
    }
    .log-live-alert {
        display: none;
        margin-bottom: 1rem;
    }
    .log-live-alert.is-visible {
        display: block;
    }
    .log-fragment-loading {
        opacity: .65;
        pointer-events: none;
        transition: opacity .16s ease;
    }
    @media (max-width: 1200px) {
        .log-meta-grid { grid-auto-flow: row; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php $fragmentData = [
    'logFiles' => $logFiles,
    'selectedFile' => $selectedFile,
    'query' => $query,
    'severity' => $severity,
    'term' => $term,
    'openPicker' => $openPicker,
    'activeLog' => $activeLog,
    'contentLines' => $contentLines,
    'meta' => $meta,
    'headerLines' => $headerLines,
    'parsedEntries' => $parsedEntries,
    'totalParsedEntries' => $totalParsedEntries,
    'footerLines' => $footerLines,
    'truncated' => $truncated,
]; ?>

<div id="diagnosticsLiveAlert" class="alert alert-danger log-live-alert" role="alert"></div>

<div id="diagnosticsToolbar">
    <?= view('admin/diagnostics/partials/logs_toolbar', $fragmentData) ?>
</div>

<div id="diagnosticsViewerShell">
    <?= view('admin/diagnostics/partials/logs_viewer', $fragmentData) ?>
</div>

<div class="modal fade log-modal" id="logFilesModal" tabindex="-1" aria-labelledby="logFilesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" id="diagnosticsModalContent">
            <?= view('admin/diagnostics/partials/logs_modal_content', $fragmentData) ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toolbarContainer = document.getElementById('diagnosticsToolbar');
    const viewerContainer = document.getElementById('diagnosticsViewerShell');
    const alertElement = document.getElementById('diagnosticsLiveAlert');
    const modalElement = document.getElementById('logFilesModal');
    const modalContent = document.getElementById('diagnosticsModalContent');

    if (!toolbarContainer || !viewerContainer || !alertElement || !modalElement || !modalContent) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const basePageUrl = <?= json_encode(base_url('admin/diagnostics/logs')) ?>;
    const fragmentsUrl = <?= json_encode(base_url('admin/diagnostics/logs/fragments')) ?>;
    const state = <?= json_encode([
        'selectedFile' => $selectedFile,
        'query' => $query,
        'severity' => $severity,
        'term' => $term,
        'openPicker' => $openPicker,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let searchDebounceHandle = null;
    let requestCounter = 0;

    function toggleLoading(isLoading) {
        toolbarContainer.classList.toggle('log-fragment-loading', isLoading);
        viewerContainer.classList.toggle('log-fragment-loading', isLoading);
        modalContent.classList.toggle('log-fragment-loading', isLoading);
    }

    function setAlert(message) {
        if (!message) {
            alertElement.textContent = '';
            alertElement.classList.remove('is-visible');
            return;
        }

        alertElement.textContent = message;
        alertElement.classList.add('is-visible');
    }

    function buildQueryString(nextState) {
        const params = new URLSearchParams();

        if (nextState.selectedFile) {
            params.set('file', nextState.selectedFile);
        }
        if (nextState.query) {
            params.set('q', nextState.query);
        }
        if (nextState.severity) {
            params.set('severity', nextState.severity);
        }
        if (nextState.term) {
            params.set('term', nextState.term);
        }
        if (nextState.openPicker) {
            params.set('openPicker', '1');
        }

        return params.toString();
    }

    function replaceFragments(payload) {
        toolbarContainer.innerHTML = payload.toolbarHtml;
        viewerContainer.innerHTML = payload.viewerHtml;
        modalContent.innerHTML = payload.modalHtml;
        Object.assign(state, payload.state);
        const queryString = buildQueryString(state);
        window.history.replaceState({}, '', queryString ? `${basePageUrl}?${queryString}` : basePageUrl);
    }

    async function refreshFragments(overrides = {}, options = {}) {
        const requestId = ++requestCounter;
        const nextState = {
            selectedFile: overrides.selectedFile ?? state.selectedFile ?? '',
            query: overrides.query ?? state.query ?? '',
            severity: overrides.severity ?? state.severity ?? '',
            term: overrides.term ?? state.term ?? '',
            openPicker: options.openPicker ?? false,
        };
        const queryString = buildQueryString(nextState);

        toggleLoading(true);
        setAlert('');

        try {
            const response = await fetch(queryString ? `${fragmentsUrl}?${queryString}` : fragmentsUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'De diagnostics-fragmenten konden niet worden geladen.');
            }

            if (requestId !== requestCounter) {
                return;
            }

            replaceFragments(payload);

            if (options.keepModalOpen) {
                modal.show();
            }

            if (options.closeModal) {
                modal.hide();
            }
        } catch (error) {
            if (requestId !== requestCounter) {
                return;
            }

            setAlert(error instanceof Error ? error.message : 'Er ging iets mis bij het verversen van de diagnostics-view.');
        } finally {
            if (requestId === requestCounter) {
                toggleLoading(false);
            }
        }
    }

    modalElement.addEventListener('show.bs.modal', function () {
        refreshFragments({}, { keepModalOpen: true });
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.id !== 'logModalFilterForm') {
            return;
        }

        event.preventDefault();
        const formData = new FormData(form);
        refreshFragments({
            query: String(formData.get('q') || ''),
            selectedFile: String(formData.get('file') || state.selectedFile || ''),
            severity: String(formData.get('severity') || state.severity || ''),
            term: String(formData.get('term') || state.term || ''),
        }, { keepModalOpen: true });
    });

    document.addEventListener('input', function (event) {
        const target = event.target;

        if (!(target instanceof HTMLInputElement) || target.name !== 'q' || target.closest('#logModalFilterForm') === null) {
            return;
        }

        window.clearTimeout(searchDebounceHandle);
        searchDebounceHandle = window.setTimeout(function () {
            refreshFragments({
                query: target.value.trim(),
                selectedFile: state.selectedFile,
                severity: state.severity,
                term: state.term,
            }, { keepModalOpen: true });
        }, 220);
    });

    document.addEventListener('click', function (event) {
        const loadButton = event.target instanceof Element ? event.target.closest('.js-load-log') : null;

        if (!(loadButton instanceof HTMLButtonElement)) {
            return;
        }

        event.preventDefault();
        refreshFragments({ selectedFile: loadButton.dataset.fileName || '' }, { closeModal: true });
    });

    if (state.openPicker) {
        modal.show();
    }
});
</script>
<?= $this->endSection() ?>