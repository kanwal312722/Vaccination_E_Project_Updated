<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('parent');
$kids = rows('SELECT * FROM children WHERE parent_id=? ORDER BY dob DESC', [$u['id']]);
$cid = (int)($_GET['child'] ?? ($kids[0]['id'] ?? 0));
$c = $cid ? child_of_parent($cid, (int)$u['id']) : null;
if (!$c && $kids) { $c = $kids[0]; $cid = (int)$c['id']; }
page_header('Parent dashboard');

if (!$c): ?>
<div class="card"><div class="empty">Add your child to see the vaccination schedule.</div><p style="margin-top:14px"><a class="btn" href="<?= e(url('parent/children.php')) ?>">Add a child</a></p></div>
<?php page_footer(); return; endif;

$sch = child_schedule($cid);
$soon = date('Y-m-d', strtotime('+3 days'));
$alerts = array_filter($sch, fn($s) => $s['state'] === 'overdue' || ($s['state'] === 'upcoming' && $s['due'] <= $soon));
$open = array_filter($sch, fn($s) => $s['state'] === 'overdue' || $s['state'] === 'upcoming');
$hospitals = rows('SELECT id,name FROM hospitals WHERE active=1 ORDER BY name');
$mine = rows("SELECT a.*, v.name vaccine, h.name hospital FROM appointments a JOIN vaccines v ON v.id=a.vaccine_id JOIN hospitals h ON h.id=a.hospital_id WHERE a.child_id=? ORDER BY a.created_at DESC, a.id DESC", [$cid]);
$recs = rows("SELECT r.*, v.name vaccine, h.name hospital FROM vaccination_records r JOIN vaccines v ON v.id=r.vaccine_id JOIN hospitals h ON h.id=r.hospital_id WHERE r.child_id=? ORDER BY r.given_on DESC, r.id DESC", [$cid]);
$pre = (int)($_GET['vaccine'] ?? 0);
?>
<div class="chips" role="group" aria-label="Child">
  <?php foreach ($kids as $k): ?><a class="chip" href="?child=<?= (int)$k['id'] ?>" aria-pressed="<?= (int)$k['id'] === $cid ? 'true' : 'false' ?>"><?= e($k['name']) ?></a><?php endforeach; ?>
  <a class="chip" href="<?= e(url('parent/children.php')) ?>">+ Add child</a>
</div>
<?php if ($alerts): ?><div class="alerts"><?php foreach ($alerts as $a): ?>
  <div class="alert <?= $a['state'] === 'overdue' ? 'bad' : '' ?>"><?= $a['state'] === 'overdue'
      ? '⚠ ' . e($a['vaccine']['name']) . ' was due on ' . e(fmt_date($a['due'])) . '. Book a hospital now.'
      : '🔔 ' . e($a['vaccine']['name']) . ' is due on ' . e(fmt_date($a['due'])) . '.' ?></div>
<?php endforeach; ?></div><?php endif; ?>

<div class="grid2">
  <div class="panel"><h3><?= e($c['name']) ?> · <?= e(age_text($c['dob'])) ?> old</h3>
    <div class="tl">
    <?php foreach ($sch as $s): $v = $s['vaccine']; ?>
      <div class="r"><span class="n"><?= e($v['name']) ?></span>
        <span class="dt"><?= $s['state'] === 'done' ? 'Given' : 'Due ' . e(fmt_date($s['due'])) ?></span>
        <?= $s['state'] === 'booked' ? pill('booked') . ' <span class="pill ' . e($s['appt']['status']) . '">' . e(ucfirst($s['appt']['status'])) . '</span>' : pill($s['state']) ?>
        <?php if (($s['state'] === 'overdue' || $s['state'] === 'upcoming') && $v['available']): ?><a class="small" href="?child=<?= $cid ?>&vaccine=<?= (int)$v['id'] ?>#book">Book</a><?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
  <div style="display:grid;gap:18px;min-width:0">
    <div class="panel" id="book"><h3>Book a hospital</h3>
      <?php if ($open && $hospitals): ?>
      <form method="post" action="<?= e(url('parent/book.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="child" value="<?= $cid ?>">
        <label>Vaccine<select name="vaccine" required><?php foreach ($open as $s): $v = $s['vaccine']; ?><option value="<?= (int)$v['id'] ?>" <?= $pre === (int)$v['id'] ? 'selected' : '' ?> <?= $v['available'] ? '' : 'disabled' ?>><?= e($v['name']) ?><?= $v['available'] ? '' : ' (unavailable)' ?></option><?php endforeach; ?></select></label>
        <label>Hospital<select name="hospital" required><?php foreach ($hospitals as $h): ?><option value="<?= (int)$h['id'] ?>"><?= e($h['name']) ?></option><?php endforeach; ?></select></label>
        <label>Preferred date<input type="date" name="date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>"></label>
        <button class="btn pri" type="submit">Send request</button>
      </form>
      <?php elseif (!$hospitals): ?><div class="empty">No hospitals are active yet.</div>
      <?php else: ?><div class="empty">All vaccines are done or booked for <?= e($c['name']) ?>.</div><?php endif; ?>
    </div>
    <div class="panel"><h3>My bookings</h3>
      <?php if ($mine): ?><div class="tw"><table><thead><tr><th>Vaccine</th><th>Hospital</th><th>Date</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($mine as $a): ?><tr><td><?= e($a['vaccine']) ?></td><td><?= e($a['hospital']) ?></td><td class="num"><?= e(fmt_date($a['preferred_date'])) ?></td>
        <td><?= pill($a['status']) ?> <?= $a['status'] === 'approved' && $a['result'] !== 'pending' ? pill($a['result']) : '' ?><?php if ($a['admin_remark']): ?><div class="note"><?= e($a['admin_remark']) ?></div><?php endif; ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php else: ?><div class="empty">No bookings yet.</div><?php endif; ?>
    </div>
  </div>
</div>
<div class="panel" style="margin-top:18px"><h3>Vaccination report</h3>
  <?php if ($recs): ?><div class="tw"><table><thead><tr><th>Date</th><th>Vaccine</th><th>Hospital</th><th>Result</th></tr></thead><tbody>
  <?php foreach ($recs as $r): ?><tr><td class="num"><?= e(fmt_date($r['given_on'])) ?></td><td><?= e($r['vaccine']) ?></td><td><?= e($r['hospital']) ?></td><td><?= pill($r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php else: ?><div class="empty">No doses recorded yet.</div><?php endif; ?>
</div>
<?php page_footer();
