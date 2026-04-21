<?php /** @var string $return @var string $email @var array $errors */ ?>
<section class="container section auth-section">
    <div class="auth-box">
        <h1>Вхід</h1>
        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash--err"><?= e($errors['general']) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= siteUrl('login') ?>">
            <?= csrfField() ?>
            <?php if ($return): ?><input type="hidden" name="return" value="<?= e($return) ?>"><?php endif; ?>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($email) ?>">
                <?php if (!empty($errors['email'])): ?><div class="field__err"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="field">
                <label>Пароль</label>
                <input type="password" name="password" required>
                <?php if (!empty($errors['password'])): ?><div class="field__err"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn--primary btn--block">Увійти</button>
        </form>
        <p class="auth-box__foot">Немає акаунту? <a href="<?= siteUrl('register') ?>">Зареєструватись</a></p>
    </div>
</section>
