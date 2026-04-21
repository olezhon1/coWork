<?php
// ui/views/view_list.php
// Очікує: $table, $rows, $total, $page, $perPage, $filters (array)

$pages = (int) ceil($total / $perPage);
$meta  = ['label' => $table->label(), 'readonly' => $table->isReadOnly(), 'note' => $table->readOnlyNote()];

$columns = match ($table) {
    AdminTable::Users             => ['id', 'full_name', 'email', 'phone', 'role', 'created_at'],
    AdminTable::Coworkings        => ['id', 'name', 'city', 'address', 'is_24_7', '_workspace_count'],
    AdminTable::Workspaces        => ['id', 'coworking_name', 'name', 'type_key', 'price_per_hour', 'capacity'],
    AdminTable::OperatingHours    => ['id', 'coworking_name', 'day_of_week', 'open_time', 'close_time', 'is_closed'],
    AdminTable::Features          => ['id', 'name', 'icon_key'],
    AdminTable::CoworkingFeatures => ['coworking_id', 'coworking_name', 'feature_id', 'feature_name', 'icon_key'],
    AdminTable::Gallery           => ['id', 'entity_type', 'entity_id', 'is_main', 'image_url'],
    AdminTable::Bookings          => ['id', 'user_name', 'workspace_name', 'coworking_name', 'status', 'total_price', 'created_at'],
    AdminTable::BookingSlots      => ['id', 'booking_id', 'user_name', 'workspace_name', 'start_time', 'end_time', 'booking_status'],
    AdminTable::Subscriptions     => ['id', 'user_name', 'coworking_name', 'hours_left', 'end_date', 'status'],
    AdminTable::Reviews           => ['id', 'user_name', 'coworking_name', 'rating', 'comment', 'created_at'],
};

$colLabels = [
    'id'               => 'ID',
    'full_name'        => 'Ім\'я',
    'email'            => 'Email',
    'phone'            => 'Телефон',
    'role'             => 'Роль',
    'name'             => 'Назва',
    'city'             => 'Місто',
    'address'          => 'Адреса',
    'is_24_7'          => 'Режим',
    '_workspace_count' => 'Місць',
    'coworking_name'   => 'Коворкінг',
    'workspace_name'   => 'Місце',
    'type_key'         => 'Тип',
    'price_per_hour'   => 'Ціна/год',
    'capacity'         => 'Місткість',
    'day_of_week'      => 'День',
    'open_time'        => 'Відкриття',
    'close_time'       => 'Закриття',
    'is_closed'        => 'Стан',
    'icon_key'         => 'Іконка',
    'feature_id'       => 'ID зруч.',
    'feature_name'     => 'Зручність',
    'coworking_id'     => 'ID кв.',
    'entity_type'      => 'Тип',
    'entity_id'        => 'ID об.',
    'is_main'          => 'Головне',
    'image_url'        => 'Фото',
    'user_name'        => 'Користувач',
    'user_id'          => 'Користувач',
    'status'           => 'Статус',
    'booking_status'   => 'Статус брон.',
    'booking_id'       => 'Брон.',
    'total_price'      => 'Сума',
    'created_at'       => 'Дата',
    'start_time'       => 'Початок',
    'end_time'         => 'Кінець',
    'hours_left'       => 'Годин',
    'end_date'         => 'До',
    'expire_date'      => 'До',
    'rating'           => 'Рейтинг',
    'comment'          => 'Коментар',
    'description'      => 'Опис',
];

// ── Поточні параметри сортування ─────────────────────────────────────────────
$curSort = $filters['sort'] ?? '';
$curDir  = strtoupper($filters['dir'] ?? 'DESC');
$baseUrl = '/admin/?t=' . h($table->value);

// Генерує URL для заголовку колонки (перемикає напрямок)
function sortUrl(string $base, string $col, string $curSort, string $curDir): string {
    $dir = ($curSort === $col && $curDir === 'ASC') ? 'DESC' : 'ASC';
    return $base . '&sort=' . urlencode($col) . '&dir=' . $dir;
}

// Відновлюємо фільтри в URL для пагінації
$filterQuery = '';
foreach (['search', 'city', 'status', 'role', 'type_key', 'coworking_id', 'workspace_id',
          'feature_id', 'entity_type', 'is_main', 'is_closed', 'is_24_7', 'rating', 'sort', 'dir'] as $fk) {
    if (!empty($filters[$fk])) {
        $filterQuery .= '&' . urlencode($fk) . '=' . urlencode($filters[$fk]);
    }
}

