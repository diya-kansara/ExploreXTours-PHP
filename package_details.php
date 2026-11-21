<?php
require __DIR__ . '/includes/bootstrap.php';

$id  = get_int('id');
$pkg = $id > 0 ? package_by_id($id) : null;
if (!$pkg) {
    abort(404, 'That tour package does not exist or is no longer available.');
}
$full = package_full_by_id($id);

$user    = current_user();
$wishIds = wishlist_ids($user);

$extraImages = db_all('SELECT image, caption FROM package_images WHERE package_id = ? ORDER BY sort_order, id LIMIT 2', 'i', [$id]);
$gallery = array_merge([['image' => $pkg['image'], 'caption' => $pkg['title']]], $extraImages);
$galleryClass = count($gallery) === 1 ? 'single' : (count($gallery) === 2 ? 'two' : '');

$days   = db_all('SELECT day_number, title, description FROM itinerary_days WHERE package_id = ? ORDER BY day_number', 'i', [$id]);
$hotels = db_all('SELECT name, city, star_rating, description FROM hotels WHERE package_id = ? ORDER BY sort_order, id', 'i', [$id]);
$items  = db_all('SELECT item, type FROM package_inclusions WHERE package_id = ? ORDER BY type, sort_order, id', 'i', [$id]);
$incl = array_values(array_filter($items, fn($i) => $i['type'] === 'inclusion'));
$excl = array_values(array_filter($items, fn($i) => $i['type'] === 'exclusion'));
$reviews = db_all(
    'SELECT r.rating, r.title, r.comment, r.created_at, u.name
       FROM reviews r JOIN users u ON u.id = r.user_id
      WHERE r.package_id = ? AND r.status = \'Approved\' ORDER BY r.created_at DESC LIMIT 10', 'i', [$id]
);
$related = db_all(
    PACKAGE_SELECT . ' WHERE p.is_active = 1 AND p.id <> ? ORDER BY (p.category_id <=> ?) DESC, ABS(p.price - ?) ASC LIMIT 3',
    'iid', [$id, $full['category_id'], (float) $pkg['price']]
);
$inWish = in_array($id, $wishIds, true);
$overview = trim((string) ($full['overview'] ?? '')) !== '' ? $full['overview'] : $full['description'];

