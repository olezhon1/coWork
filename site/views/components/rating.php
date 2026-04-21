<?php /** @var float|null $value */ /** @var int $count */ ?>
<?php if ($value): ?>
    <span class="rating">
        <span class="rating__stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= round($value) ? 'star--on' : '' ?>">★</span>
            <?php endfor; ?>
        </span>
        <span class="rating__value"><?= number_format((float) $value, 1) ?></span>
        <?php if (!empty($count)): ?>
            <span class="muted small">(<?= (int) $count ?> відгуків)</span>
        <?php endif; ?>
    </span>
<?php else: ?>
    <span class="muted small">Немає відгуків</span>
<?php endif; ?>
