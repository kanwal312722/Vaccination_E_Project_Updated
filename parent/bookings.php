<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('parent');
if (is_post() && post('action') === 'cancel') {
    $n = q("UPDATE appointments SET status='cancelled', updated_at=CURRENT_TIMESTAMP
            WHERE id=? AND status='pending' AND child_id IN (SELECT id FROM children WHERE parent_id=?)", [(int)$_POST['id'], $u['id']])->rowCount();
    flash($n ? 'ok' : 'error', $n ? 'Request cancelled.' : 'Only pending requests can be cancelled.');
    redirect('parent/bookings.php');
}
$list = rows("SELECT a.*, c.name child, v.name vaccine, h.name hospital, h.address FROM appointments a
              JOIN children c ON c.id=a.child_id JOIN vaccines v ON v.id=a.vaccine_id JOIN hospitals h ON h.id=a.hospital_id
              WHERE c.parent_id=? ORDER BY a.created_at DESC, a.id DESC", [$u['id']]);
page_header('My bookings');
?>
<div class="card">
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Child</th><th>Vaccine</th><th>Hospital</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($list as $a): ?><tr><td><?= e($a['child']) ?></td><td><?= e($a['vaccine']) ?></td><td><?= e($a['hospital']) ?><div class="muted xs"><?= e($a['address']) ?></div></td><td><?= e(fmt_date($a['preferred_date'])) ?></td>
<td><?= pill($a['status']) ?> <?= $a['status'] === 'approved' && $a['result'] !== 'pending' ? pill($a['result']) : '' ?><?php if ($a['admin_remark']): ?><div class="muted xs"><?= e($a['admin_remark']) ?></div><?php endif; ?></td>
<td><?php if ($a['status'] === 'pending'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn sm no">Cancel</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty">No bookings yet.</div><p style="margin-top:14px"><a class="btn" href="<?= e(url('parent/book.php')) ?>">Book a vaccination</a></p><?php endif; ?></div>
<?php page_footer();
