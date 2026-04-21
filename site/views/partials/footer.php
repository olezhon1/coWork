<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__col">
            <div class="brand">
                <span class="brand__icon">◉</span>
                <span class="brand__name">coWork</span>
            </div>
            <p class="muted">Демонстрація роботи з базою даних на прикладі сайту з бронюванням коворкінгів.</p>
        </div>
        <div class="site-footer__col">
            <h4>Навігація</h4>
            <a href="<?= siteUrl('home') ?>">Головна</a>
            <a href="<?= siteUrl('coworkings') ?>">Каталог</a>
            <a href="<?= siteUrl('subscriptions') ?>">Абонементи</a>
            <a href="<?= siteUrl('profile') ?>">Профіль</a>
        </div>
        <div class="site-footer__col">
            <h4>Типи місць</h4>
            <a href="<?= siteUrl('coworkings', ['type' => 'open']) ?>">Open Space</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'conference']) ?>">Переговорки</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'cabinet']) ?>">Приватні офіси</a>
            <a href="<?= siteUrl('coworkings', ['type' => 'silent']) ?>">Тихі зони</a>
        </div>
        <div class="site-footer__col">
            <h4>Контакти</h4>
            <p class="muted">© <?= date('Y') ?> coWork.<br>Освітній демо-проєкт.</p>
            <a href="/admin/" class="muted small">Панель адміністратора →</a>
        </div>
    </div>
</footer>
