<?php
require_once __DIR__ . '/includes/layout.php';
if (user()) { redirect(home_for(user()['role'])); }
$errors = [];
if (is_post()) {
    csrf_check();
    $role = post('role');
    $name = clean(post('name'));
    $email = strtolower(post('email'));
    $phone = post('phone');
    $pass = (string)($_POST['password'] ?? '');
    $hname = clean(post('hospital_name'));
    $addr = clean(post('address'));
    $city = clean(post('city'));

    if (!in_array($role, ['parent', 'hospital'], true)) $errors['role'] = 'Choose Parent or Hospital.';
    if (!valid_name($name)) $errors['name'] = 'Enter your full name (letters, 2 to 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $errors['email'] = 'Enter a valid email address.';
    elseif (val('SELECT id FROM users WHERE email=?', [$email])) $errors['email'] = 'This email is already registered.';
    if (!valid_phone($phone)) $errors['phone'] = 'Phone can use digits, + and - (7 to 20 characters).';
    if ($e = password_error($pass)) $errors['password'] = $e;
    elseif (!hash_equals($pass, (string)($_POST['confirm'] ?? ''))) $errors['confirm'] = 'Passwords do not match.';
    if ($role === 'hospital') {
        if (!valid_name($hname, 3, 120)) $errors['hospital_name'] = 'Enter the hospital name (3 to 120 characters).';
        elseif (val('SELECT id FROM hospitals WHERE LOWER(name)=?', [mb_strtolower($hname)])) $errors['hospital_name'] = 'A hospital with this name already exists.';
        if (mb_strlen($addr) < 5 || mb_strlen($addr) > 200) $errors['address'] = 'Enter the full address (5 to 200 characters).';
        if (!valid_name($city, 2, 80)) $errors['city'] = 'Enter the city.';
    }
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            q('INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)', [$name, $email, $phone ?: null, password_hash($pass, PASSWORD_DEFAULT), $role]);
            $uid = (int)$pdo->lastInsertId();
            if ($role === 'hospital') {
                q('INSERT INTO hospitals (user_id,name,address,city,phone) VALUES (?,?,?,?,?)', [$uid, $hname, $addr, $city, $phone ?: null]);
            }
            $pdo->commit();
        } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
        login_user($uid);
        flash('ok', 'Welcome! Your account is ready.');
        redirect(home_for($role));
    }
}
$err = fn($k) => isset($errors[$k]) ? '<span class="help" style="color:var(--bad)">' . e($errors[$k]) . '</span>' : '';
$sel = $_POST['role'] ?? (($_GET['role'] ?? '') === 'hospital' ? 'hospital' : 'parent');
page_header('');
?>
<div class="auth card" style="max-width:560px">
  <h2>Create an account</h2>
  <form method="post" class="stack" style="margin-top:12px" id="reg">
    <?= csrf_field() ?>
    <div class="row2">
      <label><span><input type="radio" name="role" value="parent" <?= $sel === 'parent' ? 'checked' : '' ?>> Parent</span></label>
      <label><span><input type="radio" name="role" value="hospital" <?= $sel === 'hospital' ? 'checked' : '' ?>> Hospital</span></label>
    </div><?= $err('role') ?>
    <label>Full name<input name="name" value="<?= old('name') ?>" required maxlength="100" autocomplete="name"><?= $err('name') ?></label>
    <div class="row2">
      <label>Email<input type="email" name="email" value="<?= old('email') ?>" required maxlength="150" autocomplete="email"><?= $err('email') ?></label>
      <label>Phone <span class="help">(optional)</span><input name="phone" value="<?= old('phone') ?>" maxlength="20" inputmode="tel"><?= $err('phone') ?></label>
    </div>
    <div id="hosp" <?= $sel === 'hospital' ? '' : 'hidden' ?> class="stack">
      <label>Hospital name<input name="hospital_name" value="<?= old('hospital_name') ?>" maxlength="120"><?= $err('hospital_name') ?></label>
      <div class="row2">
        <label>Address<input name="address" value="<?= old('address') ?>" maxlength="200"><?= $err('address') ?></label>
        <label>City<input name="city" value="<?= old('city', 'Karachi') ?>" maxlength="80"><?= $err('city') ?></label>
      </div>
    </div>
    <div class="row2">
      <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"><span class="help">At least 8 characters with letters and numbers.</span><?= $err('password') ?></label>
      <label>Confirm password<input type="password" name="confirm" required autocomplete="new-password"><?= $err('confirm') ?></label>
    </div>
    <button class="btn" type="submit">Create account</button>
  </form>
  <p class="muted xs" style="margin-top:14px">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<script>
document.querySelectorAll('#reg [name=role]').forEach(r=>r.addEventListener('change',()=>{document.getElementById('hosp').hidden=document.querySelector('#reg [name=role]:checked').value!=='hospital'}));
</script>
<?php page_footer();
