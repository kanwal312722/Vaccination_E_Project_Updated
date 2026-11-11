<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/schedule.php';

/* ---------- role pages: tabs, copy and live counts ---------- */
function role_tabs(string $role): array {
    return match ($role) {
        'admin' => ['admin/requests.php' => 'Requests', 'admin/children.php' => 'Children', 'admin/vaccines.php' => 'Vaccines',
                    'admin/hospitals.php' => 'Hospitals', 'admin/reports.php' => 'Reports', 'admin/bookings.php' => 'Bookings'],
        'parent' => ['parent/dashboard.php' => 'Overview', 'parent/children.php' => 'My children', 'parent/book.php' => 'Book',
                     'parent/bookings.php' => 'Bookings', 'parent/report.php' => 'Reports', 'parent/profile.php' => 'Profile'],
        'hospital' => ['hospital/dashboard.php' => 'Appointments', 'hospital/profile.php' => 'Profile'],
        default => [],
    };
}

function role_meta(string $role): array {
    return [
        'parent'   => ['Step 1 of 3', 'Parent', 'See what is due for each child, book a hospital and track every dose.'],
        'admin'    => ['Step 2 of 3', 'Admin', 'Approve requests, manage vaccines and hospitals, and read the reports.'],
        'hospital' => ['Step 3 of 3', 'Hospital', 'Receive approved appointments and record each dose.'],
    ][$role];
}

// pend = requests waiting for the admin, wait = approved and waiting for the hospital, done = recorded doses
function role_counts(string $role, array $u): array {
    static $c = [];
    if (isset($c[$role])) return $c[$role];
    if ($role === 'admin') {
        $r = ['pend' => val("SELECT COUNT(*) FROM appointments WHERE status='pending'"),
              'wait' => val("SELECT COUNT(*) FROM appointments WHERE status='approved' AND result='pending'"),
              'done' => val('SELECT COUNT(*) FROM vaccination_records')];
    } elseif ($role === 'parent') {
        $r = ['pend' => val("SELECT COUNT(*) FROM appointments a JOIN children c ON c.id=a.child_id WHERE c.parent_id=? AND a.status='pending'", [$u['id']]),
              'wait' => val("SELECT COUNT(*) FROM appointments a JOIN children c ON c.id=a.child_id WHERE c.parent_id=? AND a.status='approved' AND a.result='pending'", [$u['id']]),
              'done' => val('SELECT COUNT(*) FROM vaccination_records r JOIN children c ON c.id=r.child_id WHERE c.parent_id=?', [$u['id']])];
    } else {
        $hid = val('SELECT id FROM hospitals WHERE user_id=?', [$u['id']]) ?: 0;
        $r = ['pend' => 0,
              'wait' => val("SELECT COUNT(*) FROM appointments WHERE hospital_id=? AND status='approved' AND result='pending'", [$hid]),
              'done' => val('SELECT COUNT(*) FROM vaccination_records WHERE hospital_id=?', [$hid])];
    }
    return $c[$role] = array_map('intval', $r);
}

function self_path(): string {
    $p = trim(str_replace('\\', '/', substr($_SERVER['SCRIPT_NAME'], strlen(BASE_URL))), '/');
    return $p === 'admin/dashboard.php' ? 'admin/requests.php' : $p;
}

function next_bar(string $role, array $u): string {
    $n = role_counts($role, $u);
    $t = ''; $l = ''; $h = '';
    if ($role === 'parent') {
        if ($n['pend'])      { $t = $n['pend'] . ($n['pend'] > 1 ? ' requests are' : ' request is') . ' waiting for the admin to approve.'; $l = 'View bookings →'; $h = 'parent/bookings.php'; }
        elseif ($n['wait'])  { $t = 'Approved. The hospital will record the dose.'; $l = 'View bookings →'; $h = 'parent/bookings.php'; }
        elseif ($n['done'])  { $t = 'A dose was recorded. It shows in your report.'; $l = 'Open the report →'; $h = 'parent/report.php'; }
        else                 { $t = 'Book a vaccine to start the workflow.'; $l = 'Book now →'; $h = 'parent/book.php'; }
    } elseif ($role === 'admin') {
        if ($n['pend'])      { $t = $n['pend'] . ($n['pend'] > 1 ? ' requests need' : ' request needs') . ' a decision in the Requests tab.'; $l = 'Review requests →'; $h = 'admin/requests.php'; }
        elseif ($n['wait'])  { $t = 'Approved. Next the hospital marks the dose.'; $l = 'View bookings →'; $h = 'admin/bookings.php?status=approved'; }
        elseif ($n['done'])  { $t = 'All requests are handled. Check the Reports tab.'; $l = 'Open reports →'; $h = 'admin/reports.php'; }
        else                 { $t = 'No requests yet. Parents can book from their dashboard.'; }
    } else {
        if ($n['wait'])      { $t = $n['wait'] . ($n['wait'] > 1 ? ' appointments are' : ' appointment is') . ' waiting for you.'; $l = 'Open appointments →'; $h = 'hospital/dashboard.php'; }
        elseif ($n['done'])  { $t = 'Dose recorded. The parent and admin reports are updated.'; }
        else                 { $t = 'Nothing approved yet. Approved requests from the admin appear here.'; }
    }
    $link = ($l && $h && ltrim(strtok($h, '?'), '/') !== self_path()) ? '<a class="btn pri" href="' . e(url($h)) . '">' . e($l) . '</a>' : '';
    return '<div class="next"><span>' . e($t) . '</span>' . $link . '</div>';
}

