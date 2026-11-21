<?php
/** Shared add / edit form handler. Call handle_package_form(null) to add, handle_package_form($id) to edit. */
function handle_package_form(?int $id): void
{
    global $admin;
    $editing = $id !== null;
    $pkg = $editing ? db_one('SELECT * FROM packages WHERE id = ?', 'i', [$id]) : null;
    if ($editing && !$pkg) { abort(404, 'Package not found.'); }

    $cats = db_all('SELECT id, name FROM categories ORDER BY sort_order');
    $catIds = array_map('intval', array_column($cats, 'id'));

    if ($editing) {
        $f = $pkg;
        $f['days'] = db_all('SELECT day_number, title, description FROM itinerary_days WHERE package_id = ? ORDER BY day_number', 'i', [$id]);
        $f['hotels'] = db_all('SELECT name, city, star_rating, description FROM hotels WHERE package_id = ? ORDER BY sort_order, id', 'i', [$id]);
        $inc = db_all('SELECT item, type FROM package_inclusions WHERE package_id = ? ORDER BY sort_order, id', 'i', [$id]);
        $f['inclusions'] = implode("\n", array_column(array_filter($inc, fn($r) => $r['type'] === 'inclusion'), 'item'));
        $f['exclusions'] = implode("\n", array_column(array_filter($inc, fn($r) => $r['type'] === 'exclusion'), 'item'));
        $gallery = db_all('SELECT id, image FROM package_images WHERE package_id = ? ORDER BY sort_order, id', 'i', [$id]);
    } else {
        $f = ['title' => '', 'location' => '', 'category_id' => '', 'price' => '', 'duration_days' => 4, 'max_guests' => 10, 'description' => '', 'overview' => '',
              'is_featured' => 0, 'is_active' => 1, 'days' => [], 'hotels' => [], 'inclusions' => '', 'exclusions' => '', 'image' => ''];
        $gallery = [];
    }
    $errors = [];

    if (is_post()) {
        csrf_verify();
        $f['title'] = post_str('title', 255); $f['location'] = post_str('location', 255);
        $f['category_id'] = post_int('category_id'); $f['price'] = post_str('price', 12);
        $f['duration_days'] = post_int('duration_days'); $f['max_guests'] = post_int('max_guests');
        $f['description'] = post_str('description', 500); $f['overview'] = post_str('overview', 5000);
        $f['is_featured'] = isset($_POST['is_featured']) ? 1 : 0; $f['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $f['inclusions'] = post_str('inclusions', 3000); $f['exclusions'] = post_str('exclusions', 3000);

        $days = [];
        foreach ((array) ($_POST['day_title'] ?? []) as $i => $t) {
            $t = is_string($t) ? mb_substr(trim($t), 0, 200) : '';
            $d = is_string($_POST['day_desc'][$i] ?? null) ? mb_substr(trim($_POST['day_desc'][$i]), 0, 2000) : '';
            if ($t !== '') { $days[] = ['title' => $t, 'description' => $d]; }
        }
        $hotels = [];
        foreach ((array) ($_POST['hotel_name'] ?? []) as $i => $n) {
            $n = is_string($n) ? mb_substr(trim($n), 0, 150) : '';
            if ($n === '') continue;
            $star = is_string($_POST['hotel_star'][$i] ?? null) && preg_match('/^[1-5]$/', $_POST['hotel_star'][$i]) ? (int) $_POST['hotel_star'][$i] : null;
            $hotels[] = ['name' => $n, 'city' => is_string($_POST['hotel_city'][$i] ?? null) ? mb_substr(trim($_POST['hotel_city'][$i]), 0, 100) : '',
                         'star' => $star, 'description' => is_string($_POST['hotel_desc'][$i] ?? null) ? mb_substr(trim($_POST['hotel_desc'][$i]), 0, 1000) : ''];
        }
        $f['days'] = $days; $f['hotels'] = array_map(fn($h) => ['name' => $h['name'], 'city' => $h['city'], 'star_rating' => $h['star'], 'description' => $h['description']], $hotels);

        if ($f['title'] === '')                                           $errors['title'] = 'Title is required.';
        if ($f['location'] === '')                                        $errors['location'] = 'Destination is required.';
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', (string) $f['price']) || (float) $f['price'] <= 0) $errors['price'] = 'Enter a valid price greater than zero.';
        if ($f['duration_days'] < 1 || $f['duration_days'] > 60)          $errors['duration_days'] = 'Duration must be 1 to 60 days.';
        if ($f['max_guests'] < 1 || $f['max_guests'] > 50)                $errors['max_guests'] = 'Max guests must be 1 to 50.';
        if ($f['category_id'] !== 0 && !in_array($f['category_id'], $catIds, true)) $errors['category_id'] = 'Choose a valid category.';
        if (mb_strlen($f['description']) < 10)                            $errors['description'] = 'Add a short description (at least 10 characters).';
        if (count($days) > 60)                                            $errors['days'] = 'Too many itinerary days.';

        // images: validate and store before touching the database
        [$mainName, $imgErr] = save_package_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
        if ($imgErr) { $errors['image'] = $imgErr; }
        if (!$editing && !$mainName && !isset($errors['image'])) { $errors['image'] = 'Please upload a main image.'; }
        $newGallery = [];
        foreach (array_slice(files_list('gallery'), 0, 6) as $file) {
            [$gname, $gerr] = save_package_image($file);
            if ($gerr) { $errors['gallery'] = $gerr; } elseif ($gname) { $newGallery[] = $gname; }
        }

        if ($errors) {                       // do not leave orphan files behind
            delete_package_image($mainName);
            foreach ($newGallery as $g) { delete_package_image($g); }
        } else {
            $cat = $f['category_id'] ?: null;
            $nights = max(0, (int) $f['duration_days'] - 1);
            $label = $f['duration_days'] . ' Day' . ($f['duration_days'] > 1 ? 's' : '') . ' / ' . $nights . ' Night' . ($nights !== 1 ? 's' : '');
            $oldImage = $pkg['image'] ?? '';
            $removeIds = array_map('intval', (array) ($_POST['remove_gallery'] ?? []));
            $removedFiles = [];

            $savedId = db_transaction(function () use ($editing, $id, $f, $cat, $label, $mainName, $hotels, $days, $newGallery, $removeIds, &$removedFiles) {
                $price = (float) $f['price'];
                if ($editing) {
                    db_exec('UPDATE packages SET title=?, location=?, category_id=?, price=?, duration=?, duration_days=?, max_guests=?, description=?, overview=?, is_featured=?, is_active=? WHERE id=?',
                        'ssidsiissiii', [$f['title'], $f['location'], $cat, $price, $label, $f['duration_days'], $f['max_guests'], $f['description'], $f['overview'] ?: null, $f['is_featured'], $f['is_active'], $id]);
                    if ($mainName) { db_exec('UPDATE packages SET image = ? WHERE id = ?', 'si', [$mainName, $id]); }
                    $pid = $id;
                } else {
                    $pid = db_insert('INSERT INTO packages (title, location, category_id, price, duration, duration_days, max_guests, description, overview, image, is_featured, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                        'ssidsiisssii', [$f['title'], $f['location'], $cat, $price, $label, $f['duration_days'], $f['max_guests'], $f['description'], $f['overview'] ?: null, $mainName, $f['is_featured'], $f['is_active']]);
                }
                db_exec('DELETE FROM itinerary_days WHERE package_id = ?', 'i', [$pid]);
                foreach ($days as $i => $d) {
                    db_exec('INSERT INTO itinerary_days (package_id, day_number, title, description) VALUES (?,?,?,?)', 'iiss', [$pid, $i + 1, $d['title'], $d['description'] !== '' ? $d['description'] : null]);
                }
                db_exec('DELETE FROM hotels WHERE package_id = ?', 'i', [$pid]);
                foreach ($hotels as $i => $h) {
                    db_exec('INSERT INTO hotels (package_id, name, city, star_rating, description, sort_order) VALUES (?,?,?,?,?,?)', 'issisi',
                        [$pid, $h['name'], $h['city'] !== '' ? $h['city'] : null, $h['star'], $h['description'] !== '' ? $h['description'] : null, $i]);
                }
                db_exec('DELETE FROM package_inclusions WHERE package_id = ?', 'i', [$pid]);
                foreach (['inclusion' => $f['inclusions'], 'exclusion' => $f['exclusions']] as $type => $text) {
                    $n = 0;
                    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
                        $line = mb_substr(trim($line), 0, 255);
                        if ($line !== '') { db_exec('INSERT INTO package_inclusions (package_id, item, type, sort_order) VALUES (?,?,?,?)', 'issi', [$pid, $line, $type, $n++]); }
                    }
                }
                foreach ($removeIds as $rid) {
                    $row = db_one('SELECT image FROM package_images WHERE id = ? AND package_id = ?', 'ii', [$rid, $pid]);
                    if ($row) { db_exec('DELETE FROM package_images WHERE id = ?', 'i', [$rid]); $removedFiles[] = $row['image']; }
                }
                $order = (int) db_value('SELECT COALESCE(MAX(sort_order), 0) FROM package_images WHERE package_id = ?', 'i', [$pid]);
                foreach ($newGallery as $g) { db_exec('INSERT INTO package_images (package_id, image, sort_order) VALUES (?,?,?)', 'isi', [$pid, $g, ++$order]); }
                return $pid;
            });
            if ($mainName && $oldImage) { delete_package_image($oldImage); }
            foreach ($removedFiles as $rf) { delete_package_image($rf); }
            flash('success', $editing ? 'Package updated.' : 'Package created.');
            redirect('admin/edit_package.php?id=' . $savedId);
        }
    }

    admin_header($editing ? 'Edit package' : 'Add package', 'packages', $admin);
    $err = fn(string $k) => field_error($errors, $k);
    $inv = fn(string $k) => isset($errors[$k]) ? 'invalid' : '';
    ?>
<p><a href="<?= e(url('admin/manage_packages.php')) ?>">← All packages</a></p>
<?php render_errors($errors); ?>
<form method="post" action="" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <div class="panel"><h2>Basics</h2>
    <div class="form-row"><label class="lbl" for="title">Title</label><input class="<?= $inv('title') ?>" id="title" name="title" type="text" required maxlength="255" value="<?= e($f['title']) ?>"><?= $err('title') ?></div>
    <div class="form-grid">
      <div class="form-row"><label class="lbl" for="location">Destination</label><input class="<?= $inv('location') ?>" id="location" name="location" type="text" required maxlength="255" value="<?= e($f['location']) ?>"><?= $err('location') ?></div>
      <div class="form-row"><label class="lbl" for="category_id">Category</label><select class="<?= $inv('category_id') ?>" id="category_id" name="category_id"><option value="0">None</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $f['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?= $err('category_id') ?></div>
    </div>
    <div class="form-grid">
      <div class="form-row"><label class="lbl" for="price">Price per person (₹)</label><input class="<?= $inv('price') ?>" id="price" name="price" type="number" step="0.01" min="1" required value="<?= e($f['price']) ?>"><?= $err('price') ?></div>
      <div class="form-row"><label class="lbl" for="duration_days">Duration (days)</label><input class="<?= $inv('duration_days') ?>" id="duration_days" name="duration_days" type="number" min="1" max="60" required value="<?= e($f['duration_days']) ?>"><div class="hint">The label (e.g. “4 Days / 3 Nights”) is generated automatically.</div><?= $err('duration_days') ?></div>
    </div>
    <div class="form-grid">
      <div class="form-row"><label class="lbl" for="max_guests">Max guests per booking</label><input class="<?= $inv('max_guests') ?>" id="max_guests" name="max_guests" type="number" min="1" max="50" required value="<?= e($f['max_guests']) ?>"><?= $err('max_guests') ?></div>
      <div class="form-row"><span class="lbl">Visibility</span>
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= $f['is_active'] ? 'checked' : '' ?>> Visible to customers</label>
        <label class="check" style="margin-top:6px"><input type="checkbox" name="is_featured" value="1" <?= $f['is_featured'] ? 'checked' : '' ?>> Featured on the home page</label></div>
    </div>
    <div class="form-row"><label class="lbl" for="description">Short description <span class="muted">(shown on cards)</span></label><textarea class="<?= $inv('description') ?>" id="description" name="description" rows="2" maxlength="500" required><?= e($f['description']) ?></textarea><?= $err('description') ?></div>
    <div class="form-row"><label class="lbl" for="overview">Overview <span class="muted">(shown on the details page)</span></label><textarea id="overview" name="overview" rows="5" maxlength="5000"><?= e($f['overview']) ?></textarea></div>
  </div>

  <div class="panel"><h2>Images</h2>
    <div class="form-row"><label class="lbl" for="image">Main image <?= $editing ? '<span class="muted">(leave empty to keep current)</span>' : '' ?></label>
      <input class="<?= $inv('image') ?>" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" <?= $editing ? '' : 'required' ?>>
      <div class="hint">JPG, PNG or WebP, max <?= round(config('upload_max_bytes', 3145728) / 1048576, 1) ?> MB, at least 200×150 px. Files are re-encoded and renamed for safety.</div><?= $err('image') ?>
      <?php if ($editing && $f['image']): ?><div class="img-grid"><figure><img src="<?= e(pkg_image_url($f['image'])) ?>" alt="Current main image"><figcaption class="small muted">Current</figcaption></figure></div><?php endif; ?></div>
    <div class="form-row"><label class="lbl" for="gallery">Extra gallery images <span class="muted">(optional, up to 6)</span></label>
      <input class="<?= $inv('gallery') ?>" id="gallery" name="gallery[]" type="file" multiple accept="image/jpeg,image/png,image/webp"><?= $err('gallery') ?>
      <?php if ($gallery): ?><div class="img-grid"><?php foreach ($gallery as $g): ?><figure><img src="<?= e(pkg_image_url($g['image'])) ?>" alt=""><label><input type="checkbox" name="remove_gallery[]" value="<?= (int) $g['id'] ?>"> Remove</label></figure><?php endforeach; ?></div><?php endif; ?></div>
  </div>

  <div class="panel" data-repeat><h2>Day-by-day itinerary</h2>
    <div class="repeat-list"><?php foreach ($f['days'] as $i => $d): ?>
      <div class="repeat-row"><input type="number" data-day value="<?= $i + 1 ?>" readonly aria-label="Day number" style="background:var(--sand)">
        <input type="text" name="day_title[<?= $i ?>]" maxlength="200" placeholder="Title, e.g. Arrival and beach walk" value="<?= e($d['title']) ?>" aria-label="Day title">
        <input type="text" name="day_desc[<?= $i ?>]" maxlength="2000" placeholder="Details (optional)" value="<?= e($d['description'] ?? '') ?>" aria-label="Day details">
        <button type="button" class="btn btn-sm btn-ghost" data-remove>Remove</button></div><?php endforeach; ?></div>
    <template><div class="repeat-row"><input type="number" data-day value="" readonly aria-label="Day number" style="background:var(--sand)">
      <input type="text" name="day_title[0]" maxlength="200" placeholder="Title, e.g. Arrival and beach walk" aria-label="Day title">
      <input type="text" name="day_desc[0]" maxlength="2000" placeholder="Details (optional)" aria-label="Day details">
      <button type="button" class="btn btn-sm btn-ghost" data-remove>Remove</button></div></template>
    <button type="button" class="btn btn-sm btn-outline" data-add>+ Add day</button>
  </div>

  <div class="panel" data-repeat><h2>Hotels</h2>
    <div class="repeat-list"><?php foreach ($f['hotels'] as $i => $h): ?>
      <div class="repeat-row hotel"><input type="text" name="hotel_name[<?= $i ?>]" maxlength="150" placeholder="Hotel name" value="<?= e($h['name']) ?>" aria-label="Hotel name">
        <input type="text" name="hotel_city[<?= $i ?>]" maxlength="100" placeholder="City" value="<?= e($h['city'] ?? '') ?>" aria-label="City">
        <select name="hotel_star[<?= $i ?>]" aria-label="Stars"><option value="">★</option><?php for ($s = 1; $s <= 5; $s++): ?><option value="<?= $s ?>" <?= (int) ($h['star_rating'] ?? 0) === $s ? 'selected' : '' ?>><?= $s ?>★</option><?php endfor; ?></select>
        <input type="text" name="hotel_desc[<?= $i ?>]" maxlength="1000" placeholder="Short description" value="<?= e($h['description'] ?? '') ?>" aria-label="Hotel description">
        <button type="button" class="btn btn-sm btn-ghost" data-remove>Remove</button></div><?php endforeach; ?></div>
    <template><div class="repeat-row hotel"><input type="text" name="hotel_name[0]" maxlength="150" placeholder="Hotel name" aria-label="Hotel name">
      <input type="text" name="hotel_city[0]" maxlength="100" placeholder="City" aria-label="City">
      <select name="hotel_star[0]" aria-label="Stars"><option value="">★</option><?php for ($s = 1; $s <= 5; $s++): ?><option value="<?= $s ?>"><?= $s ?>★</option><?php endfor; ?></select>
      <input type="text" name="hotel_desc[0]" maxlength="1000" placeholder="Short description" aria-label="Hotel description">
      <button type="button" class="btn btn-sm btn-ghost" data-remove>Remove</button></div></template>
    <button type="button" class="btn btn-sm btn-outline" data-add>+ Add hotel</button>
  </div>

  <div class="panel"><h2>Inclusions &amp; exclusions</h2><div class="form-grid">
    <div class="form-row"><label class="lbl" for="inclusions">Included <span class="muted">(one per line)</span></label><textarea id="inclusions" name="inclusions" rows="7"><?= e($f['inclusions']) ?></textarea></div>
    <div class="form-row"><label class="lbl" for="exclusions">Not included <span class="muted">(one per line)</span></label><textarea id="exclusions" name="exclusions" rows="7"><?= e($f['exclusions']) ?></textarea></div></div></div>

  <div style="display:flex;gap:12px;margin-top:22px"><button class="btn btn-lg" type="submit"><?= $editing ? 'Save changes' : 'Create package' ?></button>
    <a class="btn btn-outline btn-lg" href="<?= e(url('admin/manage_packages.php')) ?>">Cancel</a></div>
</form>
<?php
    admin_footer();
}
