<?php
/**
 * @var array $coworkings
 * @var array $workspaceTypes
 * @var array $plans
 * @var array $mapPoints
 * @var array $topFeatures
 * @var ?string $city
 */
?>
<section class="hero">
    <div class="container hero__inner">
        <h1 class="hero__title">Твій ідеальний офіс на годину чи день</h1>
        <p class="hero__subtitle">Знайди коворкінг для себе або команди. Open Space, переговорки, приватні кабінети, тихі зони — в одному місці.</p>

        <form class="hero__search" action="<?= siteUrl('coworkings') ?>" method="get">
            <input type="hidden" name="page" value="coworkings">
            <div class="hero__search-field">
                <label>Місто</label>
                <input type="text" name="city" value="<?= e($city ?? '') ?>" placeholder="Наприклад, Київ" list="cities-list">
                <datalist id="cities-list">
                    <?php foreach ((new CoworkingModel())->distinctCities() as $c): ?>
                        <option value="<?= e($c) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="hero__search-field">
                <label>Дата</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="hero__search-field">
                <label>Пошук</label>
                <input type="text" name="q" placeholder="Назва, район…">
            </div>
            <button type="submit" class="btn btn--primary btn--lg">Знайти</button>
        </form>

        <div class="hero__chips">
            <a class="chip" href="<?= siteUrl('coworkings', ['is_24_7' => '1']) ?>">⏱ Відкрито 24/7</a>
            <a class="chip" href="<?= siteUrl('coworkings', ['type' => 'silent']) ?>">🤫 Тихі зони</a>
            <a class="chip" href="<?= siteUrl('coworkings', ['type' => 'conference']) ?>">🤝 Переговорки</a>
            <a class="chip" href="<?= siteUrl('coworkings', ['type' => 'event']) ?>">🎤 Івент-простір</a>
            <?php foreach (array_slice($topFeatures, 0, 3) as $f): ?>
                <a class="chip" href="<?= siteUrl('coworkings', ['features' => [$f['id']]]) ?>">✓ <?= e($f['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container section">
    <div class="section__head">
        <h2>Популярні локації<?= $city ? ' у ' . e($city) : '' ?></h2>
        <a href="<?= siteUrl('coworkings') ?>" class="btn btn--link">Дивитись усі →</a>
    </div>
    <?php if (empty($coworkings)): ?>
        <p class="muted">Поки немає коворкінгів для відображення.</p>
    <?php else: ?>
        <div class="grid grid--cards">
            <?php foreach ($coworkings as $c): View::component('coworking_card', ['c' => $c]); endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="container section">
    <div class="section__head">
        <h2>Вибір типу робочого місця</h2>
        <span class="muted">Оберіть формат під свої задачі</span>
    </div>
    <div class="grid grid--ws-types">
        <?php foreach ($workspaceTypes as $wt): ?>
            <a class="ws-type-card" href="<?= siteUrl('coworkings', ['type' => $wt['key']]) ?>">
                <div class="ws-type-card__emoji">
                    <?= match ($wt['key']) {
                        'open'       => '🏢',
                        'conference' => '🤝',
                        'cabinet'    => '🚪',
                        'silent'     => '🤫',
                        default      => '💼',
                    } ?>
                </div>
                <div class="ws-type-card__name"><?= e($wt['label']) ?></div>
                <div class="ws-type-card__tag"><?= e($wt['tagline']) ?></div>
                <div class="ws-type-card__foot">
                    <?php if ($wt['min_price'] !== null): ?>
                        <span>від <?= formatPrice((float) $wt['min_price']) ?>/год</span>
                    <?php else: ?>
                        <span class="muted">немає у вашому місті</span>
                    <?php endif; ?>
                    <span class="muted small"><?= (int) $wt['count'] ?> місць</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--plans">
    <div class="container">
        <div class="section__head section__head--light">
            <h2>Абонементи — економ до 30%</h2>
            <span>Купуй пакет годин і використовуй у будь-який зручний час</span>
        </div>
        <?php if (empty($plans)): ?>
            <p class="muted">Планів поки немає.</p>
        <?php else: ?>
            <div class="grid grid--plans">
                <?php foreach ($plans as $p):
                    $perHour = $p['hours_included'] > 0 ? (float) $p['price'] / (int) $p['hours_included'] : 0;
                ?>
                    <div class="plan-card">
                        <div class="plan-card__name"><?= e($p['name']) ?></div>
                        <div class="plan-card__price"><?= formatPrice((float) $p['price']) ?></div>
                        <div class="plan-card__hours"><?= (int) $p['hours_included'] ?> год • <?= (int) $p['duration_days'] ?> днів</div>
                        <?php if ($p['description']): ?>
                            <p class="plan-card__desc"><?= e($p['description']) ?></p>
                        <?php endif; ?>
                        <div class="plan-card__per-hour muted small">
                            <?php if ($perHour > 0): ?>~ <?= formatPrice($perHour) ?>/год<?php endif; ?>
                        </div>
                        <form method="post" action="<?= siteUrl('subscriptions') ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn--primary btn--block">Придбати</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="container section">
    <div class="section__head">
        <h2>Мапа коворкінгів<?= $city ? ' у ' . e($city) : '' ?></h2>
        <span class="muted">Знайдіть місце поблизу</span>
    </div>
    <?php if (empty($mapPoints)): ?>
        <p class="muted">Для коворкінгів ще не заведено координат. Додайте latitude/longitude у панелі адміністратора, щоб увімкнути мапу.</p>
    <?php else: ?>
        <div id="coworkings-map"
             data-points='<?= e(json_encode($mapPoints, JSON_UNESCAPED_UNICODE)) ?>'
             style="height: 460px; border-radius: 16px; overflow: hidden;"></div>
    <?php endif; ?>
</section>

<section class="container section">
    <div class="section__head">
        <h2>Чому coWork?</h2>
    </div>
    <div class="grid grid--features">
        <div class="feat-box"><div class="feat-box__ico">⚡</div><div class="feat-box__title">Швидке бронювання</div><p>Декілька кліків — і місце за тобою.</p></div>
        <div class="feat-box"><div class="feat-box__ico">💳</div><div class="feat-box__title">Гнучкі тарифи</div><p>Оплата по годинах або абонемент на місяць.</p></div>
        <div class="feat-box"><div class="feat-box__ico">📍</div><div class="feat-box__title">По всій Україні</div><p>Локації в різних містах і районах.</p></div>
        <div class="feat-box"><div class="feat-box__ico">🔒</div><div class="feat-box__title">Безпека та довіра</div><p>Реальні відгуки від відвідувачів.</p></div>
    </div>
</section>
