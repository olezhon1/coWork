<?php
// ui/components/form_field.php

function renderFormField(string $fieldName, array $meta, mixed $currentValue): void
{
    /** @var FormFieldType $type */
    $type  = $meta['type'];
    $label = $meta['label'];
    $req   = $meta['req'] ?? false;
    $hint  = $meta['hint'] ?? null;

    $inputType = $type->isInput() ? $type->value : 'text';
    $val       = h((string) $currentValue);
    $reqAttr   = $req ? ' required' : '';
    $reqMark   = $req ? '<span class="req">*</span>' : '';
    $readonly  = !empty($meta['readonly']);
    $roAttr    = $readonly ? ' readonly' : '';
    // Для number-полів за замовчуванням дозволяємо десяткові значення (step="any"),
    // інакше HTML5 блокує float (наприклад, 48.46132 у широті).
    $stepAttr  = '';
    if ($type === FormFieldType::Number) {
        $step = $meta['step'] ?? 'any';
        $stepAttr = ' step="' . h((string) $step) . '"';
    }

    // Каскадний селект коворкінг → робоче місце має власну розмітку (два form-group)
    if ($type === FormFieldType::SelectWorkspaceCascade) {
        renderWorkspaceCascadeField($fieldName, $meta, (string) $currentValue, $reqAttr, $reqMark);
        return;
    }
    ?>
    <div class="form-group <?= isset($meta['span']) && $meta['span'] === 'full' ? 'form-full' : '' ?>">
      <label class="form-label" for="field-<?= h($fieldName) ?>">
        <?= h($label) ?> <?= $reqMark ?>
      </label>

      <?php if ($type === FormFieldType::Textarea): ?>
        <textarea id="field-<?= h($fieldName) ?>" name="<?= h($fieldName) ?>"<?= $reqAttr ?>><?= $val ?></textarea>

      <?php elseif ($type === FormFieldType::Select): ?>
        <select id="field-<?= h($fieldName) ?>" name="<?= h($fieldName) ?>"<?= $reqAttr ?>>
          <option value="">— оберіть —</option>
          <?php foreach ($meta['options'] as $optVal => $optLabel): ?>
            <option value="<?= h((string) $optVal) ?>"
              <?= (string) $currentValue === (string) $optVal ? 'selected' : '' ?>>
              <?= h($optLabel) ?>
            </option>
          <?php endforeach ?>
        </select>

      <?php elseif ($type->isRelationalSelect()): ?>
        <?php $opts = loadSelectOptions($type); ?>
        <select id="field-<?= h($fieldName) ?>" name="<?= h($fieldName) ?>"<?= $reqAttr ?>>
          <option value="">— оберіть —</option>
          <?php foreach ($opts as $optId => $optLabel): ?>
            <option value="<?= h((string) $optId) ?>"
              <?= (string) $currentValue === (string) $optId ? 'selected' : '' ?>>
              <?= h($optLabel) ?>
            </option>
          <?php endforeach ?>
        </select>

      <?php elseif ($type === FormFieldType::Password): ?>
        <input type="password"
               id="field-<?= h($fieldName) ?>"
               name="<?= h($fieldName) ?>"
               autocomplete="new-password"
               <?= $reqAttr ?>>

      <?php else: ?>
        <input type="<?= h($inputType) ?>"
               id="field-<?= h($fieldName) ?>"
               name="<?= h($fieldName) ?>"
               value="<?= $val ?>"
               <?= $stepAttr ?><?= $roAttr ?><?= $reqAttr ?>>
      <?php endif ?>

      <?php if ($hint): ?>
        <div class="form-hint">
          <?= icon('info', 'width:12px;height:12px;opacity:.6') ?>
          <?= h($hint) ?>
        </div>
      <?php endif ?>
    </div>
    <?php
}

/**
 * Рендер каскадного селекту «Коворкінг → Робоче місце».
 * Два окремі form-group: перший (коворкінг) не надсилається, лише фільтрує другий.
 * JS cascade-handler у /admin/assets/js/admin.js слухає .js-cascade-parent
 * і ховає опції в .js-cascade-child, що не мають відповідного data-cw.
 */
function renderWorkspaceCascadeField(string $fieldName, array $meta, string $currentValue, string $reqAttr, string $reqMark): void
{
    $data      = loadSelectOptions(FormFieldType::SelectWorkspaceCascade);
    $cws       = $data['coworkings'];
    $wss       = $data['workspaces'];
    $spanClass = (isset($meta['span']) && $meta['span'] === 'full') ? 'form-full' : '';

    // Коворкінг поточного workspace — щоб автовиставити фільтр у режимі редагування
    $currentCw = '';
    foreach ($wss as $ws) {
        if ((string) $ws['id'] === $currentValue) {
            $currentCw = (string) $ws['coworking_id'];
            break;
        }
    }

    $cwFieldId = 'field-' . $fieldName . '-coworking';
    $wsFieldId = 'field-' . $fieldName;
    ?>
    <div class="form-group <?= $spanClass ?>">
      <label class="form-label" for="<?= h($cwFieldId) ?>">
        Коворкінг <?= $reqMark ?>
      </label>
      <select id="<?= h($cwFieldId) ?>"
              class="js-cascade-parent"
              data-child="<?= h($wsFieldId) ?>">
        <option value="">— спочатку оберіть коворкінг —</option>
        <?php foreach ($cws as $cwId => $cwName): ?>
          <option value="<?= h((string) $cwId) ?>"
                  <?= $currentCw === (string) $cwId ? 'selected' : '' ?>>
            <?= h($cwName) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="form-group <?= $spanClass ?>">
      <label class="form-label" for="<?= h($wsFieldId) ?>">
        <?= h($meta['label']) ?> <?= $reqMark ?>
      </label>
      <select id="<?= h($wsFieldId) ?>"
              name="<?= h($fieldName) ?>"
              class="js-cascade-child"<?= $reqAttr ?>>
        <option value="">— оберіть місце —</option>
        <?php foreach ($wss as $ws): ?>
          <option value="<?= h((string) $ws['id']) ?>"
                  data-cw="<?= h((string) $ws['coworking_id']) ?>"
                  <?= $currentValue === (string) $ws['id'] ? 'selected' : '' ?>>
            <?= h($ws['name'] . ' / ' . $ws['coworking_name']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <?php if (!empty($meta['hint'])): ?>
        <div class="form-hint">
          <?= icon('info', 'width:12px;height:12px;opacity:.6') ?>
          <?= h($meta['hint']) ?>
        </div>
      <?php endif ?>
    </div>
    <?php
}
