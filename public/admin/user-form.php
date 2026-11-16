<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Database\Connection;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__, 2) . '/app/bootstrap.php';
$currentAdmin = Auth::requireRole($dbConfig, ['admin']);
$pdo = Connection::make($dbConfig);
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$userData = ['full_name'=>'','email'=>'','role'=>'receptionist','is_active'=>'1'];
$error = '';
if ($id) {
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, is_active FROM users WHERE id=:id LIMIT 1');
    $stmt->execute(['id'=>$id]);
    $existing = $stmt->fetch();
    if (!$existing) { http_response_code(404); exit('User not found.'); }
    $userData = array_merge($userData, $existing);
    $userData['is_active'] = (string)(int)$existing['is_active'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
        $error = 'Sesi borang tidak sah. Sila muat semula halaman.';
    } else {
        $userData['full_name'] = trim((string)($_POST['full_name'] ?? ''));
        $userData['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
        $userData['role'] = (string)($_POST['role'] ?? '');
        $userData['is_active'] = (string)($_POST['is_active'] ?? '1');
        $password = (string)($_POST['password'] ?? '');
        $roles = ['admin','doctor','receptionist'];
        if ($userData['full_name']==='' || strlen($userData['full_name'])>600) {
            $error = 'Nama wajib diisi dan tidak melebihi 150 aksara.';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL) || strlen($userData['email'])>190) {
            $error = 'Alamat e-mel tidak sah.';
        } elseif (!in_array($userData['role'], $roles, true)) {
            $error = 'Role pengguna tidak sah.';
        } elseif (!in_array($userData['is_active'], ['0','1'], true)) {
            $error = 'Status akaun tidak sah.';
        } elseif (!$id && strlen($password)<12) {
            $error = 'Kata laluan awal mesti sekurang-kurangnya 12 aksara.';
        } elseif ($password!=='' && strlen($password)<12) {
            $error = 'Kata laluan baharu mesti sekurang-kurangnya 12 aksara.';
        } else {
            try {
                if ($id) {
                    $currentStmt = $pdo->prepare('SELECT role, is_active FROM users WHERE id=:id');
                    $currentStmt->execute(['id'=>$id]);
                    $current = $currentStmt->fetch();
                    if (!$current) { throw new RuntimeException('User not found.'); }
                    if ($id === $currentAdmin['id'] && ($userData['role'] !== 'admin' || $userData['is_active'] === '0')) {
                        $error = 'Anda tidak boleh menukar role atau menyahaktifkan akaun sendiri.';
                    }
                    if ($current['role']==='admin' && (bool)$current['is_active']
                        && ($userData['role']!=='admin' || $userData['is_active']==='0')) {
                        $adminCount=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
                        if ($adminCount<=1) {
                            $error='Administrator aktif terakhir tidak boleh ditukar role atau dinyahaktifkan.';
                        }
                    }
                    if ($error==='') {
                        $params=['full_name'=>$userData['full_name'],'email'=>$userData['email'],'role'=>$userData['role'],'is_active'=>(int)$userData['is_active'],'id'=>$id];
                        $sql='UPDATE users SET full_name=:full_name, email=:email, role=:role, is_active=:is_active';
                        if ($password!=='') { $sql.=', password_hash=:password_hash'; $params['password_hash']=password_hash($password,PASSWORD_DEFAULT); }
                        $sql.=' WHERE id=:id';
                        $save=$pdo->prepare($sql);
                        $save->execute($params);\n                        AuditLogger::record($pdo, $currentAdmin['id'], 'user.updated', 'user', $id);
                    }
                } else {
                    $save=$pdo->prepare('INSERT INTO users (full_name,email,password_hash,role,is_active) VALUES (:full_name,:email,:password_hash,:role,:is_active)');
                    $save->execute(['full_name'=>$userData['full_name'],'email'=>$userData['email'],'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'role'=>$userData['role'],'is_active'=>(int)$userData['is_active']]);\n                    AuditLogger::record($pdo, $currentAdmin['id'], 'user.created', 'user', (int) $pdo->lastInsertId());
                }
                if ($error==='') { header('Location: users.php?saved=1'); exit; }
            } catch (PDOException $exception) {
                $error = 'Maklumat tidak dapat disimpan. Pastikan e-mel belum digunakan.';
            } catch (RuntimeException $exception) {
                $error = 'Pengguna tidak ditemui.';
            }
        }
    }
}
$roles=['admin'=>'Administrator','doctor'=>'Doktor','receptionist'=>'Receptionist'];
?>
<!doctype html>
<html lang="ms">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $id?'Edit':'Tambah' ?> pengguna | ClinicOS</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/app.css" rel="stylesheet"></head>
<body><main class="container py-4 py-lg-5" style="max-width:760px">
<header class="d-flex justify-content-between align-items-center mb-4"><div><div class="brand">Clinic<span>OS</span></div><h1 class="h3 mt-3"><?= $id?'Kemaskini pengguna':'Tambah pengguna' ?></h1></div><a class="btn btn-outline-secondary" href="users.php">Kembali</a></header>
<section class="content-card"><?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(),ENT_QUOTES,'UTF-8') ?>"><?php if($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
<div class="mb-3"><label class="form-label" for="full_name">Nama penuh *</label><input class="form-control" id="full_name" name="full_name" maxlength="150" required value="<?= htmlspecialchars($userData['full_name'],ENT_QUOTES,'UTF-8') ?>"></div>
<div class="mb-3"><label class="form-label" for="email">E-mel *</label><input class="form-control" type="email" id="email" name="email" maxlength="190" required value="<?= htmlspecialchars($userData['email'],ENT_QUOTES,'UTF-8') ?>"></div>
<div class="mb-3"><label class="form-label" for="role">Role *</label><select class="form-select" id="role" name="role" required><?php foreach($roles as $key=>$label): ?><option value="<?= $key ?>" <?= $userData['role']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label" for="password">Kata laluan <?= $id?'baharu (kosongkan jika tidak ditukar)':'awal *' ?></label><input class="form-control" type="password" id="password" name="password" minlength="12" <?= $id?'':'required' ?> autocomplete="new-password"><div class="form-text">Minimum 12 aksara. Kata laluan disimpan menggunakan password_hash.</div></div>
<div class="mb-4"><label class="form-label" for="is_active">Status akaun</label><select class="form-select" id="is_active" name="is_active"><option value="1" <?= $userData['is_active']==='1'?'selected':'' ?>>Aktif</option><option value="0" <?= $userData['is_active']==='0'?'selected':'' ?>>Tidak aktif</option></select></div>
<div class="d-flex gap-2"><button class="btn btn-clinic">Simpan pengguna</button><a class="btn btn-outline-secondary" href="users.php">Batal</a></div>
</form></section></main></body></html>
