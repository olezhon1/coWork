<?php /** @var array $plans */ ?>
<section class="container section">
    <div class="section__head">
        <h1>Абонементи</h1>
        <span class="muted">Купуй пакет годин і економ до 30%</span>
    </div>
    <?php if (empty($plans)): ?>
        <p class="muted">Планів поки немає.</p>
    <?php else: ?>
        <div class="grid grid--plans">
            <?php foreach ($plans as $p):
                $perHour = $p['hours_included'] > 0 ? (float) $p['price'] / (int) $p['hours_included'] : 0;
            ?>
                <div class="plan-card plan-card--lg">
                    <div class="plan-card__name"><?= e($p['name']) ?></div>
                    <div class="plan-card__price"><?= formatPrice((float) $p['price']) ?></div>
                    <div class="plan-card__hours"><?= (int) $p['hours_included'] ?> год / <?= (int) $p['duration_days'] ?> днів</div>
                    <?php if ($p['description']): ?>
                        <p class="plan-card__desc"><?= e($p['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($p['coworking_name']): ?>
                        <div class="muted small">Діє у коворкінгу: <?= e($p['coworking_name']) ?></div>
                    <?php else: ?>
                        <div class="muted small">Діє у будь-якому коворкінгу</div>
                    <?php endif; ?>
                    <?php if ($perHour > 0): ?>
                        <div class="plan-card__per-hour muted small">Вартість 1 години: <?= formatPrice($perHour) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= siteUrl('subscriptions') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="btn btn--primary btn--block">Придбати</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
