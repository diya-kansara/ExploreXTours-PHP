<?php
require __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('user/dashboard.php');
}
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', 'user/dashboard.php');
$errors = [];
$email = '';

if (is_post()) {
    csrf_verify();
    $email = post_str('email', 150);
    $password = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Please enter your email and password.';
    } else {
        [$row, $err] = check_credentials('user', $email, $password);
        if ($row) {
            login_user((int) $row['id']);
            flash('success', 'Welcome back, ' . explode(' ', $row['name'])[0] . '.');
            redirect($next);
        }
        $errors[] = $err;
    }
}
page_header('Log in', ['active' => 'login']);
?>
<div class="auth-wrap"><div class="auth-card">
  <p class="eyebrow">Welcome back</p><h1>Log in</h1>
  <p class="muted">Access your bookings, saved tours and profile.</p>
  <?php render_errors($errors); ?>
  <form method="post" action="" novalidate>
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="form-row"><label class="lbl" for="email">Email address</label>
      <input id="email" name="email" type="email" required maxlength="150" value="<?= e($email) ?>" autocomplete="email" autofocus></div>
    <div class="form-row"><label class="lbl" for="password">Password</label>
      <input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="btn btn-block btn-lg" type="submit">Log in</button>
  </form>
  <p class="center muted" style="margin:20px 0 0">New to ExploreX? <a href="<?= e(url('user/register.php?next=' . rawurlencode($next))) ?>">Create an account</a></p>
</div></div>
<?php page_footer();
