<?php
require __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$errors = [];
$old = ['name' => $user['name'] ?? '', 'email' => $user['email'] ?? '', 'subject' => get_str('subject', 150), 'message' => ''];

if (is_post()) {
    csrf_verify();
    $old = [
        'name' => post_str('name', 100), 'email' => post_str('email', 100),
        'subject' => post_str('subject', 150), 'message' => post_str('message', 3000),
    ];
    if (post_str('website', 50) !== '') {            // honeypot: bots fill hidden fields
        redirect('contact.php');
    }
    if (mb_strlen($old['name']) < 2)                          $errors['name'] = 'Please enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))    $errors['email'] = 'Please enter a valid email address.';
    if (mb_strlen($old['subject']) < 3)                       $errors['subject'] = 'Please add a short subject.';
    if (mb_strlen($old['message']) < 10)                      $errors['message'] = 'Please write at least a sentence or two.';
    if (!$errors && time() - (int) ($_SESSION['last_contact'] ?? 0) < 20) {
        $errors['message'] = 'Please wait a few seconds before sending another message.';
    }
    if (!$errors) {
        db_insert('INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)', 'ssss',
            [$old['name'], $old['email'], $old['subject'], $old['message']]);
        $_SESSION['last_contact'] = time();
        flash('success', 'Thank you. Your message has been sent and we\'ll reply by email.');
        redirect('contact.php');
    }
}

page_header('Contact', ['active' => 'contact.php']);
?>
<section class="page-hero"><div class="container"><p class="eyebrow">Contact</p><h1>We'd love to hear from you</h1>
  <p>Questions about a tour, a booking or a custom itinerary? Send us a note.</p></div></section>
<section class="section-tight"><div class="container" style="display:grid;grid-template-columns:1.4fr 1fr;gap:36px;align-items:start">
  <form class="panel" method="post" action="" novalidate>
    <?= csrf_field() ?>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><br><?php endif; ?>
    <div class="form-grid">
      <div class="form-row"><label class="lbl" for="c-name">Your name</label>
        <input class="<?= isset($errors['name']) ? 'invalid' : '' ?>" id="c-name" name="name" type="text" required maxlength="100" value="<?= e($old['name']) ?>" autocomplete="name"></div>
      <div class="form-row"><label class="lbl" for="c-email">Email address</label>
        <input class="<?= isset($errors['email']) ? 'invalid' : '' ?>" id="c-email" name="email" type="email" required maxlength="100" value="<?= e($old['email']) ?>" autocomplete="email"></div>
    </div>
    <div class="form-row"><label class="lbl" for="c-subject">Subject</label>
      <input class="<?= isset($errors['subject']) ? 'invalid' : '' ?>" id="c-subject" name="subject" type="text" required maxlength="150" value="<?= e($old['subject']) ?>"></div>
    <div class="form-row"><label class="lbl" for="c-message">Message</label>
      <textarea class="<?= isset($errors['message']) ? 'invalid' : '' ?>" id="c-message" name="message" rows="6" required maxlength="3000"><?= e($old['message']) ?></textarea></div>
    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <button class="btn btn-lg" type="submit">Send message</button>
  </form>
  <aside class="panel"><h3>Before you write</h3>
    <p class="muted">For anything about an existing booking, include your booking reference (it starts with <strong>EX</strong>) so we can help faster.</p>
    <p class="muted">You can also view and manage your trips any time from your <a href="<?= e(url('user/dashboard.php')) ?>">dashboard</a>.</p>
    <p class="muted small" style="margin:0">This is an academic demonstration site, so messages are read by the project owner.</p></aside>
</div></section>
<?php page_footer();
