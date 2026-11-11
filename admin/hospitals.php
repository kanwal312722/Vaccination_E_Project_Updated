<?php
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);

if (is_post()) {
    $action = post('action');
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'toggle') {
        q('UPDATE hospitals SET active = 1 - active WHERE id=?', [$id]);
        flash('ok', 'Hospital status updated.');
        redirect('admin/hospitals.php');
    }
    if ($action === 'delete') {
        if (val('SELECT COUNT(*) FROM appointments WHERE hospital_id=?', [$id]) || val('SELECT COUNT(*) FROM vaccination_records WHERE hospital_id=?', [$id])) {
            flash('error', 'This hospital has appointments or records, so it cannot be deleted. Deactivate it instead.');
        } else {
            $uid = val('SELECT user_id FROM hospitals WHERE id=?', [$id]);
            q('DELETE FROM hospitals WHERE id=?', [$id]);
            if ($uid) q("DELETE FROM users WHERE id=? AND role='hospital'", [$uid]);
            flash('ok', 'Hospital deleted.');
        }
        redirect('admin/hospitals.php');
    }
    if ($action === 'save') {
        $name = clean(post('name')); $addr = clean(post('address')); $city = clean(post('city')); $phone = post('phone');
        $email = strtolower(post('email')); $pass = (string)($_POST['password'] ?? '');
        if (!valid_name($name, 3, 120)) $errors['name'] = 'Enter the hospital name (3 to 120 characters).';
        elseif (val('SELECT id FROM hospitals WHERE LOWER(name)=? AND id<>?', [mb_strtolower($name), $id])) $errors['name'] = 'A hospital with this name already exists.';
        if (mb_strlen($addr) < 5 || mb_strlen($addr) > 200) $errors['address'] = 'Enter the full address (5 to 200 characters).';
        if (!valid_name($city, 2, 80)) $errors['city'] = 'Enter the city.';
        if (!valid_phone($phone)) $errors['phone'] = 'Phone can use digits, + and - (7 to 20 characters).';
        if (!$id && ($email !== '' || $pass !== '')) {   // optional login for a new hospital
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email or leave both login fields empty.';
            elseif (val('SELECT id FROM users WHERE email=?', [$email])) $errors['email'] = 'This email is already registered.';
            if ($e = password_error($pass)) $errors['password'] = $e;
        }
        if (!$errors) {
            $pdo = db(); $pdo->beginTransaction();
            try {
                if ($id) {
                    q('UPDATE hospitals SET name=?, address=?, city=?, phone=? WHERE id=?', [$name, $addr, $city, $phone ?: null, $id]);
                } else {
                    $uid = null;
                    if ($email !== '') {
                        q("INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,'hospital')", [$name, $email, $phone ?: null, password_hash($pass, PASSWORD_DEFAULT)]);
                        $uid = (int)$pdo->lastInsertId();
                    }
                    q('INSERT INTO hospitals (user_id,name,address,city,phone) VALUES (?,?,?,?,?)', [$uid, $name, $addr, $city, $phone ?: null]);
                }
                $pdo->commit();
            } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
            flash('ok', $id ? 'Hospital updated.' : 'Hospital added.');
            redirect('admin/hospitals.php');
        }
        $editId = $id;
    }
}
$edit = $editId ? row('SELECT * FROM hospitals WHERE id=?', [$editId]) : null;
$list = rows('SELECT * FROM hospitals ORDER BY name');
$err = fn($k) => isset($errors[$k]) ? '<span class="help" style="color:var(--bad)">' . e($errors[$k]) . '</span>' : '';
page_header('Hospitals');
?>
<div class="grid">
<div class="card"><h3>All hospitals</h3>
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Name</th><th>Address</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($list as $h): ?>
  <tr><td><?= e($h['name']) ?><div class="muted xs"><?= e($h['phone']) ?><?= $h['user_id'] ? ' · has login' : '' ?></div></td><td><?= e($h['address'] . ', ' . $h['city']) ?></td><td><?= $h['active'] ? '<span class="pill available">Active</span>' : pill('inactive') ?></td>
  <td><div class="acts"><a class="btn sm ghost" href="?edit=<?= (int)$h['id'] ?>">Edit</a>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn sm ghost"><?= $h['active'] ? 'Deactivate' : 'Activate' ?></button></form>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn sm no" onclick="return confirm('Delete this hospital?')">Delete</button></form></div></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php else: ?><div class="empty">No hospitals yet.</div><?php endif; ?></div>
<div class="card"><h3><?= $edit ? 'Edit hospital' : 'Add hospital' ?></h3>
<form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <label>Name<input name="name" required maxlength="120" value="<?= old('name', $edit['name'] ?? '') ?>"><?= $err('name') ?></label>
  <label>Address<input name="address" required maxlength="200" value="<?= old('address', $edit['address'] ?? '') ?>"><?= $err('address') ?></label>
  <div class="row2"><label>City<input name="city" required maxlength="80" value="<?= old('city', $edit['city'] ?? 'Karachi') ?>"><?= $err('city') ?></label>
  <label>Phone<input name="phone" maxlength="20" value="<?= old('phone', $edit['phone'] ?? '') ?>"><?= $err('phone') ?></label></div>
  <?php if (!$edit): ?><p class="muted xs">Optional: give the hospital a login so it can update vaccine status.</p>
  <div class="row2"><label>Login email<input type="email" name="email" value="<?= old('email') ?>"><?= $err('email') ?></label>
  <label>Password<input type="password" name="password" autocomplete="new-password"><?= $err('password') ?></label></div><?php endif; ?>
  <div class="acts"><button class="btn" type="submit">Save</button><?php if ($edit): ?><a class="btn ghost" href="<?= e(url('admin/hospitals.php')) ?>">Cancel</a><?php endif; ?></div>
</form></div>
</div>
<?php page_footer();
