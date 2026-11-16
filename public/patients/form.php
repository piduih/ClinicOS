<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Database\Connection;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = Auth::requireLogin($dbConfig);
$pdo = Connection::make($dbConfig);
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$patient = [
    'full_name' => '', 'date_of_birth' => '', 'sex' => 'unknown', 'phone' => '',
    'email' => '', 'address' => '', 'emergency_contact_name' => '',
    'emergency_contact_phone' => '', 'notes' => '',
];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        http_response_code(404);
        exit('Patient record not found.');
    }
    $patient = array_merge($patient, $existing);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesi borang tidak sah. Sila muat semula halaman.';
    } else {
        foreach (array_keys($patient) as $field) {
            $patient[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $allowedSex = ['male', 'female', 'other', 'unknown'];
        if ($patient['full_name'] === '' || strlen($patient['full_name']) > 600) {
            $error = 'Nama pesakit wajib diisi dan tidak melebihi 150 aksara.';
        } elseif (!in_array($patient['sex'], $allowedSex, true)) {
            $error = 'Pilihan jantina tidak sah.';
        } elseif ($patient['email'] !== '' && !filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Format e-mel tidak sah.';
        } elseif ($patient['date_of_birth'] !== '' && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $patient['date_of_birth'], $dateParts) || !checkdate((int) ($dateParts[2] ?? 0), (int) ($dateParts[3] ?? 0), (int) ($dateParts[1] ?? 0)))) {
            $error = 'Tarikh lahir tidak sah.';
        } else {
            try {
                $data = [
                    'full_name' => $patient['full_name'],
                    'date_of_birth' => $patient['date_of_birth'] !== '' ? $patient['date_of_birth'] : null,
                    'sex' => $patient['sex'],
                    'phone' => $patient['phone'] ?: null,
                    'email' => $patient['email'] ?: null,
                    'address' => $patient['address'] ?: null,
                    'emergency_contact_name' => $patient['emergency_contact_name'] ?: null,
                    'emergency_contact_phone' => $patient['emergency_contact_phone'] ?: null,
                    'notes' => $patient['notes'] ?: null,
                ];
                if ($id) {
                    $data['id'] = $id;
                    $stmt = $pdo->prepare('UPDATE patients SET full_name=:full_name, date_of_birth=:date_of_birth, sex=:sex, phone=:phone, email=:email, address=:address, emergency_contact_name=:emergency_contact_name, emergency_contact_phone=:emergency_contact_phone, notes=:notes WHERE id=:id');
                    $stmt->execute($data);
                    header('Location: view.php?id=' . $id . '&saved=1');
                } else {
                    $mrn = 'CL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                    $data['medical_record_no'] = $mrn;
                    $data['created_by'] = $user['id'];
                    $stmt = $pdo->prepare('INSERT INTO patients (medical_record_no, full_name, date_of_birth, sex, phone, email, address, emergency_contact_name, emergency_contact_phone, notes, created_by) VALUES (:medical_record_no, :full_name, :date_of_birth, :sex, :phone, :email, :address, :emergency_contact_name, :emergency_contact_phone, :notes, :created_by)');
                    $stmt->execute($data);
                    header('Location: view.php?id=' . (int) $pdo->lastInsertId() . '&created=1');
                }
                exit;
            } catch (PDOException $exception) {
                $error = 'Rekod tidak dapat disimpan. Sila semak maklumat dan cuba lagi.';
            }
        }
    }
}
function fieldValue(array $patient, string $key): string {
    return htmlspecialchars((string) ($patient[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ms">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $id ? 'Kemaskini' : 'Daftar' ?> pesakit | ClinicOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
<main class="container py-4 py-lg-5" style="max-width:900px">
  <header class="d-flex justify-content-between align-items-center mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3"><?= $id ? 'Kemaskini pesakit' : 'Daftar pesakit baharu' ?></h1></div><a class="btn btn-outline-secondary" href="index.php">Kembali</a></header>
  <section class="content-card">
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
      <h2 class="h6 mb-3">Maklumat pesakit</h2>
      <div class="row g-3">
        <div class="col-12"><label class="form-label" for="full_name">Nama penuh *</label><input class="form-control" id="full_name" name="full_name" maxlength="150" required value="<?= fieldValue($patient, 'full_name') ?>"></div>
        <div class="col-md-6"><label class="form-label" for="date_of_birth">Tarikh lahir</label><input class="form-control" type="date" id="date_of_birth" name="date_of_birth" value="<?= fieldValue($patient, 'date_of_birth') ?>"></div>
        <div class="col-md-6"><label class="form-label" for="sex">Jantina</label><select class="form-select" id="sex" name="sex"><?php foreach (['unknown'=>'Tidak dinyatakan','male'=>'Lelaki','female'=>'Perempuan','other'=>'Lain-lain'] as $value=>$label): ?><option value="<?= $value ?>" <?= $patient['sex']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label" for="phone">Telefon</label><input class="form-control" id="phone" name="phone" maxlength="30" value="<?= fieldValue($patient, 'phone') ?>"></div>
        <div class="col-md-6"><label class="form-label" for="email">E-mel</label><input class="form-control" type="email" id="email" name="email" maxlength="190" value="<?= fieldValue($patient, 'email') ?>"></div>
        <div class="col-12"><label class="form-label" for="address">Alamat</label><textarea class="form-control" id="address" name="address" rows="2"><?= fieldValue($patient, 'address') ?></textarea></div>
      </div>
      <hr class="my-4"><h2 class="h6 mb-3">Orang untuk dihubungi ketika kecemasan</h2>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="emergency_contact_name">Nama</label><input class="form-control" id="emergency_contact_name" name="emergency_contact_name" maxlength="150" value="<?= fieldValue($patient, 'emergency_contact_name') ?>"></div>
        <div class="col-md-6"><label class="form-label" for="emergency_contact_phone">Telefon</label><input class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" maxlength="30" value="<?= fieldValue($patient, 'emergency_contact_phone') ?>"></div>
        <div class="col-12"><label class="form-label" for="notes">Nota pentadbiran</label><textarea class="form-control" id="notes" name="notes" rows="3"><?= fieldValue($patient, 'notes') ?></textarea></div>
      </div>
      <div class="d-flex gap-2 mt-4"><button class="btn btn-clinic" type="submit">Simpan rekod</button><a class="btn btn-outline-secondary" href="index.php">Batal</a></div>
      <p class="small text-secondary mt-3 mb-0">Jangan masukkan diagnosis atau maklumat klinikal sensitif dalam nota ini. Modul rekod klinikal memerlukan kawalan akses dan audit khusus.</p>
    </form>
  </section>
</main>
</body>
</html>
