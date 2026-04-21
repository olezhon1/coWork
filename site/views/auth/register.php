<?php /** @var array $form @var array $errors */ ?>
<section class="container section auth-section">
    <div class="auth-box">
        <h1>Реєстрація</h1>
        <form method="post" action="<?= siteUrl('register') ?>">
            <?= csrfField() ?>
            <div class="field">
                <label>Повне ім'я</label>
                <input type="text" name="full_name" required value="<?= e($form['full_name']) ?>">
                <?php if (!empty($errors['full_name'])): ?><div class="field__err"><?= e($errors['full_name']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($form['email']) ?>">
                <?php if (!empty($errors['email'])): ?><div class="field__err"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label>Телефон</label>
                <input type="tel" name="phone" required value="<?= e($form['phone']) ?>" placeholder="+380501234567">
                <?php if (!empty($errors['phone'])): ?><div class="field__err"><?= e($errors['phone']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label>Пароль</label>
                <input type="password" name="password" required minlength="6">
                <?php if (!empty($errors['password'])): ?><div class="field__err"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label>Підтвердіть пароль</label>
                <input type="password" name="password_confirm" required minlength="6">
                <?php if (!empty($errors['password_confirm'])): ?><div class="field__err"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn--primary btn--block">Створити акаунт</button>
        </form>
        <p class="auth-box__foot">Вже зареєстровані? <a href="<?= siteUrl('login') ?>">Увійти</a></p>
    </div>
</section>
