<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Database\Connection;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$current = Auth::requireRole($dbConfig, ['admin']);
$pdo = Connection::make($dbConfig);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $active = ($_POST['is_active'] ?? '') === '1';
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesi borang tidak sah. Sila muat semula halaman.';
    } elseif (!$id) {
        $error = 'ID pengguna tidak sah.';
    } elseif ($id === $current['id'] && !$active) {
        $error = 'Anda tidak boleh menyahaktifkan akaun sendiri.';
    } else {
        $target = $pdo->prepare('SELECT id, role, is_active FROM users WHERE id=:id LIMIT 1');
        $target->execute(['id'=>$id]);
        $record = $target->fetch();
        if (!$record) {
            $error = 'Pengguna tidak ditemui.';
        } elseif (!$active && $record['role'] === 'admin' && (bool)$record['is_active']) {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
            if ($count <= 1) {
                $error = 'Akaun Administrator aktif terakhir tidak boleh dinyahaktifkan.';
            }
        }
        if ($error === '') {
            $update = $pdo->prepare('UPDATE users SET is_active=:active WHERE id=:id');
            $update->execute(['active'=>$active ? 1 : 0, 'id'=>$id]);\n            AuditLogger::record($pdo, $current['id'], $active ? 'user.activated' : 'user.deactivated', 'user', $id);
            header('Location: users.php?updated=1');
            exit;
        }
    }
}

$users = $pdo->query('SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY role, full_name')->fetchAll();
$roleLabels = ['admin'=>'Administrator','doctor'=>'Doktor','receptionist'=>'Receptionist'];
?>
<!doctype html>
<html lang="ms">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pengguna | ClinicOS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/app.css" rel="stylesheet"></head>
<body><main class="container-fluid px-3 px-lg-5 py-4 py-lg-5">
<header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3 mb-0">Pengurusan pengguna</h1></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="../index.php">Dashboard</a><a class="btn btn-clinic" href="user-form.php">Tambah pengguna</a></div></header>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Status akaun berjaya dikemaskini.</div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Maklumat pengguna berjaya disimpan.</div><?php endif; ?>
<section class="content-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Senarai akaun</h2><span class="small text-secondary"><?= count($users) ?> pengguna</span></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>E-mel</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($users as $item): ?><tr>
<td class="fw-semibold"><?= htmlspecialchars($item['full_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($roleLabels[$item['role']] ?? $item['role'], ENT_QUOTES, 'UTF-8') ?></td>
<td><span class="badge <?= $item['is_active']?'text-bg-success':'text-bg-secondary' ?>"><?= $item['is_active']?'Aktif':'Tidak aktif' ?></span></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="user-form.php?id=<?= (int)$item['id'] ?>">Edit</a>
<form method="post" class="d-inline"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="is_active" value="<?= $item['is_active']?0:1 ?>"><button class="btn btn-sm btn-outline-secondary" <?= (int)$item['id']===$current['id']?'disabled':'' ?>><?= $item['is_active']?'Nyahaktif':'Aktifkan' ?></button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<p class="small text-secondary mb-0">Akaun Administrator aktif terakhir dilindungi daripada dinyahaktifkan. Akaun anda sendiri juga tidak boleh dinyahaktifkan.</p>
</section></main></body></html>
