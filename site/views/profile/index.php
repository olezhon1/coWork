<?php
/**
 * @var array       $user
 * @var array       $bookings
 * @var array       $infoErrors
 * @var array       $pwErrors
 * @var string|null $form
 */
$infoErrors = $infoErrors ?? [];
$pwErrors   = $pwErrors ?? [];
?>
<section class="container section">
    <h1>Мій профіль</h1>
    <div class="profile-head">
        <div>
            <div class="profile-head__name"><?= e($user['full_name']) ?></div>
            <div class="muted"><?= e($user['email']) ?> • <?= e($user['phone'] ?? '') ?></div>
        </div>
    </div>

    <div class="profile-forms">
        <!-- ── Особисті дані ─────────────────────────────────────────────── -->
        <form method="post" action="<?= siteUrl('profile_update') ?>" class="profile-card">
            <h2>Особисті дані</h2>
            <p class="muted small">Імʼя, email та телефон. Email використовується для входу.</p>
            <?= csrfField() ?>

            <?php if (!empty($infoErrors['general'])): ?>
                <div class="field__err"><?= e($infoErrors['general']) ?></div>
            <?php endif; ?>

            <div class="field">
                <label>Повне імʼя</label>
                <input type="text" name="full_name" required
                       value="<?= e($user['full_name'] ?? '') ?>">
                <?php if (!empty($infoErrors['full_name'])): ?>
                    <div class="field__err"><?= e($infoErrors['full_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required
                       value="<?= e($user['email'] ?? '') ?>">
                <?php if (!empty($infoErrors['email'])): ?>
                    <div class="field__err"><?= e($infoErrors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Телефон</label>
                <input type="tel" name="phone" required
                       placeholder="+380501234567"
                       value="<?= e($user['phone'] ?? '') ?>">
                <?php if (!empty($infoErrors['phone'])): ?>
                    <div class="field__err"><?= e($infoErrors['phone']) ?></div>
                <?php endif; ?>
            </div>

            <button class="btn btn--primary">Зберегти</button>
        </form>

        <!-- ── Зміна пароля ──────────────────────────────────────────────── -->
        <form method="post" action="<?= siteUrl('profile_password') ?>" class="profile-card" autocomplete="off">
            <h2>Зміна пароля</h2>
            <p class="muted small">Для зміни треба ввести поточний пароль.</p>
            <?= csrfField() ?>

            <?php if (!empty($pwErrors['general'])): ?>
                <div class="field__err"><?= e($pwErrors['general']) ?></div>
            <?php endif; ?>

            <div class="field">
                <label>Поточний пароль</label>
                <input type="password" name="old_password" required autocomplete="current-password">
                <?php if (!empty($pwErrors['old_password'])): ?>
                    <div class="field__err"><?= e($pwErrors['old_password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Новий пароль</label>
                <input type="password" name="new_password" required minlength="6" autocomplete="new-password">
                <div class="muted small">Мінімум 6 символів, має відрізнятись від поточного.</div>
                <?php if (!empty($pwErrors['new_password'])): ?>
                    <div class="field__err"><?= e($pwErrors['new_password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label>Повторіть новий пароль</label>
                <input type="password" name="new_password_confirm" required minlength="6" autocomplete="new-password">
                <?php if (!empty($pwErrors['new_password_confirm'])): ?>
                    <div class="field__err"><?= e($pwErrors['new_password_confirm']) ?></div>
                <?php endif; ?>
            </div>

            <button class="btn btn--primary">Змінити пароль</button>
        </form>
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
