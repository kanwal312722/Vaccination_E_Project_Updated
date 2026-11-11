<?php
require_once __DIR__ . '/includes/layout.php';
if (user()) { redirect(home_for(user()['role'])); }
$error = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post('email'));
    $pass = (string)($_POST['password'] ?? '');
    // Simple throttle: 5 failures, then wait 60 seconds.
    $t = $_SESSION['login_fail'] ?? ['n' => 0, 'at' => 0];
    if ($t['n'] >= 5 && time() - $t['at'] < 60) {
        $error = 'Too many attempts. Wait a minute and try again.';
    } else {
        $u = row('SELECT id, password_hash, role FROM users WHERE email=?', [$email]);
        // Always run password_verify so the response time does not reveal whether the email exists.
        $hash = $u['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        if (password_verify($pass, $hash) && $u) {
            unset($_SESSION['login_fail']);
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
            }
            login_user((int)$u['id']);
            redirect(home_for($u['role']));
        }
        $_SESSION['login_fail'] = ['n' => $t['n'] + 1, 'at' => time()];
        $error = 'Email or password is incorrect.';
    }
}
page_header('');
?>
<div class="auth card">
  <h2>Log in</h2>
  <?php if ($error): ?><div class="flash error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="stack" style="margin-top:12px">
    <?= csrf_field() ?>
    <label>Email<input type="email" name="email" value="<?= old('email') ?>" required autocomplete="username"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p class="muted xs" style="margin-top:14px">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
</div>
<?php page_footer();
