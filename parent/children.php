<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('parent');
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

if (is_post()) {
    $action = post('action');
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'delete') {
        if (child_of_parent($id, (int)$u['id'])) { q('DELETE FROM children WHERE id=? AND parent_id=?', [$id, $u['id']]); flash('ok', 'Child removed.'); }
        redirect('parent/children.php');
    }
    if ($action === 'save') {
        $name = clean(post('name')); $dob = post('dob'); $gender = post('gender'); $bg = post('blood_group'); $guard = clean(post('guardian_name'));
        if ($id && !child_of_parent($id, (int)$u['id'])) { http_response_code(403); exit('Not your child.'); }
        if (!valid_name($name)) $errors['name'] = 'Enter the child\'s name (2 to 100 characters).';
        if (!valid_date($dob)) $errors['dob'] = 'Enter a valid date of birth.';
        elseif ($dob > date('Y-m-d')) $errors['dob'] = 'Date of birth cannot be in the future.';
        elseif ($dob < date('Y-m-d', strtotime('-15 years'))) $errors['dob'] = 'This system is for children up to 15 years old.';
        if (!in_array($gender, ['Boy', 'Girl'], true)) $errors['gender'] = 'Choose Boy or Girl.';
        if ($bg !== '' && !in_array($bg, $groups, true)) $errors['blood_group'] = 'Choose a blood group from the list.';
        if (!valid_name($guard)) $errors['guardian_name'] = 'Enter the guardian\'s name.';
        if (!$errors) {
            if ($id) q('UPDATE children SET name=?, dob=?, gender=?, blood_group=?, guardian_name=? WHERE id=? AND parent_id=?', [$name, $dob, $gender, $bg ?: null, $guard, $id, $u['id']]);
            else q('INSERT INTO children (parent_id,name,dob,gender,blood_group,guardian_name) VALUES (?,?,?,?,?,?)', [$u['id'], $name, $dob, $gender, $bg ?: null, $guard]);
            flash('ok', $id ? 'Child details updated.' : 'Child added.');
            redirect('parent/children.php');
        }
        $editId = $id;
    }
}
$edit = $editId ? child_of_parent($editId, (int)$u['id']) : null;
$list = rows('SELECT * FROM children WHERE parent_id=? ORDER BY dob DESC', [$u['id']]);
$err = fn($k) => isset($errors[$k]) ? '<span class="help" style="color:var(--bad)">' . e($errors[$k]) . '</span>' : '';
$g = $_POST['gender'] ?? ($edit['gender'] ?? '');
page_header('My children');
?>
<div class="grid">
<div class="card"><h3>Registered children</h3>
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Name</th><th>Born</th><th>Blood</th><th></th></tr></thead><tbody>
<?php foreach ($list as $c): ?><tr><td><?= e($c['name']) ?><div class="muted xs"><?= e($c['gender']) ?> · <?= e(age_text($c['dob'])) ?></div></td><td><?= e(fmt_date($c['dob'])) ?></td><td><?= e($c['blood_group'] ?: '-') ?></td>
<td><div class="acts"><a class="btn sm ghost" href="?edit=<?= (int)$c['id'] ?>">Edit</a>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn sm no" onclick="return confirm('Remove this child and all their records?')">Remove</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty">No children yet. Add one on the right.</div><?php endif; ?></div>
<div class="card"><h3><?= $edit ? 'Edit child' : 'Add a child' ?></h3>
<form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <label>Child's name<input name="name" required maxlength="100" value="<?= old('name', $edit['name'] ?? '') ?>"><?= $err('name') ?></label>
  <div class="row2"><label>Date of birth<input type="date" name="dob" required max="<?= date('Y-m-d') ?>" value="<?= old('dob', $edit['dob'] ?? '') ?>"><?= $err('dob') ?></label>
  <label>Gender<select name="gender" required><option value="">Choose</option><?php foreach (['Boy', 'Girl'] as $o): ?><option <?= $g === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select><?= $err('gender') ?></label></div>
  <div class="row2"><label>Blood group <span class="help">(optional)</span><select name="blood_group"><option value="">Not known</option><?php $b = $_POST['blood_group'] ?? ($edit['blood_group'] ?? ''); foreach ($groups as $o): ?><option <?= $b === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select><?= $err('blood_group') ?></label>
  <label>Guardian's name<input name="guardian_name" required maxlength="100" value="<?= old('guardian_name', $edit['guardian_name'] ?? $u['name']) ?>"><?= $err('guardian_name') ?></label></div>
  <div class="acts"><button class="btn" type="submit">Save</button><?php if ($edit): ?><a class="btn ghost" href="<?= e(url('parent/children.php')) ?>">Cancel</a><?php endif; ?></div>
</form></div>
</div>
<?php page_footer();
