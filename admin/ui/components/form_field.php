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
               <?= $reqAttr ?>>
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
