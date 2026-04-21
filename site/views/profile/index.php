<?php
/**
 * @var array $user
 * @var array $bookings
 * @var array $subscriptions
 */
?>
<section class="container section">
    <h1>Мій профіль</h1>
    <div class="profile-head">
        <div>
            <div class="profile-head__name"><?= e($user['full_name']) ?></div>
            <div class="muted"><?= e($user['email']) ?> • <?= e($user['phone'] ?? '') ?></div>
        </div>
    </div>

    <h2>Мої бронювання</h2>
    <?php if (empty($bookings)): ?>
        <p class="muted">Ви ще не бронювали жодного робочого місця.
            <a href="<?= siteUrl('coworkings') ?>">Перейти до каталогу →</a></p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Коворкінг / місце</th>
                        <th>Час</th>
                        <th>Сума</th>
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td>#<?= (int) $b['id'] ?></td>
                        <td>
                            <a href="<?= siteUrl('coworking', ['id' => $b['coworking_id']]) ?>"><?= e($b['coworking_name']) ?></a>
                            <div class="muted small"><?= e($b['workspace_name']) ?> · <?= e(workspaceTypeLabel((string) $b['workspace_type'])) ?></div>
                        </td>
                        <td>
                            <?= formatDateTime($b['first_slot_start'] ?? null) ?><br>
                            <span class="muted small">до <?= formatDateTime($b['last_slot_end'] ?? null) ?></span>
                        </td>
                        <td><?= formatPrice((float) $b['total_price']) ?></td>
                        <td><span class="badge <?= bookingStatusBadge((string) $b['status']) ?>"><?= e(bookingStatusLabel((string) $b['status'])) ?></span></td>
                        <td>
                            <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
                                <form method="post" action="<?= siteUrl('cancel_booking') ?>" onsubmit="return confirm('Скасувати бронювання?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                                    <button class="btn btn--link">Скасувати</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Мої абонементи</h2>
    <?php if (empty($subscriptions)): ?>
        <p class="muted">У вас немає активних абонементів.
            <a href="<?= siteUrl('subscriptions') ?>">Подивитись доступні плани →</a></p>
    <?php else: ?>
        <div class="grid grid--subs">
            <?php foreach ($subscriptions as $s): ?>
                <div class="sub-card">
                    <div class="sub-card__name"><?= e($s['plan_name'] ?? 'Абонемент') ?></div>
                    <?php if (!empty($s['coworking_name'])): ?>
                        <div class="muted small">Коворкінг: <?= e($s['coworking_name']) ?></div>
                    <?php else: ?>
                        <div class="muted small">Діє у всіх локаціях</div>
                    <?php endif; ?>
                    <div class="sub-card__hours"><?= (int) $s['hours_left'] ?> год залишилось</div>
                    <div class="muted small">До <?= formatDate($s['end_date'] ?? null) ?></div>
                    <span class="badge <?= $s['status'] === 'active' ? 'b-green' : 'b-gray' ?>"><?= e($s['status']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
