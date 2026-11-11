<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_role('hospital');
$h = row('SELECT * FROM hospitals WHERE user_id=?', [$u['id']]);
if (!$h) { http_response_code(403); exit('No hospital is linked to this account.'); }

if (is_post() && post('action') === 'result') {
    $id = (int)($_POST['id'] ?? 0);
    $res = post('result');
    if (!in_array($res, ['vaccinated', 'not_vaccinated'], true)) { flash('error', 'Choose Vaccinated or Not vaccinated.'); redirect('hospital/dashboard.php'); }
    $pdo = db(); $pdo->beginTransaction();
    try {
        // Only this hospital's approved, not-yet-updated appointments can be changed.
        $n = q("UPDATE appointments SET result=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND hospital_id=? AND status='approved' AND result='pending'", [$res, $id, $h['id']])->rowCount();
        if ($n) {
            $a = row('SELECT child_id, vaccine_id FROM appointments WHERE id=?', [$id]);
            q('INSERT INTO vaccination_records (child_id,vaccine_id,hospital_id,appointment_id,given_on,status) VALUES (?,?,?,?,?,?)',
              [$a['child_id'], $a['vaccine_id'], $h['id'], $id, date('Y-m-d'), $res]);
        }
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
    flash($n ? 'ok' : 'error', $n ? 'Status updated. The parent and admin reports now show it.' : 'This appointment cannot be updated.');
    redirect('hospital/dashboard.php');
}
$inbox = rows("SELECT a.id, a.preferred_date, c.name child, c.dob, c.gender, v.name vaccine, p.name parent, p.phone
               FROM appointments a JOIN children c ON c.id=a.child_id JOIN users p ON p.id=c.parent_id JOIN vaccines v ON v.id=a.vaccine_id
               WHERE a.hospital_id=? AND a.status='approved' AND a.result='pending' ORDER BY a.preferred_date, a.id", [$h['id']]);
$hist = rows("SELECT a.preferred_date, a.result, a.updated_at, c.name child, v.name vaccine FROM appointments a JOIN children c ON c.id=a.child_id JOIN vaccines v ON v.id=a.vaccine_id
              WHERE a.hospital_id=? AND a.result<>'pending' ORDER BY a.updated_at DESC, a.id DESC LIMIT 20", [$h['id']]);
page_header('Hospital appointments');
?>
<div class="grid2">
  <div class="panel"><h3>Approved appointments</h3>
  <?php if ($inbox): ?><div class="tw"><table><thead><tr><th>Child</th><th>Vaccine</th><th>Date</th><th>Update status</th></tr></thead><tbody>
  <?php foreach ($inbox as $a): ?><tr><td><?= e($a['child']) ?><div class="muted xs"><?= e($a['gender']) ?> · <?= e(age_text($a['dob'])) ?> · <?= e($a['parent']) ?></div></td><td><?= e($a['vaccine']) ?></td><td class="num"><?= e(fmt_date($a['preferred_date'])) ?></td>
    <td><form method="post" class="acts"><?= csrf_field() ?><input type="hidden" name="action" value="result"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
      <button class="btn sm ok" name="result" value="vaccinated">Vaccinated</button><button class="btn sm no" name="result" value="not_vaccinated">Not vaccinated</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div><?php else: ?><div class="empty">Nothing waiting. Approve a request as Admin and it shows up here.</div><?php endif; ?></div>
  <div class="panel"><h3><?= e($h['name']) ?></h3><p class="note" style="margin-bottom:12px"><?= e($h['address'] . ', ' . $h['city']) ?></p>
    <h3 style="font-size:16px">Completed</h3>
    <?php if ($hist): ?><div class="tl"><?php foreach ($hist as $a): ?><div class="r"><span class="n"><?= e($a['child']) ?> · <?= e($a['vaccine']) ?></span><?= pill($a['result']) ?></div><?php endforeach; ?></div>
    <?php else: ?><div class="empty">No updates yet.</div><?php endif; ?></div>
</div>
<?php page_footer();
