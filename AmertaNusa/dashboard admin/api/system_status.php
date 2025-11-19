<?php
require_once '_guard.php';
header('Content-Type: application/json');

$okDb = true;
try {
  // kalau _guard.php sudah koneksi, ini otomatis OK.
  // Jika koneksi pakai variabel $conn, bisa cek ping:
  if (isset($conn)) { $okDb = @$conn->ping(); }
} catch (\Throwable $e) { $okDb = false; }

// Notifikasi email belum kamu implement → tampilkan false/pending
echo json_encode([
  'api' => true,
  'database' => (bool)$okDb,
  'notifikasi' => false
]);
