<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Database\Connection;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole($dbConfig, ['admin']);
$pdo = Connection::make($dbConfig);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$event = trim((string) ($_GET['event'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$where = [];
$params = [];
if ($event !== '' && preg_match('/^[a-z_.]+$/', $event)) {
    $where[] = 'l.event = :event';
    $params['event'] = $event;
}
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = 'l.created_at >= :from_date';
    $params['from_date'] = $from . ' 00:00:00';
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = 'l.created_at <= :to_date';
    $params['to_date'] = $to . ' 23:59:59';
}
$clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$count = $pdo->prepare('SELECT COUNT(*) FROM audit_logs l' . $clause);
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$sql = 'SELECT l.id, l.event, l.entity_type, l.entity_id, l.ip_address, l.created_at, u.full_name AS actor_name
        FROM audit_logs l LEFT JOIN users u ON u.id = l.actor_user_id' . $clause .
       ' ORDER BY l.id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$events = [
 'auth.login_succeeded'=>'Log masuk berjaya','auth.login_failed'=>'Log masuk gagal','auth.logout'=>'Log keluar',
 'patient.created'=>'Pesakit didaftarkan','patient.updated'=>'Rekod pesakit dikemaskini','patient.viewed'=>'Profil pesakit dilihat',
 'appointment.created'=>'Temujanji dibuat','appointment.updated'=>'Temujanji dikemaskini','appointment.status_changed'=>'Status temujanji berubah',
 'user.created'=>'Pengguna ditambah','user.updated'=>'Pengguna dikemaskini','user.activated'=>'Pengguna diaktifkan','user.deactivated'=>'Pengguna dinyahaktifkan'
];
$query = http_build_query(array_filter(['event'=>$event,'from'=>$from,'to'=>$to], static fn($v)=>$v!==''));
?>
<!doctype html><html lang="ms"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Audit Log | ClinicOS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/app.css" rel="stylesheet"></head>
<body><main class="container-fluid px-3 px-lg-5 py-4 py-lg-5">
<header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3 mb-0">Audit Log</h1><p class="text-secondary mb-0">Rekod aktiviti keselamatan dan operasi.</p></div><a class="btn btn-outline-secondary" href="../index.php">Dashboard</a></header>
<section class="content-card mb-3"><form method="get" class="row g-2 align-items-end">
<div class="col-12 col-md-4"><label class="form-label" for="event">Aktiviti</label><select class="form-select" id="event" name="event"><option value="">Semua aktiviti</option><?php foreach($events as $key=>$label): ?><option value="<?= htmlspecialchars($key,ENT_QUOTES,'UTF-8') ?>" <?= $event===$key?'selected':'' ?>><?= htmlspecialchars($label,ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select></div>
<div class="col-6 col-md-3"><label class="form-label" for="from">Dari</label><input class="form-control" type="date" id="from" name="from" value="<?= htmlspecialchars($from,ENT_QUOTES,'UTF-8') ?>"></div>
<div class="col-6 col-md-3"><label class="form-label" for="to">Hingga</label><input class="form-control" type="date" id="to" name="to" value="<?= htmlspecialchars($to,ENT_QUOTES,'UTF-8') ?>"></div>
<div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-clinic">Tapis</button><a class="btn btn-outline-secondary" href="audit-logs.php">Reset</a></div></form></section>
<section class="content-card"><div class="d-flex justify-content-between mb-3"><h2 class="h5 mb-0">Aktiviti direkodkan</h2><span class="small text-secondary"><?= number_format($total) ?> rekod</span></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Masa</th><th>Pengguna</th><th>Aktiviti</th><th>Rekod ID</th><th>Alamat IP</th></tr></thead><tbody>
<?php foreach($logs as $log): ?><tr><td class="text-nowrap"><?= htmlspecialchars($log['created_at'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($log['actor_name'] ?: 'Sistem / akaun dipadam',ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($events[$log['event']] ?? $log['event'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars(trim(($log['entity_type'] ?? '').' '.($log['entity_id'] ?? '')),ENT_QUOTES,'UTF-8') ?: '—' ?></td><td><?= htmlspecialchars($log['ip_address'] ?: '—',ENT_QUOTES,'UTF-8') ?></td></tr><?php endforeach; ?>
<?php if(!$logs): ?><tr><td colspan="5" class="text-center text-secondary py-4">Tiada rekod ditemui.</td></tr><?php endif; ?>
</tbody></table></div>
<nav class="d-flex justify-content-between align-items-center"><span class="small text-secondary">Halaman <?= $page ?> daripada <?= $pages ?></span><div class="d-flex gap-2"><?php if($page>1): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= htmlspecialchars($query,ENT_QUOTES,'UTF-8') ?>&page=<?= $page-1 ?>">Sebelumnya</a><?php endif; ?><?php if($page<$pages): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= htmlspecialchars($query,ENT_QUOTES,'UTF-8') ?>&page=<?= $page+1 ?>">Seterusnya</a><?php endif; ?></div></nav>
<p class="small text-secondary mt-3 mb-0">Paparan baca sahaja. Log tidak menyimpan kata laluan, nota, diagnosis atau kandungan borang pesakit.</p></section></main></body></html>