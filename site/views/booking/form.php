<?php
/**
 * @var array $workspace
 * @var array $cw
 * @var array $hours
 * @var array $booked
 * @var array $errors
 * @var array $values
 */
?>
<section class="container section">
    <div class="booking">
        <h1>Бронювання — <?= e($workspace['name']) ?></h1>
        <div class="muted"><?= e($cw['name'] . ', ' . $cw['address']) ?></div>
        <div class="booking__meta">
            <span><?= e(workspaceTypeLabel((string) $workspace['type_key'])) ?></span>
            <span>👥 до <?= (int) $workspace['capacity'] ?></span>
            <span><strong><?= formatPrice((float) $workspace['price_per_hour']) ?>/год</strong></span>
        </div>

        <?php if (!empty($booked)): ?>
            <details class="booking__booked">
                <summary>Заброньовані інтервали на найближчий тиждень (<?= count($booked) ?>)</summary>
                <ul>
                    <?php foreach ($booked as $b): ?>
                        <li><?= formatDateTime($b['start_time']) ?> — <?= formatDateTime($b['end_time']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>

        <form method="post" action="<?= siteUrl('book') ?>" class="booking__form">
            <?= csrfField() ?>
            <input type="hidden" name="workspace_id" value="<?= (int) $workspace['id'] ?>">

            <?php if (!empty($errors['time'])): ?>
                <div class="flash flash--err"><?= e($errors['time']) ?></div>
            <?php endif; ?>

            <div class="field">
                <label>Початок</label>
                <input type="datetime-local" name="start_time" required
                       value="<?= e($values['start_time']) ?>"
                       min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="field">
                <label>Кінець</label>
                <input type="datetime-local" name="end_time" required
                       value="<?= e($values['end_time']) ?>">
            </div>
            <div class="booking__calc" id="booking-calc"
                 data-price="<?= (float) $workspace['price_per_hour'] ?>">
                Виберіть час для розрахунку вартості.
            </div>
            <button type="submit" class="btn btn--primary btn--lg">Забронювати</button>
        </form>
    </div>
</section>
