<?php
declare(strict_types=1);

session_start();

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
          <a class="nav-link disabled" href="#" aria-disabled="true">Pesakit</a>
          <a class="nav-link disabled" href="#" aria-disabled="true">Temujanji</a>
          <a class="nav-link disabled" href="#" aria-disabled="true">Pengguna</a>
        </nav>
      </aside>

      <main class="col p-4 p-lg-5">
        <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
          <div>
            <p class="text-secondary mb-1">Sistem Pengurusan Klinik</p>
            <h1 class="h3 mb-0">Dashboard</h1>
          </div>
          <span class="badge rounded-pill text-bg-light border">Versi 0.1.0</span>
        </header>

        <section class="row g-3 mb-4" aria-label="Ringkasan">
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="stat-card">
              <p class="text-secondary mb-2">Jumlah pesakit</p>
              <div class="display-6 fw-semibold">—</div>
              <small class="text-secondary">Belum disambungkan ke pangkalan data</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="stat-card">
              <p class="text-secondary mb-2">Temujanji hari ini</p>
              <div class="display-6 fw-semibold">—</div>
              <small class="text-secondary">Belum disambungkan ke pangkalan data</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="stat-card">
              <p class="text-secondary mb-2">Pengguna aktif</p>
              <div class="display-6 fw-semibold">—</div>
              <small class="text-secondary">Belum disambungkan ke pangkalan data</small>
            </article>
          </div>
        </section>

        <section class="content-card">
          <h2 class="h5 mb-2">ClinicOS sedang dibangunkan</h2>
          <p class="text-secondary mb-0">Ini ialah rangka awal antara muka. Modul pesakit, temujanji, log masuk dan kawalan akses akan dibina secara berperingkat.</p>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
