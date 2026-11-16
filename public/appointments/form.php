<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Audit\AuditLogger;
use ClinicOS\Database\Connection;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = Auth::requireLogin($dbConfig);
$pdo = Connection::make($dbConfig);
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$appointment = ['patient_id'=>'', 'doctor_id'=>'', 'scheduled_at'=>'', 'reason'=>''];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id=:id LIMIT 1');
    $stmt->execute(['id'=>$id]);
    $existing = $stmt->fetch();
    if (!$existing) { http_response_code(404); exit('Appointment not found.'); }
    $appointment = array_merge($appointment, $existing);
    $appointment['scheduled_at'] = date('Y-m-d\TH:i', strtotime($existing['scheduled_at']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesi borang tidak sah. Sila muat semula halaman.';
    } else {
        $appointment['patient_id'] = (string) ($_POST['patient_id'] ?? '');
        $appointment['doctor_id'] = (string) ($_POST['doctor_id'] ?? '');
        $appointment['scheduled_at'] = trim((string) ($_POST['scheduled_at'] ?? ''));
        $appointment['reason'] = trim((string) ($_POST['reason'] ?? ''));
        $patientId = filter_var($appointment['patient_id'], FILTER_VALIDATE_INT);
        $doctorId = $appointment['doctor_id'] !== '' ? filter_var($appointment['doctor_id'], FILTER_VALIDATE_INT) : null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $appointment['scheduled_at']);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $validDate = $date && (!$dateErrors || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
            && $date->format('Y-m-d\TH:i') === $appointment['scheduled_at'];

        if (!$patientId) {
            $error = 'Sila pilih pesakit yang sah.';
        } elseif (!$validDate) {
            $error = 'Tarikh dan masa temujanji tidak sah.';
        } elseif (strlen($appointment['reason']) > 255) {
            $error = 'Tujuan temujanji tidak boleh melebihi 255 aksara.';
        } elseif ($appointment['doctor_id'] !== '' && !$doctorId) {
            $error = 'Pilihan doktor tidak sah.';
        } else {
            $patientCheck = $pdo->prepare('SELECT id FROM patients WHERE id=:id');
            $patientCheck->execute(['id'=>$patientId]);
            $doctorValid = true;
            if ($doctorId) {
                $doctorCheck = $pdo->prepare("SELECT id FROM users WHERE id=:id AND role='doctor' AND is_active=1");
                $doctorCheck->execute(['id'=>$doctorId]);
                $doctorValid = (bool) $doctorCheck->fetchColumn();
            }
            if (!$patientCheck->fetchColumn()) {
                $error = 'Pesakit tidak ditemui.';
            } elseif (!$doctorValid) {
                $error = 'Doktor tidak sah atau akaun tidak aktif.';
            } else {
                $duplicateSql = "SELECT id FROM appointments
                    WHERE scheduled_at=:scheduled_at AND status NOT IN ('cancelled','no_show')";
                $duplicateParams = ['scheduled_at'=>$date->format('Y-m-d H:i:s')];
                if ($doctorId) {
                    $duplicateSql .= ' AND doctor_id=:doctor_id';
                    $duplicateParams['doctor_id'] = $doctorId;
                } else {
                    $duplicateSql .= ' AND doctor_id IS NULL';
                }
                if ($id) {
                    $duplicateSql .= ' AND id<>:id';
                    $duplicateParams['id'] = $id;
                }
                $duplicateSql .= ' LIMIT 1';
                $duplicate = $pdo->prepare($duplicateSql);
                $duplicate->execute($duplicateParams);
                if ($duplicate->fetchColumn()) {
                    $error = 'Slot masa ini sudah mempunyai temujanji. Pilih masa lain.';
                } else {
                    $values = [
                        'patient_id'=>$patientId,
                        'doctor_id'=>$doctorId,
                        'scheduled_at'=>$date->format('Y-m-d H:i:s'),
                        'reason'=>$appointment['reason'] ?: null,
                    ];
                    if ($id) {
                        $values['id'] = $id;
                        $save = $pdo->prepare('UPDATE appointments SET patient_id=:patient_id, doctor_id=:doctor_id, scheduled_at=:scheduled_at, reason=:reason WHERE id=:id');
                        $save->execute($values);
                        AuditLogger::record($pdo, $user['id'], 'appointment.updated', 'appointment', $id);
                    } else {
                        $values['created_by'] = $user['id'];
                        $save = $pdo->prepare('INSERT INTO appointments (patient_id, doctor_id, scheduled_at, reason, created_by) VALUES (:patient_id, :doctor_id, :scheduled_at, :reason, :created_by)');
                        $save->execute($values);
                        AuditLogger::record($pdo, $user['id'], 'appointment.created', 'appointment', (int) $pdo->lastInsertId());
                    }
                    header('Location: index.php?saved=1');
                    exit;
                }
            }
        }
    }
}

