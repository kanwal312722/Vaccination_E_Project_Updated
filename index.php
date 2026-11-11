<?php
require_once __DIR__ . '/includes/layout.php';
$u = user();
$start = $u ? url(home_for($u['role'])) : url('register.php?role=parent');
page_header('');
?>
  <header class="cover" id="top">
    <div class="blob b1"></div><div class="blob b2"></div>
    <div style="position:relative">
      <div class="eyebrow"><i></i>E-vaccination for infants and children</div>
      <h1>Every child. Every dose. <em>On time.</em></h1>
      <p class="lede">Parents register their child once and see exactly which vaccines are due. They book a hospital, the admin approves, and the hospital records the dose. No missed camps, no paper cards.</p>
      <div class="cta"><a class="btn pri" href="<?= e($start) ?>"><?= $u ? 'Open your dashboard →' : 'Start as a parent →' ?></a><a class="btn ghost" href="<?= e(url('how.php')) ?>">See how it works</a></div>
      <div class="facts"><div><b>3</b>roles: parent, admin, hospital</div><div><b><?= (int)val('SELECT COUNT(*) FROM vaccines') ?></b>childhood vaccines tracked</div><div><b>0</b>paper cards to lose</div></div>
    </div>
    <div class="stage">
      <div class="arch"><img class="baby" alt="Smiling baby" src="<?= e(url('assets/baby.jpg')) ?>"></div>
      <div class="vcard mini" aria-label="Sample vaccination card">
        <header><div><small>Immunization card</small><h3>Ayaan Khan</h3></div><div class="seal"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2A0F0A" stroke-width="3" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></div></header>
        <button class="vrow vb" type="button" data-dose="bcg"><span class="dot d">✓</span><b>BCG</b><span class="w">Birth</span></button>
        <button class="vrow vb" type="button" data-dose="p2"><span class="dot d">✓</span><b>Pentavalent-2</b><span class="w">10 wks</span></button>
        <button class="vrow vb" type="button" data-dose="ipv"><span class="dot o">!</span><b>IPV</b><span class="w">Due 26 Aug</span></button>
        <div class="vfoot"><span id="vmsg">Tap IPV to mark it given</span><a href="<?= e($start) ?>">Open full card →</a></div>
      </div>
      <div class="sticker">Brave baby. Dose 3 done!</div>
    </div>
  </header>

  <section id="roles">
    <div class="kicker">Pick a role</div><h2>One system, three connected pages</h2>
    <p class="sub">Each role has its own dashboard on the same data. A parent books, the admin approves, then the hospital marks the dose.</p>
    <div class="roles">
      <a class="role p" href="<?= e(url('register.php?role=parent')) ?>"><h3>Parent</h3><ul><li>Add children, see due dates</li><li>Book a hospital</li><li>View past vaccinations</li></ul><span class="go">Register as a parent →</span></a>
      <a class="role a" href="<?= e(url('login.php')) ?>"><h3>Admin</h3><ul><li>Approve or reject requests</li><li>Vaccines and hospitals</li><li>Date-wise reports</li></ul><span class="go">Admin log in →</span></a>
      <a class="role h" href="<?= e(url('register.php?role=hospital')) ?>"><h3>Hospital</h3><ul><li>See approved appointments</li><li>Mark Vaccinated or Not vaccinated</li></ul><span class="go">Register a hospital →</span></a>
    </div>
  </section>
<?php page_footer();