/* ---------- page frame ---------- */
// Adds the file's modified time to the URL so browsers never show an old stylesheet or script.
function asset(string $p): string { $f = ROOT . '/' . $p; return url($p) . (is_file($f) ? '?v=' . filemtime($f) : ''); }

const LOGO = '<svg width="30" height="30" viewBox="0 0 32 32" aria-hidden="true"><path d="M16 2 28 6v9c0 7.5-4.8 12.6-12 15C8.8 27.6 4 22.5 4 15V6z" fill="var(--primary)"/><path d="M16 9v12M10 15h12" stroke="var(--accent)" stroke-width="3.4" stroke-linecap="round" fill="none"/></svg>';

function page_header(string $title, ?string $unused = null): void {
    $u = user();
    $self = self_path();
    $role = $u['role'] ?? null;
    $isRole = $role && preg_match('#^(admin|parent|hospital)/#', $self);
    $roleHome = $role ? home_for($role) : '';
    $badge = ($role === 'admin') ? role_counts('admin', $u)['pend'] : (($role === 'hospital') ? role_counts('hospital', $u)['wait'] : 0);
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title !== '' ? $title . ' · ' . APP_NAME : APP_NAME) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
<script>try{var t=localStorage.getItem('vms-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head><body>
<div class="wrap">
  <div class="top">
    <a class="brand" href="<?= e(url('index.php')) ?>"><?= LOGO ?><span><?= e(APP_NAME) ?></span></a>
    <nav aria-label="Main">
      <a href="<?= e(url('index.php')) ?>"<?= $self === 'index.php' || $self === '' ? ' aria-current="page"' : '' ?>>Home</a>
      <a href="<?= e(url('how.php')) ?>"<?= $self === 'how.php' ? ' aria-current="page"' : '' ?>>How it works</a>
      <?php if ($u): ?>
        <a href="<?= e(url($roleHome)) ?>"<?= $isRole ? ' aria-current="page"' : '' ?>><?= e(ucfirst($role)) ?><?php if ($badge): ?><span class="badge"><?= (int)$badge ?></span><?php endif; ?></a>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="inline"><?= csrf_field() ?><button class="link" type="submit">Log out</button></form>
      <?php else: ?>
        <a href="<?= e(url('login.php')) ?>"<?= $self === 'login.php' ? ' aria-current="page"' : '' ?>>Log in</a>
        <a href="<?= e(url('register.php')) ?>"<?= $self === 'register.php' ? ' aria-current="page"' : '' ?>>Register</a>
      <?php endif; ?>
      <button class="theme" id="theme" type="button" aria-label="Toggle dark mode"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z"/></svg></button>
    </nav>
  </div>
<?php
    if ($isRole) {
        [$kicker, $name, $sub] = role_meta($role);
        ?>
  <header class="pagehead"><div class="kicker"><?= e($kicker) ?></div><h1><?= e($name) ?></h1><p class="sub"><?= e($sub) ?></p>
    <ol class="steps" aria-label="Workflow"><?php foreach (['parent' => '1. Parent', 'admin' => '2. Admin', 'hospital' => '3. Hospital'] as $k => $l): ?><li><span<?= $k === $role ? ' aria-current="step"' : '' ?>><?= e($l) ?></span></li><?php endforeach; ?></ol>
  </header>
  <div class="panelbox">
    <?php foreach (flashes() as [$type, $msg]): ?><div class="flash <?= e($type) ?>" role="status"><?= e($msg) ?></div><?php endforeach; ?>
    <?php if ($role === 'admin'): ?>
      <div class="stats">
        <div class="stat"><b class="num"><?= (int)val('SELECT COUNT(*) FROM children') ?></b><span>Children</span></div>
        <div class="stat"><b class="num"><?= (int)val('SELECT COUNT(*) FROM hospitals WHERE active=1') ?></b><span>Hospitals</span></div>
        <div class="stat"><b class="num"><?= role_counts('admin', $u)['pend'] ?></b><span>Pending requests</span></div>
        <div class="stat"><b class="num"><?= (int)val("SELECT COUNT(*) FROM vaccination_records WHERE status='vaccinated'") ?></b><span>Doses given</span></div>
      </div>
    <?php endif; ?>
    <nav class="tabs" aria-label="<?= e(ucfirst($role)) ?> sections"><?php foreach (role_tabs($role) as $href => $label): ?><a href="<?= e(url($href)) ?>"<?= $self === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?></nav>
<?php } else {
        foreach (flashes() as [$type, $msg]): ?><div class="flash <?= e($type) ?>" role="status"><?= e($msg) ?></div><?php endforeach;
    }
}

function page_footer(): void {
    $u = user();
    $self = self_path();
    $isRole = $u && preg_match('#^(admin|parent|hospital)/#', $self);
    if ($isRole) { echo '</div>' . next_bar($u['role'], $u); }
    ?>
  <footer class="foot">
    <nav aria-label="Footer"><a href="<?= e(url('index.php')) ?>">Home</a><a href="<?= e(url('how.php')) ?>">How it works</a>
      <?php if ($u): ?><a href="<?= e(url(home_for($u['role']))) ?>"><?= e(ucfirst($u['role'])) ?></a><?php else: ?><a href="<?= e(url('login.php')) ?>">Log in</a><a href="<?= e(url('register.php')) ?>">Register</a><?php endif; ?></nav>
    <span><?= e(APP_NAME) ?> · Pakistan EPI schedule</span>
  </footer>
</div>
<script src="<?= e(asset('assets/app.js')) ?>"></script>
</body></html>
<?php }