page_header($pkg['title'], ['active' => 'packages.php', 'description' => mb_strimwidth((string) $pkg['description'], 0, 155, '…')]);
?>
<div class="container">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a> / <a href="<?= e(url('packages.php')) ?>">Tours</a> / <?= e($pkg['title']) ?></nav>
  <div class="gallery <?= e($galleryClass) ?>">
    <?php foreach (array_slice($gallery, 0, 3) as $g): ?>
      <a href="<?= e(pkg_image_url($g['image'])) ?>" target="_blank" rel="noopener"><img src="<?= e(pkg_image_url($g['image'])) ?>" alt="<?= e($g['caption'] ?: $pkg['title']) ?>"></a>
    <?php endforeach; ?>
  </div>

  <div class="detail">
    <article>
      <?php if ($pkg['category_name']): ?><p class="eyebrow"><?= e($pkg['category_name']) ?></p><?php endif; ?>
      <h1><?= e($pkg['title']) ?></h1>
      <p class="lead" style="margin-bottom:0">📍 <?= e($pkg['location']) ?></p>
      <div class="facts">
        <span>⏱ <strong><?= e($pkg['duration'] ?: pluralize((int) $pkg['duration_days'], 'day')) ?></strong></span>
        <span>👥 Up to <strong><?= (int) $full['max_guests'] ?></strong> guests</span>
        <?php if ((int) $pkg['review_count']): ?><span><?= stars((float) $pkg['avg_rating']) ?> <strong><?= e(number_format((float) $pkg['avg_rating'], 1)) ?></strong> (<?= (int) $pkg['review_count'] ?> <?= (int) $pkg['review_count'] === 1 ? 'review' : 'reviews' ?>)</span><?php endif; ?>
      </div>

      <section><h2>About this tour</h2><p><?= nl2br(e($overview)) ?></p></section>

      <?php if ($days): ?>
      <section><h2>Day-by-day itinerary</h2>
        <ol class="timeline">
          <?php foreach ($days as $d): ?>
            <li><span class="day">Day <?= (int) $d['day_number'] ?></span><h3><?= e($d['title']) ?></h3>
              <?php if (trim((string) $d['description']) !== ''): ?><p class="muted"><?= nl2br(e($d['description'])) ?></p><?php endif; ?></li>
          <?php endforeach; ?>
        </ol></section>
      <?php endif; ?>

      <?php if ($hotels): ?>
      <section><h2>Where you'll stay</h2>
        <?php foreach ($hotels as $h): ?>
          <div class="hotel"><div class="ic">🏨</div><div>
            <strong><?= e($h['name']) ?></strong>
            <?php if ($h['star_rating']): ?> <span class="stars"><?= str_repeat('<span class="on">★</span>', (int) $h['star_rating']) ?></span><?php endif; ?>
            <?php if ($h['city']): ?><div class="muted small"><?= e($h['city']) ?></div><?php endif; ?>
            <?php if ($h['description']): ?><p class="muted" style="margin:6px 0 0"><?= e($h['description']) ?></p><?php endif; ?>
          </div></div>
        <?php endforeach; ?></section>
      <?php endif; ?>

      <?php if ($incl || $excl): ?>
      <section><h2>What's included</h2>
        <div class="incl-grid">
          <div class="incl-box yes"><h3>Included</h3><ul><?php foreach ($incl as $i): ?><li><?= e($i['item']) ?></li><?php endforeach; ?></ul></div>
          <div class="incl-box no"><h3>Not included</h3><ul><?php foreach ($excl as $i): ?><li><?= e($i['item']) ?></li><?php endforeach; ?>
            <?php if (!$excl): ?><li class="muted">Nothing listed. Ask us if unsure.</li><?php endif; ?></ul></div>
        </div></section>
      <?php endif; ?>

      <section><h2>Traveller reviews</h2>
        <?php if (!$reviews): ?><p class="muted">No reviews yet. Reviews appear here after guests complete this trip.</p>
        <?php else: foreach ($reviews as $r): ?>
          <div class="review"><div><?= stars((float) $r['rating']) ?> <?php if ($r['title']): ?><strong><?= e($r['title']) ?></strong><?php endif; ?></div>
            <p style="margin:6px 0"><?= e($r['comment']) ?></p>
            <div class="muted small"><span class="who"><?= e($r['name']) ?></span> · <?= e(fmt_date($r['created_at'], 'M Y')) ?></div></div>
        <?php endforeach; endif; ?>
      </section>

      <section><h2>Booking information</h2>
        <ul class="muted" style="line-height:1.9">
          <li>Prices are per person and include all taxes listed in the inclusions.</li>
          <li>Choose your travel date and guests when booking. The total is calculated for you.</li>
          <li>Your booking is confirmed by our team after you submit it. You can request a cancellation from your dashboard.</li>
          <li>Payments on this website are a <strong>demonstration</strong> and never charge real money.</li>
        </ul></section>
    </article>

    <aside>
      <div class="booking-box">
        <div class="price"><?= e(money($pkg['price'])) ?><small> / person</small></div>
        <ul>
          <li>⏱ <?= e($pkg['duration'] ?: pluralize((int) $pkg['duration_days'], 'day')) ?></li>
          <li>👥 Up to <?= (int) $full['max_guests'] ?> guests per booking</li>
          <li>✓ Taxes included</li>
        </ul>
        <a class="btn btn-block btn-lg" href="<?= e(url('book.php?package_id=' . $id)) ?>">Book this tour</a>
        <form class="wish-form" style="position:static;margin-top:12px" method="post" action="<?= e(url('wishlist_toggle.php')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="package_id" value="<?= $id ?>">
          <input type="hidden" name="back" value="<?= e(current_path()) ?>">
          <button class="btn btn-outline btn-block" type="submit" data-wish aria-pressed="<?= $inWish ? 'true' : 'false' ?>"><?= $inWish ? '♥ Saved. Click to remove' : '♡ Save for later' ?></button>
        </form>
        <p class="muted small center" style="margin:14px 0 0">Not ready? Save it and come back any time.</p>
      </div>
    </aside>
  </div>

  <?php if ($related): ?>
  <section style="padding-bottom:80px">
    <div class="section-head"><div><p class="eyebrow">You might also like</p><h2>Related tours</h2></div></div>
    <div class="grid grid-3"><?php foreach ($related as $p) package_card($p, $user, $wishIds); ?></div>
  </section>
  <?php endif; ?>
</div>
<?php page_footer();
