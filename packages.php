<?php
require __DIR__ . '/includes/bootstrap.php';

$user    = current_user();
$wishIds = wishlist_ids($user);

// ---- read filters (all validated / whitelisted) ----------------------------
$q        = get_str('q', 80);
$catSlug  = get_str('category', 80);
$price    = get_str('price', 20);
if ($price === '' && get_int('max_price') > 0) {
    $price = '0-' . get_int('max_price');
}
$days     = get_str('days', 6);
$sort     = get_str('sort', 12);
$page     = max(1, get_int('page', 1));
$perPage  = 9;

$priceRanges = ['0-20000' => 'Under ₹20,000', '20000-40000' => '₹20,000 – ₹40,000', '40000-60000' => '₹40,000 – ₹60,000', '60000-' => '₹60,000 & above'];
$dayRanges   = ['1-3' => '1 – 3 days', '4-4' => '4 days', '5-99' => '5+ days'];
$sorts = [
    'popular'    => ['Most popular',        'booking_count DESC, review_count DESC, p.id DESC'],
    'rating'     => ['Highest rated',       'avg_rating DESC, review_count DESC, p.id DESC'],
    'price_asc'  => ['Price: low to high',  'p.price ASC, p.id ASC'],
    'price_desc' => ['Price: high to low',  'p.price DESC, p.id ASC'],
    'duration'   => ['Shortest first',      'p.duration_days ASC, p.price ASC'],
    'newest'     => ['Newest',              'p.id DESC'],
];
if (!isset($sorts[$sort])) {
    $sort = $q !== '' ? 'popular' : 'popular';
}
if ($price !== '' && !preg_match('/^\d*-\d*$/', $price)) $price = '';
if ($days !== '' && !isset($dayRanges[$days]))         $days = '';

$categories = db_all(
    'SELECT c.slug, c.name, COUNT(p.id) AS n FROM categories c
       LEFT JOIN packages p ON p.category_id = c.id AND p.is_active = 1
      GROUP BY c.id ORDER BY c.sort_order'
);
$catSlugs = array_column($categories, 'slug');
if ($catSlug !== '' && !in_array($catSlug, $catSlugs, true)) {
    $catSlug = '';
}

// ---- build WHERE with bound parameters -------------------------------------
$where  = ['p.is_active = 1'];
$types  = '';
$params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(p.title LIKE ? OR p.location LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($catSlug !== '') {
    $where[] = 'c.slug = ?';
    $types .= 's';
    $params[] = $catSlug;
}
if ($price !== '') {
    [$lo, $hi] = explode('-', $price) + [1 => ''];
    if ($lo !== '') { $where[] = 'p.price >= ?'; $types .= 'd'; $params[] = (float) $lo; }
    if ($hi !== '') { $where[] = 'p.price <= ?'; $types .= 'd'; $params[] = (float) $hi; }
}
if ($days !== '') {
    [$lo, $hi] = explode('-', $days);
    $where[] = 'p.duration_days BETWEEN ? AND ?';
    $types .= 'ii';
    array_push($params, (int) $lo, (int) $hi);
}
$whereSql = implode(' AND ', $where);

$total = (int) db_value(
    "SELECT COUNT(*) FROM packages p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql", $types, $params
);
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);
$rows  = db_all(
    PACKAGE_SELECT . " WHERE $whereSql ORDER BY " . $sorts[$sort][1] . ' LIMIT ? OFFSET ?',
    $types . 'ii', array_merge($params, [$perPage, ($page - 1) * $perPage])
);

function qs(array $override = []): string
{
    $base = array_filter([
        'q' => $GLOBALS['q'], 'category' => $GLOBALS['catSlug'], 'price' => $GLOBALS['price'],
        'days' => $GLOBALS['days'], 'sort' => $GLOBALS['sort'] !== 'popular' ? $GLOBALS['sort'] : '',
    ], fn($v) => $v !== '');
    $merged = array_filter(array_merge($base, $override), fn($v) => $v !== '' && $v !== null);
    return $merged ? '?' . http_build_query($merged) : '';
}

$filtersOn = ($q !== '' || $catSlug !== '' || $price !== '' || $days !== '');
page_header('Tour packages', ['active' => 'packages.php', 'description' => 'Browse and filter ExploreX tour packages by destination, price, duration and style.']);
?>
<section class="page-hero">
  <div class="container">
    <p class="eyebrow">Tour packages</p>
    <h1>Find your next journey</h1>
    <p>Search by destination or tour name, then narrow by budget, length and style.</p>
  </div>
