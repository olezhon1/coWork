<?php
/**
 * @var array $user
 * @var array $bookings
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
</section>
