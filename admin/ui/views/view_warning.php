<?php
// ui/views/view_warning.php
// ─────────────────────────────────────────────────────────────────────────────
// Виводиться коли referenced entity не існує або часовий діапазон невірний.
//
// Очікує змінні:
//   $warnReason    WarnReason          — причина (визначає заголовок і текст)
//   $warnBackUrl   string              — куди повернутись
//   $warnBackLabel string              — текст кнопки "Назад"
//   $warnDetails   array<string>       — (optional) додаткові деталі
//   $warnAction    array|null          — (optional) ['url'=>..., 'label'=>...] кнопка дії
// ─────────────────────────────────────────────────────────────────────────────

$warnBackLabel = $warnBackLabel ?? 'Назад до списку';
$warnDetails   = $warnDetails   ?? [];
$warnAction    = $warnAction    ?? null;
?>

<div style="max-width: 600px; margin: 2rem auto;">
  <div class="card warn-card">

    <!-- Іконка + заголовок -->
    <div style="display:flex; align-items:flex-start; gap:1rem; margin-bottom:1.25rem;">
      <div class="warn-icon-wrap">
        <?= icon('warning', 'width:22px;height:22px') ?>
      </div>
      <div style="flex:1;">
        <div style="font-size:var(--fs-lg); font-weight:500; color:var(--text); margin-bottom:.35rem;">
          <?= h($warnReason->title()) ?>
        </div>
        <div style="font-size:var(--fs-sm); color:var(--text2); line-height:1.6;">
          <?= h($warnReason->message()) ?>
        </div>
      </div>
    </div>

    <!-- Деталі (необов'язково) -->
    <?php if ($warnDetails): ?>
      <div class="warn-details">
        <div class="warn-details-lbl">Деталі</div>
        <?php foreach ($warnDetails as $detail): ?>
          <div class="warn-detail-item">
            <span class="warn-dot"></span>
            <?= h($detail) ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <!-- Дії -->
    <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border);">
      <a href="<?= h($warnBackUrl) ?>" class="btn">
        <?= icon('back') ?> <?= h($warnBackLabel) ?>
      </a>
      <?php if ($warnAction): ?>
        <a href="<?= h($warnAction['url']) ?>" class="btn btn-accent">
          <?= icon('add') ?> <?= h($warnAction['label']) ?>
        </a>
      <?php endif ?>
    </div>

  </div>
</div>