</section>

<section class="section-tight">
  <div class="container listing">
    <aside>
      <button class="btn btn-outline btn-sm filters-toggle" type="button">Filters ▾</button>
      <form class="filters" method="get" action="<?= e(url('packages.php')) ?>">
        <h3>Refine results</h3>
        <div class="group"><label for="f-q">Search</label>
          <input id="f-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Package or destination" maxlength="80"></div>
        <div class="group"><label for="f-cat">Style</label>
          <select id="f-cat" name="category"><option value="">All styles</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= e($c['slug']) ?>" <?= $catSlug === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= (int) $c['n'] ?>)</option>
            <?php endforeach; ?></select></div>
        <fieldset><legend>Price per person</legend>
          <label class="radio"><input type="radio" name="price" value="" <?= $price === '' ? 'checked' : '' ?>> Any price</label>
          <?php foreach ($priceRanges as $v => $label): ?>
            <label class="radio"><input type="radio" name="price" value="<?= e($v) ?>" <?= $price === $v ? 'checked' : '' ?>> <?= e($label) ?></label>
          <?php endforeach; ?>
        </fieldset>
        <fieldset><legend>Duration</legend>
          <label class="radio"><input type="radio" name="days" value="" <?= $days === '' ? 'checked' : '' ?>> Any length</label>
          <?php foreach ($dayRanges as $v => $label): ?>
            <label class="radio"><input type="radio" name="days" value="<?= e($v) ?>" <?= $days === $v ? 'checked' : '' ?>> <?= e($label) ?></label>
          <?php endforeach; ?>
        </fieldset>
        <div class="group"><label for="f-sort">Sort by</label>
          <select id="f-sort" name="sort">
            <?php foreach ($sorts as $k => [$label]): ?><option value="<?= e($k) ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select></div>
        <button class="btn btn-block" type="submit">Apply filters</button>
        <?php if ($filtersOn): ?><p class="center" style="margin:14px 0 0"><a href="<?= e(url('packages.php')) ?>">Clear all filters</a></p><?php endif; ?>
      </form>
    </aside>

    <div>
      <div class="results-bar">
        <p style="margin:0"><strong><?= (int) $total ?></strong> <?= $total === 1 ? 'tour' : 'tours' ?> found<?= $q !== '' ? ' for “' . e($q) . '”' : '' ?></p>
        <span class="muted small">Sorted by <?= e(strtolower($sorts[$sort][0])) ?></span>
      </div>
      <?php if ($filtersOn): ?>
        <div class="active-filters">
          <?php if ($q !== ''): ?><span class="tag">“<?= e($q) ?>”</span><?php endif; ?>
          <?php foreach ($categories as $c) if ($c['slug'] === $catSlug): ?><span class="tag"><?= e($c['name']) ?></span><?php endif; ?>
          <?php if ($price !== '' && isset($priceRanges[$price])): ?><span class="tag"><?= e($priceRanges[$price]) ?></span>
          <?php elseif ($price !== ''): ?><span class="tag">Price <?= e($price) ?></span><?php endif; ?>
          <?php if ($days !== ''): ?><span class="tag"><?= e($dayRanges[$days]) ?></span><?php endif; ?>
          <a class="small" href="<?= e(url('packages.php')) ?>">Clear all</a>
        </div>
      <?php endif; ?>

      <?php if (!$rows): ?>
        <div class="empty"><div class="big">🧭</div><h3>No tours match these filters</h3>
          <p class="muted">Try a different destination, widen the price range or clear the filters.</p>
          <a class="btn" href="<?= e(url('packages.php')) ?>">Clear filters</a></div>
      <?php else: ?>
        <div class="grid grid-3" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
          <?php foreach ($rows as $p) package_card($p, $user, $wishIds); ?>
        </div>
        <?php if ($pages > 1): ?>
          <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?><a href="<?= e(url('packages.php' . qs(['page' => (string) ($page - 1)]))) ?>" aria-label="Previous page">‹</a><?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
              <?php if ($i === $page): ?><span class="current" aria-current="page"><?= $i ?></span>
              <?php else: ?><a href="<?= e(url('packages.php' . qs(['page' => (string) $i]))) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a href="<?= e(url('packages.php' . qs(['page' => (string) ($page + 1)]))) ?>" aria-label="Next page">›</a><?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php page_footer();
