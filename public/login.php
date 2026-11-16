<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__) . '/app/bootstrap.php';

if (Auth::check($dbConfig)) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesi borang tidak sah. Sila muat semula halaman.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $password !== ''
            && Auth::attempt($email, $password, $dbConfig)) {
            header('Location: index.php');
            exit;
        }

        $error = 'E-mel atau kata laluan tidak sah.';
    }
}
?>
<!doctype html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log masuk | ClinicOS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="login-page">
  <main class="login-card">
    <div class="brand text-center mb-2">Clinic<span>OS</span></div>
    <p class="text-secondary text-center mb-4">Log masuk ke sistem klinik</p>
    <?php if ($error !== ''): ?>
      <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
      <div class="mb-3">
        <label for="email" class="form-label">E-mel</label>
        <input class="form-control" id="email" name="email" type="email" autocomplete="username" required>
      </div>
      <div class="mb-4">
        <label for="password" class="form-label">Kata laluan</label>
        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-clinic w-100" type="submit">Log masuk</button>
    </form>
  </main>
</body>
</html>
