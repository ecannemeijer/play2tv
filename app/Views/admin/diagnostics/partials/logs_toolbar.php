<div class="log-primary-filters">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logFilesModal">
            <i class="bi bi-folder2-open me-1"></i>Laad logbestand
        </button>
        <span class="badge bg-secondary"><?= number_format(count($logFiles)) ?> logs</span>
    </div>
</div>