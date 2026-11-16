<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Database\Connection;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = Auth::requireLogin($dbConfig);
$pdo = Connection::make($dbConfig);
$allowedStatuses = ['scheduled', 'checked_in', 'completed', 'cancelled', 'no_show'];
$statusLabels = [
    'scheduled' => 'Dijadualkan',
    'checked_in' => 'Sudah daftar',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'no_show' => 'Tidak hadir',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesi borang tidak sah. Sila muat semula halaman.';
    } elseif (!$id || !in_array($status, $allowedStatuses, true)) {
        $error = 'Maklumat kemaskini tidak sah.';
    } else {
        $stmt = $pdo->prepare('UPDATE appointments SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
        header('Location: index.php?updated=1');
        exit;
    }
}

$date = trim((string) ($_GET['date'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$sql = 'SELECT a.id, a.scheduled_at, a.status, a.reason, p.full_name AS patient_name,
               p.medical_record_no, u.full_name AS doctor_name
        FROM appointments a
        INNER JOIN patients p ON p.id = a.patient_id
        LEFT JOIN users u ON u.id = a.doctor_id';
$where = [];
$params = [];
if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $where[] = 'DATE(a.scheduled_at) = :date';
    $params['date'] = $date;
}
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $where[] = 'a.status = :status';
    $params['status'] = $statusFilter;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY a.scheduled_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ms">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Temujanji | ClinicOS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/app.css" rel="stylesheet"></head>
<body><main class="container-fluid px-3 px-lg-5 py-4 py-lg-5">
<header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3 mb-0">Pengurusan temujanji</h1></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="../index.php">Dashboard</a><a class="btn btn-clinic" href="form.php">Temujanji baharu</a></div></header>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Status temujanji berjaya dikemaskini.</div><?php endif; ?>
<section class="content-card mb-3"><form method="get" class="row g-2 align-items-end">
<div class="col-12 col-md-4"><label class="form-label" for="date">Tarikh</label><input class="form-control" type="date" id="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="col-12 col-md-4"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Semua status</option><?php foreach ($statusLabels as $key=>$label): ?><option value="<?= $key ?>" <?= $statusFilter===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-4 d-flex gap-2"><button class="btn btn-outline-secondary">Tapis</button><a class="btn btn-outline-secondary" href="index.php">Reset</a></div>
</form></section>
<section class="content-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Senarai temujanji</h2><span class="small text-secondary"><?= count($appointments) ?> rekod dipaparkan</span></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Tarikh / Masa</th><th>Pesakit</th><th>Doktor</th><th>Tujuan</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($appointments as $a): ?><tr>
<td class="text-nowrap"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($a['scheduled_at'])), ENT_QUOTES, 'UTF-8') ?></td>
<td><div class="fw-semibold"><?= htmlspecialchars($a['patient_name'], ENT_QUOTES, 'UTF-8') ?></div><small class="text-secondary"><?= htmlspecialchars($a['medical_record_no'], ENT_QUOTES, 'UTF-8') ?></small></td>
<td><?= htmlspecialchars($a['doctor_name'] ?: 'Belum ditetapkan', ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($a['reason'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
<td><span class="badge text-bg-light border"><?= htmlspecialchars($statusLabels[$a['status']] ?? $a['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="form.php?id=<?= (int)$a['id'] ?>">Edit</a>
<form method="post" class="d-inline-flex gap-1 mt-1 mt-md-0"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><select class="form-select form-select-sm" name="status" aria-label="Status baharu"><?php foreach ($statusLabels as $key=>$label): ?><option value="<?= $key ?>" <?= $a['status']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-secondary">Simpan</button></form></td>
</tr><?php endforeach; ?>
<?php if (!$appointments): ?><tr><td colspan="6" class="text-center text-secondary py-4">Tiada temujanji ditemui.</td></tr><?php endif; ?>
</tbody></table></div><p class="small text-secondary mb-0">Paparan dihadkan kepada 200 rekod terkini. Gunakan penapis tarikh atau status untuk mengecilkan senarai.</p></section>
</main></body></html>
