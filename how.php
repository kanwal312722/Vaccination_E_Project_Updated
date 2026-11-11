<?php
require_once __DIR__ . '/includes/layout.php';
$u = user();
$vaccines = rows('SELECT name, age_weeks FROM vaccines ORDER BY age_weeks, id');
page_header('How it works');
?>
<header class="pagehead"><div class="kicker">How it works</div><h1>From a due date to a recorded dose</h1><p class="sub">Each step has one owner, so nothing waits on a phone call.</p></header>
<div class="flow">
  <div class="step"><h3>Parent books</h3><p>Picks the child, the vaccine and a hospital, then sends the request.</p><a href="<?= e(url($u && $u['role'] === 'parent' ? 'parent/dashboard.php' : 'register.php?role=parent')) ?>"><?= $u && $u['role'] === 'parent' ? 'Open Parent page →' : 'Register as a parent →' ?></a></div>
  <div class="step"><h3>Admin approves</h3><p>Checks stock and slots, then approves or rejects with a remark.</p><a href="<?= e(url($u && $u['role'] === 'admin' ? 'admin/requests.php' : 'login.php')) ?>"><?= $u && $u['role'] === 'admin' ? 'Open Admin page →' : 'Admin log in →' ?></a></div>
  <div class="step"><h3>Hospital vaccinates</h3><p>Sees the approved appointment and marks it Vaccinated or Not vaccinated.</p><a href="<?= e(url($u && $u['role'] === 'hospital' ? 'hospital/dashboard.php' : 'register.php?role=hospital')) ?>"><?= $u && $u['role'] === 'hospital' ? 'Open Hospital page →' : 'Register a hospital →' ?></a></div>
  <div class="step"><h3>Reports update</h3><p>The child's card and the admin's date-wise report change right away.</p><a href="<?= e(url($u && $u['role'] === 'admin' ? 'admin/reports.php' : ($u && $u['role'] === 'parent' ? 'parent/report.php' : 'login.php'))) ?>">See the reports →</a></div>
</div>
<section><div class="kicker">The schedule</div><h2>Vaccines and when they are due</h2><p class="sub">Due dates come from the child's date of birth. This follows Pakistan EPI timings, in weeks after birth.</p>
<div class="panelbox sched"><div class="tw"><table><thead><tr><th>Vaccine</th><th>Given at</th></tr></thead><tbody>
<?php foreach ($vaccines as $v): ?><tr><td><?= e($v['name']) ?></td><td><?= $v['age_weeks'] ? (int)$v['age_weeks'] . ' weeks' : 'Birth' ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<p style="margin-top:22px"><a class="btn pri" href="<?= e(url($u ? home_for($u['role']) : 'register.php?role=parent')) ?>"><?= $u ? 'Open your dashboard →' : 'Try the workflow →' ?></a></p></section>
<?php page_footer();
