<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('parent');
$kids = rows('SELECT * FROM children WHERE parent_id=? ORDER BY name', [$u['id']]);
$cid = (int)($_GET['child'] ?? ($kids[0]['id'] ?? 0));
$child = $cid ? child_of_parent($cid, (int)$u['id']) : null;
$recs = $child ? rows("SELECT r.*, v.name vaccine, h.name hospital FROM vaccination_records r JOIN vaccines v ON v.id=r.vaccine_id JOIN hospitals h ON h.id=r.hospital_id WHERE r.child_id=? ORDER BY r.given_on DESC, r.id DESC", [$cid]) : [];
page_header('Vaccination report');
?>
<div class="card">
<?php if (!$kids): ?><div class="empty">Add a child to see reports.</div>
<?php else: ?>
<form method="get" class="filters"><label>Child<select name="child"><?php foreach ($kids as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cid === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label><button class="btn" type="submit">Show</button></form>
<?php if ($child): ?><h3><?= e($child['name']) ?> <span class="muted xs">· born <?= e(fmt_date($child['dob'])) ?></span></h3>
<?php if ($recs): ?><div class="tw"><table><thead><tr><th>Date</th><th>Vaccine</th><th>Hospital</th><th>Result</th></tr></thead><tbody>
<?php foreach ($recs as $r): ?><tr><td><?= e(fmt_date($r['given_on'])) ?></td><td><?= e($r['vaccine']) ?></td><td><?= e($r['hospital']) ?></td><td><?= pill($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty">No doses recorded yet.</div><?php endif; endif; endif; ?>
</div>
<?php page_footer();
