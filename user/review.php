<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];

$ref = get_str('ref', 24) ?: post_str('ref', 24);
$b = user_booking_by_ref($ref, $uid);                    // must be this customer's own booking
if (!$b) {
    abort(404, 'We could not find that booking in your account.');
}
if ($b['status'] !== 'Completed') {
    flash('info', 'You can review a tour after your trip is marked as completed.');
    redirect('user/booking_view.php?ref=' . rawurlencode($ref));
}
if (!can_review_booking($b)) {                           // already reviewed (also enforced by a UNIQUE key)
    flash('info', 'You have already reviewed this trip. Thank you.');
    redirect('user/booking_view.php?ref=' . rawurlencode($ref));
}

$errors = [];
$old = ['rating' => 0, 'title' => '', 'comment' => ''];
if (is_post()) {
    csrf_verify();
    $old = ['rating' => post_int('rating'), 'title' => post_str('title', 120), 'comment' => post_str('comment', 2000)];
    if ($old['rating'] < 1 || $old['rating'] > 5)   $errors['rating'] = 'Please choose a star rating from 1 to 5.';
    if (mb_strlen($old['comment']) < 10)            $errors['comment'] = 'Please write at least a sentence about your trip.';
    if (!$errors) {
        try {
            db_insert('INSERT INTO reviews (booking_id, user_id, package_id, rating, title, comment) VALUES (?,?,?,?,?,?)', 'iiiiss',
                [(int) $b['id'], $uid, (int) $b['package_id'], $old['rating'], $old['title'] !== '' ? $old['title'] : null, $old['comment']]);
            flash('success', 'Thank you. Your review will appear once our team has approved it.');
            redirect('user/booking_view.php?ref=' . rawurlencode($ref));
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() !== 1062) { throw $e; }
            flash('info', 'You have already reviewed this trip.');
            redirect('user/booking_view.php?ref=' . rawurlencode($ref));
        }
    }
}
dash_open('bookings', 'Review your trip', $user);
?>
<p class="crumbs" style="margin-top:0"><a href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($ref))) ?>">← Back to booking</a></p>
<h1>Review “<?= e($b['package_title']) ?>”</h1>
<p class="muted">Travelled <?= e(fmt_date($b['travel_date'])) ?>. Your review helps other travellers.</p>
<form class="panel" method="post" action="" novalidate>
  <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
  <?php render_errors($errors); ?>
  <div class="form-row"><span class="lbl" id="rate-lbl">Your rating</span>
    <div class="star-input" role="radiogroup" aria-labelledby="rate-lbl">
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $old['rating'] === $i ? 'checked' : '' ?> required><label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★<span class="sr-only"> <?= $i ?> star<?= $i > 1 ? 's' : '' ?></span></label>
      <?php endfor; ?>
    </div></div>
  <div class="form-row"><label class="lbl" for="title">Headline <span class="muted">(optional)</span></label><input id="title" name="title" type="text" maxlength="120" value="<?= e($old['title']) ?>"></div>
  <div class="form-row"><label class="lbl" for="comment">Your review</label><textarea id="comment" name="comment" rows="6" required maxlength="2000"><?= e($old['comment']) ?></textarea></div>
  <button class="btn" type="submit">Submit review</button>
</form>
<?php dash_close();
