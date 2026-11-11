<?php
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);

if (is_post()) {
    $action = post('action');
    if ($action === 'toggle') {
        q('UPDATE vaccines SET available = 1 - available WHERE id=?', [(int)$_POST['id']]);
        flash('ok', 'Availability updated.');
        redirect('admin/vaccines.php');
    }
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = clean(post('name'));
        $weeks = post('age_weeks');
        $desc = mb_substr(clean(post('description')), 0, 255);
        if (!valid_name($name, 2, 80)) $errors['name'] = 'Enter the vaccine name (2 to 80 characters).';
        elseif (val('SELECT id FROM vaccines WHERE LOWER(name)=? AND id<>?', [mb_strtolower($name), $id])) $errors['name'] = 'This vaccine already exists.';
        if (!ctype_digit($weeks) || (int)$weeks > 936) $errors['age_weeks'] = 'Enter weeks after birth (0 to 936).';
        if (!$errors) {
            if ($id) q('UPDATE vaccines SET name=?, age_weeks=?, description=? WHERE id=?', [$name, (int)$weeks, $desc ?: null, $id]);
            else q('INSERT INTO vaccines (name, age_weeks, description) VALUES (?,?,?)', [$name, (int)$weeks, $desc ?: null]);
            flash('ok', $id ? 'Vaccine updated.' : 'Vaccine added.');
            redirect('admin/vaccines.php');
        }
        $editId = $id;
    }
}
$edit = $editId ? row('SELECT * FROM vaccines WHERE id=?', [$editId]) : null;
$list = rows('SELECT * FROM vaccines ORDER BY age_weeks, id');
$err = fn($k) => isset($errors[$k]) ? '<span class="help" style="color:var(--bad)">' . e($errors[$k]) . '</span>' : '';
page_header('Vaccines');
?>
<div class="grid">
<div class="card"><h3>Availability</h3><div class="tw"><table><thead><tr><th>Vaccine</th><th>Given at</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($list as $v): ?>
  <tr><td><?= e($v['name']) ?><div class="muted xs"><?= e($v['description']) ?></div></td><td><?= $v['age_weeks'] ? (int)$v['age_weeks'] . ' weeks' : 'Birth' ?></td>
  <td><?= pill($v['available'] ? 'available' : 'unavailable') ?></td>
  <td><div class="acts"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><button class="btn sm ghost">Mark <?= $v['available'] ? 'unavailable' : 'available' ?></button></form>
  <a class="btn sm ghost" href="?edit=<?= (int)$v['id'] ?>">Edit</a></div></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<div class="card"><h3><?= $edit || $editId ? 'Edit vaccine' : 'Add vaccine' ?></h3>
<form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <label>Name<input name="name" required maxlength="80" value="<?= old('name', $edit['name'] ?? '') ?>"><?= $err('name') ?></label>
  <label>Given at (weeks after birth)<input name="age_weeks" type="number" min="0" max="936" required value="<?= old('age_weeks', (string)($edit['age_weeks'] ?? '')) ?>"><?= $err('age_weeks') ?></label>
  <label>Description<input name="description" maxlength="255" value="<?= old('description', $edit['description'] ?? '') ?>"></label>
  <div class="acts"><button class="btn" type="submit">Save</button><?php if ($edit): ?><a class="btn ghost" href="<?= e(url('admin/vaccines.php')) ?>">Cancel</a><?php endif; ?></div>
</form></div>
</div>
<?php page_footer();
