<?php
/**
 * @var array $coworkings
 * @var int $total
 * @var int $page
 * @var int $totalPages
 * @var array $filters
 * @var string $sort
 * @var array $allFeatures
 * @var array $allCities
 */
?>
<section class="container section">
    <div class="section__head">
        <h2>Каталог коворкінгів</h2>
        <span class="muted">Знайдено <?= (int) $total ?></span>
    </div>

    <form class="filter-bar" method="get" action="<?= siteUrl('coworkings') ?>">
        <input type="hidden" name="page" value="coworkings">
        <div class="filter-bar__row">
            <div class="field">
                <label>Місто</label>
                <select name="city">
                    <option value="">Всі</option>
                    <?php foreach ($allCities as $c): ?>
                        <option value="<?= e($c) ?>" <?= ($filters['city'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Тип місця</label>
                <select name="type">
                    <option value="">Всі</option>
                    <?php foreach (WorkspaceType::options() as $val => $lbl): ?>
                        <option value="<?= e($val) ?>" <?= ($filters['workspace_type_key'] ?? '') === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Графік</label>
                <select name="is_24_7">
                    <option value="">Будь-який</option>
                    <option value="1" <?= ($filters['is_24_7'] ?? '') === '1' || ($filters['is_24_7'] ?? null) === 1 ? 'selected' : '' ?>>Тільки 24/7</option>
                </select>
            </div>
            <div class="field">
                <label>Ціна до, ₴/год</label>
                <input type="number" name="price_max" min="0" step="10" value="<?= !empty($filters['price_max']) ? (float) $filters['price_max'] : '' ?>">
            </div>
            <div class="field field--grow">
                <label>Пошук</label>
                <input type="text" name="q" value="<?= e($filters['search'] ?? '') ?>" placeholder="Назва або адреса">
            </div>
            <div class="field">
                <label>Сортування</label>
                <select name="sort">
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>За рейтингом</option>
                    <option value="price"  <?= $sort === 'price'  ? 'selected' : '' ?>>Від дешевших</option>
                    <option value="new"    <?= $sort === 'new'    ? 'selected' : '' ?>>Нові</option>
                    <option value="name"   <?= $sort === 'name'   ? 'selected' : '' ?>>За назвою</option>
                </select>
            </div>
        </div>
        <?php if (!empty($allFeatures)): ?>
        <div class="filter-bar__features">
            <span class="muted small">Зручності:</span>
            <?php foreach ($allFeatures as $f): $checked = in_array((int) $f['id'], $filters['feature_ids'] ?? [], true); ?>
                <label class="feat-toggle">
                    <input type="checkbox" name="features[]" value="<?= (int) $f['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                    <span><?= e($f['name']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="filter-bar__actions">
            <button type="submit" class="btn btn--primary">Застосувати</button>
            <a href="<?= siteUrl('coworkings') ?>" class="btn btn--link">Скинути</a>
        </div>
    </form>

    <?php if (empty($coworkings)): ?>
        <p class="muted">Нічого не знайдено. Спробуйте змінити фільтри.</p>
    <?php else: ?>
        <div class="grid grid--cards">
            <?php foreach ($coworkings as $c): View::component('coworking_card', ['c' => $c]); endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination">
                <?php
                $baseParams = $_GET; unset($baseParams['p']);
                for ($i = 1; $i <= $totalPages; $i++):
                    $url = siteUrl('coworkings', array_merge($baseParams, ['p' => $i]));
                ?>
                    <a class="page <?= $i === $page ? 'page--active' : '' ?>" href="<?= e($url) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
