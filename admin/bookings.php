<?php
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');
$status = (string)($_GET['status'] ?? '');
if (!in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) $status = '';
$search = clean((string)($_GET['q'] ?? ''));
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
$w = []; $p = [];
if ($status) { $w[] = 'a.status=?'; $p[] = $status; }
if ($search !== '') { $w[] = '(c.name LIKE ? OR u.name LIKE ? OR h.name LIKE ?)'; array_push($p, $like, $like, $like); }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$from = "FROM appointments a JOIN children c ON c.id=a.child_id JOIN users u ON u.id=c.parent_id JOIN vaccines v ON v.id=a.vaccine_id JOIN hospitals h ON h.id=a.hospital_id $where";
[$page, $pages, $off, $per] = paginate((int)val("SELECT COUNT(*) $from", $p));
$list = rows("SELECT a.*, c.name child, u.name parent, v.name vaccine, h.name hospital $from ORDER BY a.created_at DESC, a.id DESC LIMIT $per OFFSET $off", $p);
page_header('Booking details');
?>
<div class="card">
<form method="get" class="filters"><label>Search<input name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Child, parent or hospital"></label>
<label>Status<select name="status"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></label>
<button class="btn" type="submit">Filter</button></form>
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Date</th><th>Request</th><th>Vaccination</th></tr></thead><tbody>
<?php foreach ($list as $a): ?><tr><td><?= e($a['child']) ?></td><td><?= e($a['parent']) ?></td><td><?= e($a['vaccine']) ?></td><td><?= e($a['hospital']) ?></td><td><?= e(fmt_date($a['preferred_date'])) ?></td>
<td><?= pill($a['status']) ?><?php if ($a['admin_remark']): ?><div class="muted xs"><?= e($a['admin_remark']) ?></div><?php endif; ?></td><td><?= $a['status'] === 'approved' ? pill($a['result']) : '' ?></td></tr><?php endforeach; ?>
</tbody></table></div><?= pager($page, $pages) ?>
<?php else: ?><div class="empty">No bookings found.</div><?php endif; ?></div>
<?php page_footer();
