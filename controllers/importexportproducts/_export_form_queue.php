<?php if (!$this->fatalError): ?>

    <div class="modal-body">
        <p>
            <?= e(trans('initbiz.mallimportexport::lang.ux.export_queue_message')) ?>
        </p>
    </div>
    <div class="modal-footer">
        <button
            type="button"
            class="btn btn-secondary"
            onclick="location.reload();">
            <?= e(trans('initbiz.mallimportexport::lang.ux.export_queue_success_button')) ?>
        </button>
    </div>

<?php else: ?>

    <div class="modal-body">
        <p class="flash-message static error"><?= e($this->fatalError) ?></p>
    </div>
    <div class="modal-footer">
        <button
            type="button"
            class="btn btn-secondary"
            data-dismiss="popup">
            <?= __("Close") ?>
        </button>
    </div>

<?php endif ?>
