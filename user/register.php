<?php
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('user/dashboard.php');
}
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', 'user/dashboard.php');
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if (is_post()) {
    csrf_verify();
    $old = ['name' => post_str('name', 100), 'email' => post_str('email', 150), 'phone' => post_str('phone', 20)];
    $pw = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['confirm_password'] ?? '');

    if (mb_strlen($old['name']) < 2)                          $errors['name'] = 'Please enter your full name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))    $errors['email'] = 'Please enter a valid email address.';
    if ($old['phone'] !== '' && !valid_phone($old['phone']))  $errors['phone'] = 'Please enter a valid phone number or leave it blank.';
    if ($pp = password_problems($pw)) $errors['password'] = $pp[0];
    if ($pw !== $pw2)                                         $errors['confirm_password'] = 'Passwords do not match.';
    if (empty($_POST['terms']))                               $errors['terms'] = 'Please accept the terms to continue.';

    if (!$errors) {
        try {
            $id = db_insert('INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)', 'ssss',
                [$old['name'], strtolower($old['email']), $old['phone'] !== '' ? $old['phone'] : null, password_hash($pw, PASSWORD_DEFAULT)]);
            login_user($id);
            flash('success', 'Your account is ready. Welcome to ExploreX.');
            redirect($next);
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() !== 1062) {
                throw $e;
            }
            $errors['email'] = 'An account with this email already exists. Try logging in instead.';
        }
    }
}
page_header('Create account', ['active' => 'register']);
?>
<div class="auth-wrap"><div class="auth-card" style="width:min(520px,100% - 40px)">
  <p class="eyebrow">Join ExploreX</p><h1>Create your account</h1>
  <p class="muted">Book trips, save favourites and track everything in one place.</p>
  <?php render_errors($errors); ?>
  <form method="post" action="" novalidate>
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="form-row"><label class="lbl" for="name">Full name</label>
      <input id="name" name="name" type="text" required maxlength="100" value="<?= e($old['name']) ?>" autocomplete="name"></div>
    <div class="form-grid">
      <div class="form-row"><label class="lbl" for="email">Email address</label>
        <input id="email" name="email" type="email" required maxlength="150" value="<?= e($old['email']) ?>" autocomplete="email"></div>
      <div class="form-row"><label class="lbl" for="phone">Phone <span class="muted">(optional)</span></label>
        <input id="phone" name="phone" type="tel" maxlength="20" value="<?= e($old['phone']) ?>" autocomplete="tel"></div>
    </div>
    <div class="form-grid">
      <div class="form-row"><label class="lbl" for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
        <div class="hint">8+ characters with a letter and a number.</div></div>
      <div class="form-row"><label class="lbl" for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></div>
    </div>
    <div class="form-row"><label class="check"><input type="checkbox" name="terms" value="1" required> <span>I understand this is an academic demo site and payments are simulated.</span></label></div>
    <button class="btn btn-block btn-lg" type="submit">Create account</button>
  </form>
  <p class="center muted" style="margin:20px 0 0">Already registered? <a href="<?= e(url('user/login.php?next=' . rawurlencode($next))) ?>">Log in</a></p>
</div></div>
<?php page_footer();
