<?php
/**
 * @var array $cw
 * @var array $gallery
 * @var array $hours
 * @var array $features
 * @var array $workspaces
 * @var array $reviews
 * @var bool $canReview
 * @var bool $alreadyReviewed
 */
$daysLabels = [1=>'Понеділок',2=>'Вівторок',3=>'Середа',4=>'Четвер',5=>"П'ятниця",6=>'Субота',7=>'Неділя'];
$hoursByDay = [];
foreach ($hours as $h) { $hoursByDay[(int) $h['day_of_week']] = $h; }
$openNow = isOpenNow($hours, !empty($cw['is_24_7']));
?>
<section class="container section cw-hero">
    <div class="cw-hero__header">
        <div>
            <h1 class="cw-hero__title"><?= e($cw['name']) ?></h1>
            <div class="cw-hero__meta">
                <span>📍 <?= e($cw['city'] . ', ' . $cw['address']) ?></span>
                <?= View::component('rating', ['value' => $cw['avg_rating'], 'count' => $cw['reviews_count']]) ?>
                <span class="badge <?= $openNow ? 'badge--open' : 'badge--closed' ?>"><?= $openNow ? 'Відкрито зараз' : 'Зачинено' ?></span>
                <?php if (!empty($cw['is_24_7'])): ?><span class="badge badge--24">24/7</span><?php endif; ?>
            </div>
        </div>
        <div>
            <?php if (!empty($cw['price_from'])): ?>
                <div class="cw-hero__price">від <?= formatPrice((float) $cw['price_from']) ?>/год</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($gallery)): ?>
        <div class="gallery" data-gallery>
            <?php foreach ($gallery as $i => $g): ?>
                <img src="<?= e($g['image_url']) ?>" alt="" class="gallery__img <?= $i === 0 ? 'gallery__img--active' : '' ?>">
            <?php endforeach; ?>
            <?php if (count($gallery) > 1): ?>
                <button type="button" class="gallery__nav gallery__nav--prev" data-dir="-1">‹</button>
                <button type="button" class="gallery__nav gallery__nav--next" data-dir="1">›</button>
                <div class="gallery__dots">
                    <?php foreach ($gallery as $i => $_): ?>
                        <span class="gallery__dot <?= $i === 0 ? 'gallery__dot--active' : '' ?>" data-idx="<?= $i ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<section class="container section cw-body">
    <div class="cw-body__main">
        <?php if (!empty($cw['description'])): ?>
            <h2>Про коворкінг</h2>
            <p><?= nl2br(e($cw['description'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($features)): ?>
            <h2>Зручності</h2>
            <div class="features-grid">
                <?php foreach ($features as $f): ?>
                    <div class="feat-chip feat-chip--lg">
                        <span>✓</span> <?= e($f['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 id="workspaces">Робочі місця</h2>
        <?php if (empty($workspaces)): ?>
            <p class="muted">У цьому коворкінгу ще не додано робочих місць.</p>
        <?php else: ?>
            <div class="grid grid--ws">
                <?php foreach ($workspaces as $w): View::component('workspace_card', ['w' => $w]); endforeach; ?>
            </div>
        <?php endif; ?>

        <h2>Відгуки</h2>
        <?php if (empty($reviews)): ?>
            <p class="muted">Відгуків поки немає. Станьте першим!</p>
        <?php else: ?>
            <div class="reviews">
                <?php foreach ($reviews as $r): ?>
                    <div class="review">
                        <div class="review__head">
                            <strong><?= e($r['user_name'] ?? 'Користувач') ?></strong>
                            <span class="review__rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= (int) $r['rating'] ? 'star--on' : '' ?>">★</span>
                                <?php endfor; ?>
                            </span>
                            <span class="muted small"><?= formatDate($r['created_at'] ?? null) ?></span>
                        </div>
                        <p><?= nl2br(e($r['comment'] ?? '')) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($canReview): ?>
            <form class="review-form" method="post" action="<?= siteUrl('review') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="coworking_id" value="<?= (int) $cw['id'] ?>">
                <h3>Залишити відгук</h3>
                <div class="field">
                    <label>Оцінка</label>
                    <select name="rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>"><?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?> (<?= $i ?>)</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Коментар</label>
                    <textarea name="comment" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn--primary">Надіслати</button>
            </form>
        <?php elseif ($alreadyReviewed): ?>
            <p class="muted small">Ви вже залишали відгук для цього коворкінгу.</p>
        <?php elseif (Auth::check()): ?>
            <p class="muted small">Залишати відгук можуть лише користувачі, які мали тут бронювання.</p>
        <?php endif; ?>
    </div>

    <aside class="cw-body__side">
        <div class="side-card">
            <h3>Графік роботи</h3>
            <?php if (!empty($cw['is_24_7'])): ?>
                <p class="badge badge--24">Відкрито 24/7</p>
            <?php else: ?>
                <table class="hours-table">
                    <?php foreach ($daysLabels as $num => $name):
                        $h = $hoursByDay[$num] ?? null; ?>
                        <tr>
                            <td><?= e($name) ?></td>
                            <td>
                                <?php if (!$h): ?><span class="muted">—</span>
                                <?php elseif (!empty($h['is_closed'])): ?><span class="muted">Зачинено</span>
                                <?php else: ?>
                                    <?= e(substr((string) $h['open_time'], 0, 5)) ?>—<?= e(substr((string) $h['close_time'], 0, 5)) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($cw['latitude']) && !empty($cw['longitude'])): ?>
        <div class="side-card">
            <h3>Розташування</h3>
            <div id="cw-map" style="height: 260px; border-radius: 12px;"
                 data-lat="<?= e((string) $cw['latitude']) ?>"
                 data-lng="<?= e((string) $cw['longitude']) ?>"
                 data-name="<?= e($cw['name']) ?>"></div>
        </div>
        <?php endif; ?>
    </aside>
</section>
