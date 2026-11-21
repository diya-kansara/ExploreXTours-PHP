<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];
$errP = []; $errW = [];
$old = ['name' => $user['name'], 'email' => $user['email'], 'phone' => (string) $user['phone']];

if (is_post()) {
    csrf_verify();
    $action = post_str('action', 20);
    if ($action === 'profile') {
        $old = ['name' => post_str('name', 100), 'email' => post_str('email', 150), 'phone' => post_str('phone', 20)];
        if (mb_strlen($old['name']) < 2)                         $errP['name'] = 'Please enter your name.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))   $errP['email'] = 'Please enter a valid email address.';
        if ($old['phone'] !== '' && !valid_phone($old['phone'])) $errP['phone'] = 'Please enter a valid phone number or leave it blank.';
        if (!$errP) {
            try {
                db_exec('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?', 'sssi',
                    [$old['name'], strtolower($old['email']), $old['phone'] !== '' ? $old['phone'] : null, $uid]);
                flash('success', 'Profile updated.');
                redirect('user/profile.php');
            } catch (mysqli_sql_exception $e) {
                if ((int) $e->getCode() !== 1062) { throw $e; }
                $errP['email'] = 'That email is already used by another account.';
            }
        }
    } elseif ($action === 'password') {
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $con = (string) ($_POST['confirm_password'] ?? '');
        $hash = (string) db_value('SELECT password FROM users WHERE id = ?', 'i', [$uid]);
        if (!password_verify($cur, $hash))     $errW['current_password'] = 'Your current password is incorrect.';
        if ($pp = password_problems($new)) $errW['new_password'] = $pp[0];
        if ($new !== $con)                      $errW['confirm_password'] = 'New passwords do not match.';
        if (!$errW) {
            db_exec('UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
            redirect('user/profile.php');
        }
    }
}
dash_open('profile', 'Profile', $user);
?>
<h1>Profile</h1>
<form class="panel" method="post" action="" novalidate>
  <h3>Personal details</h3><?= csrf_field() ?><input type="hidden" name="action" value="profile">
  <?php render_errors($errP); ?>
  <div class="form-grid">
    <div class="form-row"><label class="lbl" for="name">Full name</label><input id="name" name="name" type="text" required maxlength="100" value="<?= e($old['name']) ?>"></div>
    <div class="form-row"><label class="lbl" for="phone">Phone</label><input id="phone" name="phone" type="tel" maxlength="20" value="<?= e($old['phone']) ?>"></div>
  </div>
  <div class="form-row"><label class="lbl" for="email">Email address</label><input id="email" name="email" type="email" required maxlength="150" value="<?= e($old['email']) ?>"></div>
  <button class="btn" type="submit">Save changes</button>
</form>
<form class="panel" method="post" action="" novalidate>
  <h3>Change password</h3><?= csrf_field() ?><input type="hidden" name="action" value="password">
  <?php render_errors($errW); ?>
  <div class="form-row"><label class="lbl" for="cp">Current password</label><input id="cp" name="current_password" type="password" required autocomplete="current-password"></div>
  <div class="form-grid">
    <div class="form-row"><label class="lbl" for="np">New password</label><input id="np" name="new_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"><div class="hint">8+ characters with a letter and a number.</div></div>
    <div class="form-row"><label class="lbl" for="np2">Confirm new password</label><input id="np2" name="confirm_password" type="password" required autocomplete="new-password"></div>
  </div>
  <button class="btn" type="submit">Update password</button>
</form>
<p class="muted small" style="margin-top:20px">Member since <?= e(fmt_date($user['created_at'], 'F Y')) ?>.</p>
<?php dash_close();
