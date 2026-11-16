<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Security\Csrf;
use ClinicOS\Database\Connection;

$dbConfig = require dirname(__DIR__) . '/app/bootstrap.php';
$user = Auth::requireLogin($dbConfig);
$pdo = Connection::make($dbConfig);

$patientCount = (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$todayStatement = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE DATE(scheduled_at) = CURRENT_DATE()');
$todayStatement->execute();
$todayAppointments = (int) $todayStatement->fetchColumn();
$activeUsers = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();

$roleLabels = [
    'admin' => 'Administrator',
    'doctor' => 'Doktor',
    'receptionist' => 'Receptionist',
];
$pageTitle = 'Dashboard';
?>
<!doctype html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | ClinicOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid">
    <div class="row min-vh-100">
      <aside class="col-12 col-md-3 col-xl-2 sidebar p-4">
        <div class="brand mb-5">Clinic<span>OS</span></div>
        <div class="small text-uppercase text-secondary mb-2">Menu utama</div>
        <nav class="nav flex-column gap-2">
          <a class="nav-link active" href="index.php">Dashboard</a>
          <a class="nav-link" href="patients/index.php">Pesakit</a>
          <a class="nav-link" href="appointments/index.php">Temujanji</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a class="nav-link" href="admin/users.php">Pengguna</a>
            <a class="nav-link" href="admin/audit-logs.php">Audit Log</a>
            <a class="nav-link" href="admin/audit-logs.php">Audit Log</a>
          <?php endif; ?>
        </nav>
      </aside>

      <main class="col p-4 p-lg-5">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
          <div>
            <p class="text-secondary mb-1">Sistem Pengurusan Klinik</p>
            <h1 class="h3 mb-1">Dashboard</h1>
            <div class="text-secondary small">
              Selamat datang, <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>
              · <?= htmlspecialchars($roleLabels[$user['role']] ?? $user['role'], ENT_QUOTES, 'UTF-8') ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill text-bg-light border">Versi 0.6.0</span>
            <form method="post" action="logout.php">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
              <button class="btn btn-outline-secondary btn-sm" type="submit">Log keluar</button>
            </form>
          </div>
        </header>

        <section class="row g-3 mb-4" aria-label="Ringkasan">
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="stat-card">
              <p class="text-secondary mb-2">Jumlah pesakit</p>
              <div class="display-6 fw-semibold"><?= number_format($patientCount) ?></div>
              <small class="text-secondary">Rekod dalam pangkalan data</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="stat-card">
              <p class="text-secondary mb-2">Temujanji hari ini</p>
              <div class="display-6 fw-semibold"><?= number_format($todayAppointments) ?></div>
              <small class="text-secondary">Mengikut tarikh pelayan</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="stat-card">
              <p class="text-secondary mb-2">Pengguna aktif</p>
              <div class="display-6 fw-semibold"><?= number_format($activeUsers) ?></div>
              <small class="text-secondary">Akaun aktif</small>
            </article>
          </div>
        </section>

        <section class="content-card">
          <h2 class="h5 mb-2">Asas keselamatan telah disediakan</h2>
          <p class="text-secondary mb-0">Modul pesakit, temujanji dan pengurusan pengguna kini menyediakan aliran operasi asas.</p>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
