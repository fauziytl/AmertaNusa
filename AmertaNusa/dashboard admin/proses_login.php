<?php
session_start();

// PENTING: karena file ini ada di dalam folder dashboard,
// koneksi.php ada satu level di atas
require __DIR__ . '/../koneksi.php';

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
  echo "<script>alert('Email dan password harus diisi!');history.back();</script>";
  exit;
}

// pakai prepared statement
$stmt = mysqli_prepare($conn, "SELECT admin_id, nama, email, password, role FROM admin WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res) {
  echo "<script>alert('Kesalahan server: ".mysqli_error($conn)."');history.back();</script>";
  exit;
}

if ($admin = mysqli_fetch_assoc($res)) {
  $hash = (string)$admin['password'];
  $ok = false;

  // kalau password di DB sudah di-hash ($2y$...), pakai password_verify
  if (strlen($hash) >= 7 && $hash[0] === '$') {
    $ok = password_verify($password, $hash);
  } else {
    // DB kamu sekarang pakai plain: "Admin123" → cocokkan secara case-sensitive
    $ok = hash_equals($hash, $password);
  }

  if ($ok) {
    $_SESSION['admin_id'] = (int)$admin['admin_id'];
    $_SESSION['email']    = $admin['email'];
    $_SESSION['nama']     = $admin['nama'];
    $_SESSION['role']     = $admin['role'] ?? 'admin';

    // langsung ke dashboard (file berada di folder yang sama)
    header("Location: index.html");
    exit;
  } else {
    echo "<script>alert('Password salah!');history.back();</script>";
    exit;
  }
} else {
  echo "<script>alert('Email tidak ditemukan!');history.back();</script>";
  exit;
}