$patients = $pdo->query('SELECT id, medical_record_no, full_name FROM patients ORDER BY full_name ASC')->fetchAll();
$doctors = $pdo->query("SELECT id, full_name FROM users WHERE role='doctor' AND is_active=1 ORDER BY full_name ASC")->fetchAll();
?>
<!doctype html>
<html lang="ms">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $id?'Kemaskini':'Daftar' ?> temujanji | ClinicOS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/app.css" rel="stylesheet"></head>
<body><main class="container py-4 py-lg-5" style="max-width:850px">
<header class="d-flex justify-content-between align-items-center mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3"><?= $id?'Kemaskini temujanji':'Temujanji baharu' ?></h1></div><a class="btn btn-outline-secondary" href="index.php">Kembali</a></header>
<section class="content-card">
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (!$patients): ?><div class="alert alert-warning">Belum ada pesakit. Daftar pesakit dahulu sebelum membuat temujanji. <a href="../patients/form.php">Daftar pesakit</a></div><?php endif; ?>
<form method="post"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><?php if($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
<div class="mb-3"><label class="form-label" for="patient_id">Pesakit *</label><select class="form-select" id="patient_id" name="patient_id" required><option value="">Pilih pesakit</option><?php foreach($patients as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (string)$appointment['patient_id']===(string)$p['id']?'selected':'' ?>><?= htmlspecialchars($p['full_name'].' · '.$p['medical_record_no'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label" for="doctor_id">Doktor</label><select class="form-select" id="doctor_id" name="doctor_id"><option value="">Belum ditetapkan</option><?php foreach($doctors as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (string)$appointment['doctor_id']===(string)$d['id']?'selected':'' ?>><?= htmlspecialchars($d['full_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><?php if(!$doctors): ?><div class="form-text">Tiada akaun doktor aktif. Temujanji boleh disimpan tanpa doktor ditetapkan.</div><?php endif; ?></div>
<div class="mb-3"><label class="form-label" for="scheduled_at">Tarikh dan masa *</label><input class="form-control" type="datetime-local" id="scheduled_at" name="scheduled_at" required value="<?= htmlspecialchars((string)$appointment['scheduled_at'], ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="mb-4"><label class="form-label" for="reason">Tujuan temujanji</label><input class="form-control" id="reason" name="reason" maxlength="255" value="<?= htmlspecialchars((string)$appointment['reason'], ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="d-flex gap-2"><button class="btn btn-clinic" type="submit" <?= !$patients?'disabled':'' ?>>Simpan temujanji</button><a class="btn btn-outline-secondary" href="index.php">Batal</a></div>
<p class="small text-secondary mt-3 mb-0">Pemeriksaan slot hanya mengesan temujanji pada masa mula yang sama. Tempoh konsultasi dan pertindihan masa belum dimodelkan kerana tempoh slot belum ditetapkan.</p>
</form></section></main></body></html>
