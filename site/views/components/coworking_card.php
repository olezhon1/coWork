<?php
/**
 * @var array $c coworking row with: id, name, address, city, main_image, avg_rating, reviews_count, price_from, is_24_7
 * @var array $c['top_features'] (optional)
 * @var array $c['hours'] (optional)
 */
$openNow = !empty($c['is_24_7']);
if (!$openNow && !empty($c['hours'])) {
    $openNow = isOpenNow($c['hours'], (bool) $c['is_24_7']);
}
$img = $c['main_image'] ?? null;
?>
<a class="cw-card" href="<?= siteUrl('coworking', ['id' => $c['id']]) ?>">
    <div class="cw-card__img" <?= $img ? 'style="background-image:url(\'' . e($img) . '\')"' : '' ?>>
        <?php if (empty($img)): ?>
            <span class="cw-card__placeholder">coWork</span>
        <?php endif; ?>
        <div class="cw-card__badges">
            <?php if (!empty($c['is_24_7'])): ?>
                <span class="badge badge--24">24/7</span>
            <?php endif; ?>
            <span class="badge <?= $openNow ? 'badge--open' : 'badge--closed' ?>">
                <?= $openNow ? 'Відкрито' : 'Закрито' ?>
            </span>
        </div>
    </div>
    <div class="cw-card__body">
        <div class="cw-card__title"><?= e($c['name']) ?></div>
        <div class="cw-card__meta">
            <span>📍 <?= e(($c['city'] ?? '') . ', ' . ($c['address'] ?? '')) ?></span>
        </div>
        <div class="cw-card__row">
            <div class="cw-card__rating">
                <?php if (!empty($c['avg_rating'])): ?>
                    ★ <?= number_format((float) $c['avg_rating'], 1) ?>
                    <span class="muted small">(<?= (int) ($c['reviews_count'] ?? 0) ?>)</span>
                <?php else: ?>
                    <span class="muted small">Без оцінок</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($c['price_from'])): ?>
                <div class="cw-card__price">від <?= formatPrice((float) $c['price_from']) ?>/год</div>
            <?php endif; ?>
        </div>
        <?php if (!empty($c['top_features'])): ?>
            <div class="cw-card__features">
                <?php foreach ($c['top_features'] as $f): ?>
                    <span class="feat-chip"><?= e($f['name']) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</a>
