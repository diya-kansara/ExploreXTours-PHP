<?php
require __DIR__ . '/includes/bootstrap.php';

$user    = current_user();
$wishIds = wishlist_ids($user);

$categories = db_all(
    'SELECT c.id, c.name, c.slug, COUNT(p.id) AS n
       FROM categories c LEFT JOIN packages p ON p.category_id = c.id AND p.is_active = 1
      GROUP BY c.id ORDER BY c.sort_order, c.name'
);
$featured = db_all(PACKAGE_SELECT . ' WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.id DESC LIMIT 6');
if (count($featured) < 3) {   // fall back to newest packages if few are flagged
    $featured = db_all(PACKAGE_SELECT . ' WHERE p.is_active = 1 ORDER BY p.is_featured DESC, p.id DESC LIMIT 6');
}
$destinations = db_all(
    'SELECT p.location, p.image, p.price, p.id,
            (SELECT COUNT(*) FROM packages x WHERE x.location = p.location AND x.is_active = 1) AS tours
       FROM packages p
      WHERE p.is_active = 1 AND p.image <> \'\'
        AND p.id = (SELECT MIN(y.id) FROM packages y WHERE y.location = p.location AND y.is_active = 1 AND y.image <> \'\')
      ORDER BY (SELECT COUNT(*) FROM bookings b JOIN packages z ON z.id = b.package_id WHERE z.location = p.location) DESC, p.id
      LIMIT 8'
);
$testimonials = db_all(
    'SELECT r.rating, r.title, r.comment, u.name, p.title AS package_title, p.location
       FROM reviews r JOIN users u ON u.id = r.user_id JOIN packages p ON p.id = r.package_id
      WHERE r.status = \'Approved\' ORDER BY r.rating DESC, r.created_at DESC LIMIT 3'
);
$stats = [
    'tours'     => (int) db_value('SELECT COUNT(*) FROM packages WHERE is_active = 1'),
    'travellers'=> (int) db_value('SELECT COALESCE(SUM(guests),0) FROM bookings WHERE status IN (\'Confirmed\',\'Completed\')'),
];

page_header('Home', ['active' => 'home', 'body_class' => 'home']);
?>
<section class="hero" style="background-image:url('<?= e(url('images/hero.jpg')) ?>')">
  <div class="container">
    <p class="eyebrow">Curated journeys · India &amp; beyond</p>
    <h1>Travel that feels <em>effortlessly</em> well planned.</h1>
    <p class="lead">Handpicked tours with clear pricing, day-by-day itineraries and a team that answers when you need them.</p>
    <p><a class="btn btn-light btn-lg" href="<?= e(url('packages.php')) ?>">Explore tours</a></p>
  </div>
  <form class="search-card" method="get" action="<?= e(url('packages.php')) ?>" role="search">
    <div class="field"><label for="hq">Where to?</label><input id="hq" type="search" name="q" placeholder="Goa, Bali, Kerala…" maxlength="80"></div>
    <div class="field"><label for="hc">Style</label>
      <select id="hc" name="category"><option value="">Any style</option>
        <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field"><label for="hp">Budget</label>
      <select id="hp" name="max_price"><option value="">Any budget</option>
        <option value="20000">Up to ₹20,000</option><option value="40000">Up to ₹40,000</option><option value="60000">Up to ₹60,000</option></select></div>
    <button class="btn" type="submit">Search</button>
  </form>
</section>

<section class="section first-after-search" id="destinations">
  <div class="container">
    <div class="section-head reveal">
      <div><p class="eyebrow">Popular destinations</p><h2>Where travellers are heading</h2>
        <p>From golden deserts to alpine rail journeys, start with the places our guests love most.</p></div>
      <a class="btn btn-outline" href="<?= e(url('packages.php')) ?>">View all tours</a>
    </div>
    <div class="dest-grid">
      <?php foreach ($destinations as $d): ?>
        <a class="dest reveal" href="<?= e(url('packages.php?q=' . rawurlencode($d['location']))) ?>">
          <img src="<?= e(pkg_image_url($d['image'])) ?>" alt="<?= e($d['location']) ?>" loading="lazy" width="480" height="640">
          <div class="dest-info"><h3><?= e($d['location']) ?></h3><span><?= e(pluralize((int) $d['tours'], 'tour')) ?> · from <?= e(money($d['price'])) ?></span></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="container">
    <div class="section-head reveal"><div><p class="eyebrow">Tour categories</p><h2>Find your kind of trip</h2></div></div>
    <div class="cat-row reveal">
      <?php foreach ($categories as $c): if (!(int) $c['n']) continue; ?>
        <a class="cat-pill" href="<?= e(url('packages.php?category=' . rawurlencode($c['slug']))) ?>"><?= e($c['name']) ?> <small><?= (int) $c['n'] ?></small></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <div><p class="eyebrow">Recommended for you</p><h2>Featured tour packages</h2>
        <p>Our most loved itineraries. Everything is priced per person with taxes included.</p></div>
      <a class="btn btn-outline" href="<?= e(url('packages.php')) ?>">Browse all <?= (int) $stats['tours'] ?> tours</a>
    </div>
    <div class="grid grid-3">
      <?php foreach ($featured as $p): ?><div class="reveal"><?php package_card($p, $user, $wishIds); ?></div><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head reveal"><div><p class="eyebrow">Why ExploreX</p><h2>Planning you can rely on</h2></div></div>
    <div class="grid grid-4">
      <div class="feature reveal"><div class="icon">🧭</div><h3>Clear itineraries</h3><p>Day-by-day plans, named hotels and an honest list of what is and isn't included.</p></div>
      <div class="feature reveal"><div class="icon">₹</div><h3>Transparent pricing</h3><p>Per-person prices with taxes included. The total you see is the total you pay.</p></div>
      <div class="feature reveal"><div class="icon">🛎️</div><h3>Real support</h3><p>Track every booking online and reach a real person through our contact page.</p></div>
      <div class="feature reveal"><div class="icon">✓</div><h3>Verified reviews</h3><p>Only travellers who completed a trip can review it, so ratings reflect real experiences.</p></div>
    </div>
  </div>
</section>

<?php if ($testimonials): ?>
<section class="section">
  <div class="container">
    <div class="section-head reveal"><div><p class="eyebrow">Traveller stories</p><h2>What our guests say</h2></div></div>
    <div class="grid grid-3">
      <?php foreach ($testimonials as $t): ?>
        <figure class="quote reveal" style="margin:0">
          <?= stars((float) $t['rating']) ?>
          <blockquote>“<?= e(mb_strimwidth($t['comment'], 0, 200, '…')) ?>”</blockquote>
          <cite><?= e($t['name']) ?><span><?= e($t['package_title']) ?> · <?= e($t['location']) ?></span></cite>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="band reveal">
      <div><h2>Not sure where to go?</h2><p>Tell us your dates and budget and we'll suggest a few itineraries that fit.</p></div>
      <div class="actions"><a class="btn btn-light btn-lg" href="<?= e(url('contact.php')) ?>">Talk to us</a>
        <a class="btn btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,.5)" href="<?= e(url('packages.php')) ?>">Browse tours</a></div>
    </div>
  </div>
</section>
<?php page_footer();
