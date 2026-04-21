<?php
// ui/views/view_form.php
// ─────────────────────────────────────────────────────────────────────────────
// Очікує:
//   $table      AdminTable
//   $action     string  ('add' | 'edit')
//   $editRow    array|null
//   $id         int
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../components/form_field.php';

$cfg    = tableFormConfig($table);
$isEdit = $action === 'edit' && $editRow !== null;
$title  = $isEdit
    ? 'Редагувати — ' . $table->label()
    : 'Додати — ' . $table->label();

$postUrl = '/admin/?t=' . h($table->value) . '&a=' . h($action)
    . ($isEdit ? '&id=' . $id : '');
?>

<div class="page-header">
  <div>
    <div class="page-title"><?= h($title) ?></div>
    <?php if ($isEdit): ?>
      <div class="page-sub">ID: <?= h((string) $id) ?></div>
    <?php endif ?>
  </div>
  <div class="page-actions">
    <a href="/admin/?t=<?= h($table->value) ?>" class="btn">
      <?= icon('back') ?> Назад до списку
    </a>
  </div>
</div>

<div class="card" style="max-width:680px;">
  <form method="post" action="<?= $postUrl ?>">
    <div class="form-row">
      <?php foreach ($cfg as $fieldName => $meta):
          $currentValue = $isEdit ? ($editRow[$fieldName] ?? '') : ($_POST[$fieldName] ?? '');
          renderFormField($fieldName, $meta, $currentValue);
      endforeach ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-accent">
        <?= icon($isEdit ? 'save' : 'add') ?>
        <?= $isEdit ? 'Зберегти зміни' : 'Додати запис' ?>
      </button>
      <a href="/admin/?t=<?= h($table->value) ?>" class="btn btn-ghost">
        <?= icon('cancel') ?> Скасувати
      </a>
    </div>
  </form>
</div>