// ── Конфіг фільтрів для кожної таблиці ───────────────────────────────────────
$filterConfig = match ($table) {
    AdminTable::Users => [
        'search' => ['type' => 'text',   'placeholder' => 'Пошук по імені / email'],
        'role'   => ['type' => 'select', 'placeholder' => 'Всі ролі', 'options' => UserRole::options()],
    ],
    AdminTable::Coworkings => [
        'search'  => ['type' => 'text',   'placeholder' => 'Пошук по назві / місту / адресі'],
        'city'    => ['type' => 'dynamic','placeholder' => 'Всі міста', 'source' => 'cities'],
        'is_24_7' => ['type' => 'select', 'placeholder' => 'Режим роботи', 'options' => ['1' => '24/7', '0' => 'З графіком']],
    ],
    AdminTable::Workspaces => [
        'search'       => ['type' => 'text',    'placeholder' => 'Пошук по назві'],
        'coworking_id' => ['type' => 'rel',     'placeholder' => 'Всі коворкінги', 'rel' => 'coworkings'],
        'type_key'     => ['type' => 'select',  'placeholder' => 'Всі типи', 'options' => WorkspaceType::options()],
    ],
    AdminTable::OperatingHours => [
        'coworking_id' => ['type' => 'rel',    'placeholder' => 'Всі коворкінги', 'rel' => 'coworkings'],
        'is_closed'    => ['type' => 'select', 'placeholder' => 'Всі дні', 'options' => ['0' => 'Робочі', '1' => 'Вихідні']],
    ],
    AdminTable::Features => [
        'search' => ['type' => 'text', 'placeholder' => 'Пошук по назві'],
    ],
    AdminTable::CoworkingFeatures => [
        'coworking_id' => ['type' => 'rel', 'placeholder' => 'Всі коворкінги', 'rel' => 'coworkings'],
        'feature_id'   => ['type' => 'rel', 'placeholder' => 'Всі зручності',  'rel' => 'features'],
    ],
    AdminTable::Gallery => [
        'entity_type' => ['type' => 'select', 'placeholder' => 'Всі типи', 'options' => GalleryEntityType::options()],
        'is_main'     => ['type' => 'select', 'placeholder' => 'Всі фото', 'options' => ['1' => 'Головні', '0' => 'Звичайні']],
    ],
    AdminTable::Bookings => [
        'search'       => ['type' => 'text',   'placeholder' => 'Пошук по користувачу'],
        'status'       => ['type' => 'select', 'placeholder' => 'Всі статуси', 'options' => BookingStatus::options()],
        'coworking_id' => ['type' => 'rel',    'placeholder' => 'Всі коворкінги', 'rel' => 'coworkings'],
    ],
    AdminTable::BookingSlots => [
        'booking_id' => ['type' => 'text', 'placeholder' => 'ID бронювання'],
        'status'     => ['type' => 'select','placeholder' => 'Всі статуси', 'options' => BookingStatus::options()],
    ],
    AdminTable::Subscriptions => [
        'search'       => ['type' => 'text',   'placeholder' => 'Пошук по користувачу'],
        'coworking_id' => ['type' => 'rel',    'placeholder' => 'Всі коворкінги', 'rel' => 'coworkings'],
        'status'       => ['type' => 'select', 'placeholder' => 'Всі статуси', 'options' => SubscriptionStatus::options()],
    ],
    AdminTable::Reviews => [
        'search'       => ['type' => 'text',   'placeholder' => 'Пошук по коментарю / користувачу'],
        'coworking_id' => ['type' => 'rel',    'placeholder' => 'Всі коворкінги', 'rel' => 'coworkings'],
        'rating'       => ['type' => 'select', 'placeholder' => 'Будь-який рейтинг',
                           'options' => ['5'=>'★★★★★','4'=>'★★★★','3'=>'★★★','2'=>'★★','1'=>'★']],
    ],
    default => [],
};

// ── Завантажуємо динамічні списки ─────────────────────────────────────────────
function loadFilterOptions(string $source): array {
    if ($source === 'coworkings') {
        require_once __DIR__ . '/../../db/CoworkingRepository.php';
        return array_column((new CoworkingRepository())->allForSelect(), 'name', 'id');
    }
    if ($source === 'features') {
        require_once __DIR__ . '/../../db/FeatureRepository.php';
        return array_column((new FeatureRepository())->allForSelect(), 'name', 'id');
    }
    if ($source === 'cities') {
        require_once __DIR__ . '/../../db/CoworkingRepository.php';
        $rows = (new CoworkingRepository())->distinctCities();
        $opts = [];
        foreach ($rows as $r) { $opts[$r['city']] = $r['city']; }
        return $opts;
    }
    return [];
}
?>

