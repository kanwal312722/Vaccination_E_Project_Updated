<?php
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');
$from = (string)($_GET['from'] ?? date('Y-01-01'));
$to = (string)($_GET['to'] ?? date('Y-12-31'));
$status = (string)($_GET['status'] ?? '');
$bad = '';
if (!valid_date($from) || !valid_date($to)) { $bad = 'Enter valid dates.'; $from = date('Y-01-01'); $to = date('Y-12-31'); }
elseif ($from > $to) { $bad = 'The start date must be before the end date.'; $from = date('Y-01-01'); $to = date('Y-12-31'); }
if (!in_array($status, ['vaccinated', 'not_vaccinated'], true)) $status = '';
$p = [$from, $to]; $extra = '';
if ($status) { $extra = ' AND r.status=?'; $p[] = $status; }
$list = rows("SELECT r.given_on, r.status, c.name child, c.dob, u.name parent, v.name vaccine, h.name hospital
              FROM vaccination_records r JOIN children c ON c.id=r.child_id JOIN users u ON u.id=c.parent_id
              JOIN vaccines v ON v.id=r.vaccine_id JOIN hospitals h ON h.id=r.hospital_id
              WHERE r.given_on BETWEEN ? AND ? $extra ORDER BY r.given_on DESC, c.name", $p);

if (isset($_GET['export']) && !$bad) {
    $safe = fn($s) => preg_match('/^[=+\-@\t\r]/', (string)$s) ? "'" . $s : (string)$s;   // stop spreadsheet formula injection
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vaccination-report-' . $from . '-to-' . $to . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Date', 'Child', 'Parent', 'Vaccine', 'Hospital', 'Result']);
    foreach ($list as $r) fputcsv($o, [$r['given_on'], $safe($r['child']), $safe($r['parent']), $safe($r['vaccine']), $safe($r['hospital']), $r['status']]);
    exit;
}
$done = count(array_filter($list, fn($r) => $r['status'] === 'vaccinated'));
page_header('Vaccination reports');
?>
<div class="card">
<?php if ($bad): ?><div class="flash error" role="alert"><?= e($bad) ?></div><?php endif; ?>
<form method="get" class="filters">
  <label>From<input type="date" name="from" value="<?= e($from) ?>" required></label>
  <label>To<input type="date" name="to" value="<?= e($to) ?>" required></label>
  <label>Result<select name="status"><option value="">All</option><option value="vaccinated" <?= $status === 'vaccinated' ? 'selected' : '' ?>>Vaccinated</option><option value="not_vaccinated" <?= $status === 'not_vaccinated' ? 'selected' : '' ?>>Not vaccinated</option></select></label>
  <button class="btn" type="submit">Show</button>
  <button class="btn ghost" type="submit" name="export" value="csv">Download CSV</button>
</form>
<p class="muted xs"><?= count($list) ?> records · <?= $done ?> vaccinated</p>
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Date</th><th>Child</th><th>Parent</th><th>Vaccine</th><th>Hospital</th><th>Result</th></tr></thead><tbody>
<?php foreach ($list as $r): ?><tr><td><?= e(fmt_date($r['given_on'])) ?></td><td><?= e($r['child']) ?></td><td><?= e($r['parent']) ?></td><td><?= e($r['vaccine']) ?></td><td><?= e($r['hospital']) ?></td><td><?= pill($r['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty">No records in this range.</div><?php endif; ?></div>
<?php page_footer();
