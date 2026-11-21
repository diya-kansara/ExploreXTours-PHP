<?php
require __DIR__ . '/_init.php';
require_super_admin();
$errors = []; $old = ['name' => '', 'email' => '', 'role' => 'admin'];

if (is_post()) {
    csrf_verify();
    $action = post_str('action', 10);
    if ($action === 'add') {
        $old = ['name' => post_str('name', 100), 'email' => post_str('email', 150), 'role' => post_str('role', 12) === 'super_admin' ? 'super_admin' : 'admin'];
        $pw = (string) ($_POST['password'] ?? '');
        if (mb_strlen($old['name']) < 2) $errors['name'] = 'Enter a name.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        if ($pp = password_problems($pw)) $errors['password'] = $pp[0];
        if (!$errors) {
            try {
                db_insert('INSERT INTO admins (name, email, password, role) VALUES (?,?,?,?)', 'ssss', [$old['name'], strtolower($old['email']), password_hash($pw, PASSWORD_DEFAULT), $old['role']]);
                flash('success', 'Administrator ' . $old['name'] . ' was created.'); redirect('admin/admins.php');
            } catch (mysqli_sql_exception $e) { if ((int) $e->getCode() !== 1062) throw $e; $errors['email'] = 'An administrator with this email already exists.'; }
        }
    } elseif ($action === 'delete') {
        $id = post_int('id');
        $supers = (int) db_value("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'");
        $t = db_one('SELECT id, name, role FROM admins WHERE id = ?', 'i', [$id]);
        if (!$t) abort(404, 'Administrator not found.');
        if ($id === (int) $admin['id']) flash('error', 'You cannot delete your own account.');
        elseif ($t['role'] === 'super_admin' && $supers <= 1) flash('error', 'You cannot delete the last super administrator.');
        else { db_exec('DELETE FROM admins WHERE id = ?', 'i', [$id]); flash('success', 'Administrator ' . $t['name'] . ' was removed.'); }
        redirect('admin/admins.php');
    }
}
$rows = db_all('SELECT id, name, email, role, last_login_at, created_at FROM admins ORDER BY id');
admin_header('Administrators', 'admins', $admin);
?>
<div class="demo-banner"><span>🔒</span><span>Public admin registration has been removed. Administrators can only be created here by a super administrator, or with <code>php scripts/create_admin.php</code> on the server.</span></div>
<div class="table-wrap" style="margin-bottom:24px"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last sign-in</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $a): ?><tr><td><strong><?= e($a['name']) ?></strong><?= (int) $a['id'] === (int) $admin['id'] ? ' <span class="badge badge-info">You</span>' : '' ?></td><td><?= e($a['email']) ?></td>
  <td><?= $a['role'] === 'super_admin' ? 'Super admin' : 'Administrator' ?></td><td><?= e(fmt_date($a['last_login_at'], 'd M Y, H:i')) ?></td>
  <td class="actions"><?php if ((int) $a['id'] !== (int) $admin['id']) echo admin_action_form(url('admin/admins.php'), ['id' => $a['id'], 'action' => 'delete'], 'Remove', 'btn btn-sm btn-ghost', 'Remove ' . $a['name'] . '?'); ?></td></tr><?php endforeach; ?></tbody></table></div>
<form class="panel" method="post" action="" novalidate style="max-width:620px"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h2>Add administrator</h2>
  <?php render_errors($errors); ?>
  <div class="form-row"><label class="lbl" for="n">Full name</label><input id="n" name="name" type="text" required maxlength="100" value="<?= e($old['name']) ?>"></div>
  <div class="form-row"><label class="lbl" for="e">Email</label><input id="e" name="email" type="email" required maxlength="150" value="<?= e($old['email']) ?>"></div>
  <div class="form-grid"><div class="form-row"><label class="lbl" for="p">Temporary password</label><input id="p" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
  <div class="form-row"><label class="lbl" for="r">Role</label><select id="r" name="role"><option value="admin">Administrator</option><option value="super_admin" <?= $old['role'] === 'super_admin' ? 'selected' : '' ?>>Super admin</option></select></div></div>
  <button class="btn" type="submit">Create administrator</button></form>
<?php admin_footer();
