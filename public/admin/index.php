<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = Auth::requireRole($dbConfig, ['admin']);
?>
<!doctype html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pengurusan pengguna | ClinicOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
  <main class="container py-5">
    <header class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <div class="brand">Clinic<span>OS</span></div>
        <h1 class="h3 mt-3">Kawasan pentadbir</h1>
        <p class="text-secondary mb-0">Halaman ini hanya boleh dibuka oleh role Administrator.</p>
      </div>
      <a href="../index.php" class="btn btn-outline-secondary">Dashboard</a>
    </header>
    <section class="content-card">
      <h2 class="h5">Pengurusan pengguna</h2>
      <p class="text-secondary mb-0">Kawalan akses pentadbir aktif. Fungsi tambah, kemas kini dan nyahaktif akaun akan dibina dalam modul pengurusan pengguna.</p>
    </section>
  </main>
</body>
</html>
