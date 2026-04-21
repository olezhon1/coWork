<?php /** @var array $w */ ?>
<div class="ws-card">
    <div class="ws-card__head">
        <div>
            <div class="ws-card__title"><?= e($w['name']) ?></div>
            <div class="ws-card__type"><?= e(workspaceTypeLabel((string) $w['type_key'])) ?></div>
        </div>
        <div class="ws-card__price"><?= formatPrice((float) $w['price_per_hour']) ?>/год</div>
    </div>
    <div class="ws-card__meta">
        <span>👥 до <?= (int) $w['capacity'] ?> осіб</span>
    </div>
    <?php if (!empty($w['description'])): ?>
        <div class="ws-card__desc"><?= e($w['description']) ?></div>
    <?php endif; ?>
    <div class="ws-card__foot">
        <a class="btn btn--primary" href="<?= siteUrl('book', ['workspace_id' => $w['id']]) ?>">Забронювати</a>
    </div>
</div>
