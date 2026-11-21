<?php
require __DIR__ . '/../includes/bootstrap.php';
if (current_admin()) { redirect('admin/dashboard.php'); }
$errors = []; $email = '';
if (is_post()) {
    csrf_verify();
    $email = post_str('email', 150);
    $pw = (string) ($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pw === '') {
        $errors[] = 'Please enter your email and password.';
    } else {
        [$row, $err] = check_credentials('admin', $email, $pw);
        if ($row) { login_admin((int) $row['id']); redirect('admin/dashboard.php'); }
        $errors[] = $err;
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><meta name="csrf-token" content="<?= e(csrf_token()) ?>"><title>Admin login · ExploreX</title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"><link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>"></head>
<body class="admin"><div class="login-bg"><div class="auth-card">
  <p class="eyebrow">Staff only</p><h1>Admin sign in</h1>
  <p class="muted">ExploreX administration panel.</p>
  <?php render_errors($errors); ?>
  <form method="post" action="" novalidate><?= csrf_field() ?>
    <div class="form-row"><label class="lbl" for="email">Email</label><input id="email" name="email" type="email" required maxlength="150" value="<?= e($email) ?>" autocomplete="username" autofocus></div>
    <div class="form-row"><label class="lbl" for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="btn btn-block btn-lg" type="submit">Sign in</button>
  </form>
  <p class="center small muted" style="margin:18px 0 0"><a href="<?= e(url('index.php')) ?>">← Back to website</a></p>
</div></div></body></html>
