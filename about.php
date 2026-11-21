<?php
require __DIR__ . '/includes/bootstrap.php';
$stats = [
    'tours' => (int) db_value('SELECT COUNT(*) FROM packages WHERE is_active = 1'),
    'places' => (int) db_value('SELECT COUNT(DISTINCT location) FROM packages WHERE is_active = 1'),
];
page_header('About us', ['active' => 'about.php']);
?>
<section class="page-hero"><div class="container"><p class="eyebrow">About ExploreX</p><h1>Your trusted partner for unforgettable travel</h1>
  <p>Personalised tours, honest pricing and journeys that feel easy from the first click.</p></div></section>
<section class="section"><div class="container">
  <div class="grid grid-2">
    <div class="panel reveal"><h2>Who we are</h2><p>We are a passionate team of travel enthusiasts committed to offering personalised tours, exceptional service and memorable journeys tailored just for you.</p></div>
    <div class="panel reveal"><h2>Our mission</h2><p>To provide hassle-free, safe and affordable travel packages that inspire and delight travellers from every walk of life.</p></div>
  </div>
  <div class="stat-grid" style="grid-template-columns:repeat(2,1fr);max-width:420px;margin-top:32px">
    <div class="stat"><div class="n"><?= $stats['tours'] ?></div><div class="l">curated tours</div></div>
    <div class="stat"><div class="n"><?= $stats['places'] ?></div><div class="l">destinations</div></div>
  </div>
</div></section>
<section class="section section-alt"><div class="container">
  <div class="section-head"><div><p class="eyebrow">Why choose us</p><h2>The ExploreX difference</h2></div></div>
  <div class="grid grid-4">
    <div class="feature reveal"><div class="icon">🌍</div><h3>Handpicked tours</h3><p>Designed by experts with deep local knowledge.</p></div>
    <div class="feature reveal"><div class="icon">₹</div><h3>Competitive pricing</h3><p>No hidden fees. Every price shows what is and isn't included.</p></div>
    <div class="feature reveal"><div class="icon">📞</div><h3>Support when needed</h3><p>Track bookings online and message us any time.</p></div>
    <div class="feature reveal"><div class="icon">🔄</div><h3>Flexible booking</h3><p>Plans changed? Request a cancellation from your dashboard.</p></div>
  </div>
</div></section>
<section class="section center"><div class="container narrow"><h2>Have questions?</h2><p class="lead">We're happy to help.</p>
  <a class="btn btn-lg" href="<?= e(url('contact.php')) ?>">Contact us</a></div></section>
<?php page_footer();
