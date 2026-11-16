<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Database\Connection;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$user = Auth::requireLogin($dbConfig);
$pdo = Connection::make($dbConfig);

$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT id, medical_record_no, full_name, date_of_birth, phone, created_at
        FROM patients';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE full_name LIKE :name OR medical_record_no LIKE :mrn OR phone LIKE :phone';
    $term = '%' . $q . '%';
    $params = ['name' => $term, 'mrn' => $term, 'phone' => $term];
}
$sql .= ' ORDER BY created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pesakit | ClinicOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
<main class="container py-4 py-lg-5">
  <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3 mb-0">Direktori pesakit</h1></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="../index.php">Dashboard</a><a class="btn btn-clinic" href="form.php">Daftar pesakit</a></div>
  </header>
  <form class="content-card mb-3" method="get">
    <label class="form-label" for="q">Carian pesakit</label>
    <div class="input-group"><input class="form-control" id="q" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nama, nombor rekod atau telefon"><button class="btn btn-outline-secondary">Cari</button><?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="index.php">Reset</a><?php endif; ?></div>
  </form>
  <section class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Senarai pesakit</h2><span class="text-secondary small"><?= count($patients) ?> rekod dipaparkan</span></div>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>No. rekod</th><th>Nama pesakit</th><th>Tarikh lahir</th><th>Telefon</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($patients as $patient): ?>
          <tr>
            <td class="text-nowrap"><?= htmlspecialchars($patient['medical_record_no'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($patient['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($patient['date_of_birth'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($patient['phone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="view.php?id=<?= (int) $patient['id'] ?>">Lihat</a> <a class="btn btn-sm btn-outline-primary" href="form.php?id=<?= (int) $patient['id'] ?>">Edit</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$patients): ?><tr><td colspan="5" class="text-center text-secondary py-4">Tiada rekod ditemui.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <p class="small text-secondary mb-0">Paparan dihadkan kepada 100 rekod terkini. Gunakan carian untuk mencari rekod lain.</p>
  </section>
</main>
</body>
</html>
