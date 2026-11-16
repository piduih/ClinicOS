<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Audit\AuditLogger;
use ClinicOS\Database\Connection;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = Auth::requireLogin($dbConfig);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid patient ID.'); }
$pdo = Connection::make($dbConfig);
$stmt = $pdo->prepare('SELECT p.*, u.full_name AS created_by_name FROM patients p LEFT JOIN users u ON u.id=p.created_by WHERE p.id=:id LIMIT 1');
$stmt->execute(['id'=>$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); exit('Patient record not found.'); }
AuditLogger::record($pdo, $user['id'], 'patient.viewed', 'patient', $id);
$e = static fn($v) => htmlspecialchars((string)($v ?: '—'), ENT_QUOTES, 'UTF-8');
$sexLabels = ['male'=>'Lelaki','female'=>'Perempuan','other'=>'Lain-lain','unknown'=>'Tidak dinyatakan'];
?>
<!doctype html>
<html lang="ms">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profil pesakit | ClinicOS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/app.css" rel="stylesheet"></head>
<body><main class="container py-4 py-lg-5" style="max-width:900px">
<header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3 mb-1"><?= $e($p['full_name']) ?></h1><div class="text-secondary"><?= $e($p['medical_record_no']) ?></div></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="index.php">Senarai</a><a class="btn btn-clinic" href="form.php?id=<?= (int)$p['id'] ?>">Kemaskini</a></div></header>
<?php if (isset($_GET['created']) || isset($_GET['saved'])): ?><div class="alert alert-success">Rekod pesakit berjaya <?= isset($_GET['created'])?'didaftarkan':'dikemaskini' ?>.</div><?php endif; ?>
<section class="content-card mb-3"><h2 class="h5 mb-3">Maklumat pesakit</h2><div class="row g-3">
<?php foreach (['Tarikh lahir'=>$p['date_of_birth'],'Jantina'=>$sexLabels[$p['sex']]??'Tidak dinyatakan','Telefon'=>$p['phone'],'E-mel'=>$p['email'],'Alamat'=>$p['address']] as $label=>$value): ?><div class="col-md-6"><div class="small text-secondary"><?= $label ?></div><div><?= nl2br($e($value)) ?></div></div><?php endforeach; ?>
</div></section>
<section class="content-card"><h2 class="h5 mb-3">Hubungan kecemasan</h2><div class="row g-3"><div class="col-md-6"><div class="small text-secondary">Nama</div><?= $e($p['emergency_contact_name']) ?></div><div class="col-md-6"><div class="small text-secondary">Telefon</div><?= $e($p['emergency_contact_phone']) ?></div></div></section>
<p class="small text-secondary mt-3">Didaftarkan oleh <?= $e($p['created_by_name']) ?> · <?= $e($p['created_at']) ?></p>
</main></body></html>
