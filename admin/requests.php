<?php
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');

if (is_post()) {
    $id = (int)($_POST['id'] ?? 0);
    $action = post('action');
    $remark = mb_substr(clean(post('remark')), 0, 255);
    $a = row('SELECT a.*, v.available FROM appointments a JOIN vaccines v ON v.id=a.vaccine_id WHERE a.id=?', [$id]);
    if (!$a || $a['status'] !== 'pending') {
        flash('error', 'This request was already handled.');
    } elseif ($action === 'approve') {
        if (!$a['available']) flash('error', 'This vaccine is marked unavailable. Mark it available first, or reject the request.');
        else { q("UPDATE appointments SET status='approved', admin_remark=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='pending'", [$remark ?: null, $id]); flash('ok', 'Request approved. The hospital can now see it.'); }
    } elseif ($action === 'reject') {
        if ($remark === '') flash('error', 'Write a short remark so the parent knows why.');
        else { q("UPDATE appointments SET status='rejected', admin_remark=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='pending'", [$remark, $id]); flash('ok', 'Request rejected.'); }
    }
    redirect('admin/requests.php');
}
$list = rows("SELECT a.id, a.preferred_date, a.created_at, c.name child, c.dob, v.name vaccine, v.available, h.name hospital, u.name parent, u.phone
              FROM appointments a JOIN children c ON c.id=a.child_id JOIN users u ON u.id=c.parent_id
              JOIN vaccines v ON v.id=a.vaccine_id JOIN hospitals h ON h.id=a.hospital_id
              WHERE a.status='pending' ORDER BY a.preferred_date, a.id");
page_header('Parent requests');
?>
<div class="card">
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Date</th><th>Decision</th></tr></thead><tbody>
<?php foreach ($list as $r): ?>
  <tr><td><?= e($r['child']) ?><div class="muted xs"><?= e(age_text($r['dob'])) ?></div></td>
  <td><?= e($r['parent']) ?><div class="muted xs"><?= e($r['phone']) ?></div></td>
  <td><?= e($r['vaccine']) ?><?= $r['available'] ? '' : ' ' . pill('unavailable') ?></td>
  <td><?= e($r['hospital']) ?></td><td><?= e(fmt_date($r['preferred_date'])) ?></td>
  <td><form method="post" class="acts"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <input name="remark" maxlength="255" placeholder="Remark" aria-label="Remark">
    <button class="btn sm ok" name="action" value="approve">Approve</button>
    <button class="btn sm no" name="action" value="reject">Reject</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php else: ?><div class="empty">No pending requests.</div><?php endif; ?>
</div>
<?php page_footer();
