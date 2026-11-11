<?php
require_once __DIR__ . '/../includes/layout.php';
require_role('admin');
$search = clean((string)($_GET['q'] ?? ''));
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
$where = $search !== '' ? 'WHERE c.name LIKE ? OR u.name LIKE ?' : '';
$params = $search !== '' ? [$like, $like] : [];
$total = (int)val("SELECT COUNT(*) FROM children c JOIN users u ON u.id=c.parent_id $where", $params);
[$page, $pages, $off, $per] = paginate($total);
$list = rows("SELECT c.*, u.name parent, u.phone, u.email FROM children c JOIN users u ON u.id=c.parent_id $where ORDER BY c.name LIMIT $per OFFSET $off", $params);
page_header('All children');
?>
<div class="card">
<form method="get" class="filters"><label>Search child or parent<input name="q" value="<?= e($search) ?>" maxlength="100"></label><button class="btn" type="submit">Search</button></form>
<?php if ($list): ?><div class="tw"><table><thead><tr><th>Child</th><th>Gender</th><th>Born</th><th>Blood</th><th>Parent</th><th>Next vaccination</th></tr></thead><tbody>
<?php foreach ($list as $c): $nx = next_due(child_schedule((int)$c['id'])); ?>
  <tr><td><?= e($c['name']) ?><div class="muted xs"><?= e(age_text($c['dob'])) ?></div></td><td><?= e($c['gender']) ?></td><td><?= e(fmt_date($c['dob'])) ?></td><td><?= e($c['blood_group'] ?: '-') ?></td>
  <td><?= e($c['parent']) ?><div class="muted xs"><?= e($c['phone'] ?: $c['email']) ?></div></td>
  <td><?php if ($nx): ?><?= e($nx['vaccine']['name']) ?> · <?= e(fmt_date($nx['due'])) ?> <?= pill($nx['state']) ?><?php else: ?>Complete<?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div><?= pager($page, $pages) ?>
<?php else: ?><div class="empty">No children found.</div><?php endif; ?>
</div>
<?php page_footer();
