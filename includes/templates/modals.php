<?php
/**
 * Reusable modal helpers. Call modal_open()/modal_close() around custom
 * content, or confirm_delete_modal() for the common "are you sure?" case.
 */

function modal_open(string $id, string $title, string $size = ''): void
{
    $sizeClass = $size ? "modal-$size" : '';
    echo <<<HTML
    <div class="modal fade" id="{$id}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog {$sizeClass}">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{$title}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
    HTML;
}

function modal_close(): void
{
    echo <<<HTML
          </div>
        </div>
      </div>
    </div>
    HTML;
}

function confirm_delete_modal(string $id, string $actionUrl, string $itemLabel = 'this item', array $hiddenFields = []): void
{
    $csrf = csrf_field();
    $hidden = '';
    foreach ($hiddenFields as $name => $value) {
        $hidden .= '<input type="hidden" name="' . e((string) $name) . '" value="' . e((string) $value) . '">';
    }

    echo <<<HTML
    <div class="modal fade" id="{$id}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete {$itemLabel}? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <form method="post" action="{$actionUrl}">
              {$csrf}
              <input type="hidden" name="_method" value="DELETE">
              {$hidden}
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    HTML;
}