<!-- Заголовок сторінки -->
<div class="page-header">
  <div>
    <div class="page-title"><?= h($meta['label']) ?></div>
    <div class="page-sub">Всього записів: <?= $total ?></div>
  </div>
  <div class="page-actions">
    <?php if (!$meta['readonly'] && $table !== AdminTable::CoworkingFeatures): ?>
      <a href="/admin/?t=<?= h($table->value) ?>&a=add" class="btn btn-accent">
        <?= icon('add') ?> Додати
      </a>
    <?php elseif ($table === AdminTable::CoworkingFeatures): ?>
      <a href="/admin/?t=<?= h($table->value) ?>&a=add" class="btn btn-accent">
        <?= icon('add') ?> Прив'язати зручність
      </a>
    <?php endif ?>
  </div>
</div>

<?php if ($meta['readonly'] && $meta['note']): ?>
  <div class="readonly-banner"><?= icon('info') ?> <?= h($meta['note']) ?></div>
<?php endif ?>

<!-- Панель фільтрів -->
<?php if (!empty($filterConfig)): ?>
<form method="get" action="/admin/" class="filter-bar">
  <input type="hidden" name="t" value="<?= h($table->value) ?>">
  <?php foreach ($filterConfig as $fKey => $fConf):
      $curVal = $filters[$fKey] ?? '';
  ?>
    <?php if ($fConf['type'] === 'text'): ?>
      <div class="filter-item filter-search">
        <?= icon('search') ?>
        <input type="text" name="<?= h($fKey) ?>" value="<?= h($curVal) ?>"
               placeholder="<?= h($fConf['placeholder']) ?>">
      </div>

    <?php elseif ($fConf['type'] === 'select'): ?>
      <div class="filter-item">
        <select name="<?= h($fKey) ?>">
          <option value=""><?= h($fConf['placeholder']) ?></option>
          <?php foreach ($fConf['options'] as $ov => $ol): ?>
            <option value="<?= h((string)$ov) ?>" <?= (string)$curVal === (string)$ov ? 'selected' : '' ?>>
              <?= h($ol) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>

    <?php elseif (in_array($fConf['type'], ['rel', 'dynamic'])): ?>
      <?php $relOpts = loadFilterOptions($fConf['rel'] ?? $fConf['source'] ?? ''); ?>
      <div class="filter-item">
        <select name="<?= h($fKey) ?>">
          <option value=""><?= h($fConf['placeholder']) ?></option>
          <?php foreach ($relOpts as $ov => $ol): ?>
            <option value="<?= h((string)$ov) ?>" <?= (string)$curVal === (string)$ov ? 'selected' : '' ?>>
              <?= h($ol) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>

    <?php endif ?>
  <?php endforeach ?>
  <button type="submit" class="btn btn-sm"><?= icon('search') ?> Фільтр</button>
  <?php if (array_filter(array_intersect_key($filters, $filterConfig))): ?>
    <a href="/admin/?t=<?= h($table->value) ?>" class="btn btn-sm btn-ghost">✕ Скинути</a>
  <?php endif ?>
</form>
<?php endif ?>

