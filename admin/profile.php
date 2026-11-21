<?php
require __DIR__ . '/_init.php';
$errP = []; $errW = []; $old = ['name' => $admin['name'], 'email' => $admin['email']];
if (is_post()) {
    csrf_verify();
    if (post_str('action', 10) === 'profile') {
        $old = ['name' => post_str('name', 100), 'email' => post_str('email', 150)];
        if (mb_strlen($old['name']) < 2) $errP['name'] = 'Enter your name.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errP['email'] = 'Enter a valid email.';
        if (!$errP) {
            try { db_exec('UPDATE admins SET name = ?, email = ? WHERE id = ?', 'ssi', [$old['name'], strtolower($old['email']), (int) $admin['id']]); flash('success', 'Profile updated.'); redirect('admin/profile.php'); }
            catch (mysqli_sql_exception $e) { if ((int) $e->getCode() !== 1062) throw $e; $errP['email'] = 'That email is already used by another administrator.'; }
        }
    } else {
        $cur = (string) ($_POST['current_password'] ?? ''); $new = (string) ($_POST['new_password'] ?? ''); $con = (string) ($_POST['confirm_password'] ?? '');
        $hash = (string) db_value('SELECT password FROM admins WHERE id = ?', 'i', [(int) $admin['id']]);
        if (!password_verify($cur, $hash)) $errW['cur'] = 'Your current password is incorrect.';
        if ($pp = password_problems($new)) $errW['new'] = $pp[0];
        if ($new !== $con) $errW['con'] = 'New passwords do not match.';
        if (!$errW) { db_exec('UPDATE admins SET password = ? WHERE id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), (int) $admin['id']]); session_regenerate_id(true); flash('success', 'Password changed.'); redirect('admin/profile.php'); }
    }
}
admin_header('My profile', 'profile', $admin);
?>
<form class="panel" method="post" action="" novalidate style="max-width:620px"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><h2>Profile</h2><?php render_errors($errP); ?>
  <div class="form-row"><label class="lbl" for="n">Name</label><input id="n" name="name" type="text" required maxlength="100" value="<?= e($old['name']) ?>"></div>
  <div class="form-row"><label class="lbl" for="e">Email</label><input id="e" name="email" type="email" required maxlength="150" value="<?= e($old['email']) ?>"></div>
  <button class="btn" type="submit">Save profile</button></form>
<form class="panel" method="post" action="" novalidate style="max-width:620px"><?= csrf_field() ?><input type="hidden" name="action" value="password"><h2>Change password</h2><?php render_errors($errW); ?>
  <div class="form-row"><label class="lbl" for="c">Current password</label><input id="c" name="current_password" type="password" required autocomplete="current-password"></div>
  <div class="form-grid"><div class="form-row"><label class="lbl" for="np">New password</label><input id="np" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
  <div class="form-row"><label class="lbl" for="cp">Confirm</label><input id="cp" name="confirm_password" type="password" required autocomplete="new-password"></div></div>
  <button class="btn" type="submit">Change password</button></form>
<?php admin_footer();
