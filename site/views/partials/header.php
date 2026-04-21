<?php
$coworkingModel = new CoworkingModel();
$cities = $coworkingModel->distinctCities();
$selectedCity = selectedCity();
$user = Auth::user();
?>
<header class="site-header">
    <div class="site-header__top container">
        <a href="<?= siteUrl('home') ?>" class="brand">
            <span class="brand__icon">◉</span>
            <span class="brand__name">coWork</span>
        </a>

        <form action="<?= siteUrl('set_city') ?>" method="get" class="city-picker">
            <input type="hidden" name="page" value="set_city">
            <label for="city-select" class="city-picker__label">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-7.5-7-12a7 7 0 0 1 14 0c0 4.5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
                <span>Місто:</span>
            </label>
            <select id="city-select" name="city" onchange="this.form.submit()">
                <option value="">— Всі міста —</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?= e($c) ?>" <?= $selectedCity === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="header-auth">
            <?php if ($user): ?>
                <a href="<?= siteUrl('profile') ?>" class="btn btn--ghost">
                    👤 <?= e($user['full_name']) ?>
                </a>
                <a href="<?= siteUrl('logout') ?>" class="btn btn--link">Вихід</a>
            <?php else: ?>
                <a href="<?= siteUrl('login') ?>" class="btn btn--ghost">Увійти</a>
                <a href="<?= siteUrl('register') ?>" class="btn btn--primary">Реєстрація</a>
            <?php endif; ?>
        </div>
    </div>

    <nav class="site-header__nav">
        <div class="container nav-inner">
            <a href="<?= siteUrl('coworkings') ?>" class="nav-link">Всі коворкінги</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'open']) ?>" class="nav-link">Open Space</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'conference']) ?>" class="nav-link">Переговорки</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'cabinet']) ?>" class="nav-link">Приватні офіси</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'silent']) ?>" class="nav-link">Тихі зони</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'event']) ?>" class="nav-link">Івенти</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'photo']) ?>" class="nav-link">Фотостудії</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'rec']) ?>" class="nav-link">Звукозапис</a>
            <a href="<?= siteUrl('coworkings', ['is_24_7' => '1']) ?>" class="nav-link nav-link--accent">24/7</a>
            <span class="nav-spacer"></span>
            <a href="<?= siteUrl('subscriptions') ?>" class="nav-link nav-link--pill">Абонементи</a>
        </div>
    </nav>
</header>