<!-- Таблиця -->
<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <?php foreach ($columns as $col): ?>
          <?php
            $sortKey = match($table) {
                AdminTable::Users         => ['id'=>'id','full_name'=>'full_name','created_at'=>'created_at'],
                AdminTable::Coworkings    => ['id'=>'id','name'=>'name','city'=>'city','created_at'=>'created_at'],
                AdminTable::Workspaces    => ['id'=>'w.id','name'=>'w.name','price_per_hour'=>'w.price_per_hour','capacity'=>'w.capacity'],
                AdminTable::Bookings      => ['id'=>'b.id','total_price'=>'b.total_price','created_at'=>'b.created_at'],
                AdminTable::BookingSlots  => ['id'=>'bs.id','start_time'=>'bs.start_time'],
                AdminTable::Subscriptions => ['id'=>'s.id','hours_left'=>'s.hours_left','end_date'=>'s.end_date'],
                AdminTable::Reviews       => ['id'=>'r.id','rating'=>'r.rating','created_at'=>'r.created_at'],
                default                   => [],
            }[$col] ?? null;
            $isSortable = $sortKey !== null;
            $isActive   = $curSort === $sortKey;
          ?>
          <th class="<?= $col === 'id' ? 'col-id' : '' ?> <?= $isSortable ? 'sortable' : '' ?> <?= $isActive ? 'sort-active' : '' ?>">
            <?php if ($isSortable): ?>
              <a href="<?= sortUrl($baseUrl . $filterQuery, $sortKey, $curSort, $curDir) ?>" class="sort-link">
                <?= h($colLabels[$col] ?? $col) ?>
                <?php if ($isActive): ?>
                  <span class="sort-arrow"><?= $curDir === 'ASC' ? '↑' : '↓' ?></span>
                <?php else: ?>
                  <span class="sort-arrow sort-arrow-inactive">↕</span>
                <?php endif ?>
              </a>
            <?php else: ?>
              <?= h($colLabels[$col] ?? $col) ?>
            <?php endif ?>
          </th>
        <?php endforeach ?>
        <th class="col-actions">Дії</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="<?= count($columns) + 1 ?>">
            <div class="empty-state">
              <div class="empty-state-icon">📭</div>
              <div class="empty-state-title">Записів немає</div>
              <?php if (!$meta['readonly']): ?>
                <div style="margin-top:.75rem;">
                  <a href="/admin/?t=<?= h($table->value) ?>&a=add" class="btn btn-sm btn-accent">
                    <?= icon('add') ?> Додати перший запис
                  </a>
                </div>
              <?php endif ?>
            </div>
          </td>
        </tr>
      <?php endif ?>

      <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($columns as $col): ?>
            <td class="<?= $col === 'id' ? 'col-id' : '' ?>" title="<?= h((string) ($row[$col] ?? '')) ?>">
              <?= renderCell($table, $col, $row) ?>
            </td>
          <?php endforeach ?>

          <!-- Дії -->
          <td class="col-actions">
            <div class="actions">
              <?php
                // Для CoworkingFeatures — немає єдиного id, є пара (coworking_id, feature_id)
                if ($table === AdminTable::CoworkingFeatures):
                  $cwId  = $row['coworking_id'] ?? 0;
                  $ftId  = $row['feature_id']   ?? 0;
              ?>
                <form method="post"
                      action="/admin/?t=<?= h($table->value) ?>&a=delete&coworking_id=<?= (int)$cwId ?>&feature_id=<?= (int)$ftId ?>"
                      class="js-delete-form" style="display:inline;">
                  <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Від'єднати">
                    <?= icon('delete') ?>
                  </button>
                </form>

              <?php elseif (!$meta['readonly']): ?>
                <a href="/admin/?t=<?= h($table->value) ?>&a=edit&id=<?= h((string)$row['id']) ?>"
                   class="btn btn-sm btn-icon" title="Редагувати">
                  <?= icon('edit') ?>
                </a>
                <form method="post"
                      action="/admin/?t=<?= h($table->value) ?>&a=delete&id=<?= h((string)$row['id']) ?>"
                      class="js-delete-form" style="display:inline;">
                  <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Видалити">
                    <?= icon('delete') ?>
                  </button>
                </form>

              <?php else: ?>
                <a href="/admin/?t=<?= h($table->value) ?>&a=view&id=<?= h((string)$row['id']) ?>"
                   class="btn btn-sm btn-icon btn-ghost" title="Переглянути">
                  <?= icon('view') ?>
                </a>
                <form method="post"
                      action="/admin/?t=<?= h($table->value) ?>&a=delete&id=<?= h((string)$row['id']) ?>"
                      class="js-delete-form" style="display:inline;">
                  <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Видалити">
                    <?= icon('delete') ?>
                  </button>
                </form>
              <?php endif ?>
            </div>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<!-- Пагінація -->
<?php if ($pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="<?= $baseUrl . $filterQuery ?>&p=<?= $page - 1 ?>" class="page-btn"><?= icon('chevron_left') ?></a>
    <?php endif ?>
    <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
      <a href="<?= $baseUrl . $filterQuery ?>&p=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor ?>
    <?php if ($page < $pages): ?>
      <a href="<?= $baseUrl . $filterQuery ?>&p=<?= $page + 1 ?>" class="page-btn"><?= icon('chevron_right') ?></a>
    <?php endif ?>
    <span class="page-info">Сторінка <?= $page ?> з <?= $pages ?></span>
  </div>
<?php endif ?>
