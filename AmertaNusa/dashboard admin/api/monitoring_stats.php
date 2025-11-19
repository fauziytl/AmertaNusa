<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../koneksi.php';
require __DIR__ . '/_guard.php';

// Hitung jumlah untuk setiap status
$menunggu = mysqli_fetch_assoc(mysqli_query($conn, 
  "SELECT COUNT(*) as total FROM reservasi WHERE status IN ('unpaid', 'pending')"
))['total'] ?? 0;

$diterima = mysqli_fetch_assoc(mysqli_query($conn, 
  "SELECT COUNT(*) as total FROM reservasi WHERE status = 'confirmed'"
))['total'] ?? 0;

$ditolak = mysqli_fetch_assoc(mysqli_query($conn, 
  "SELECT COUNT(*) as total FROM reservasi WHERE status = 'canceled'"
))['total'] ?? 0;

echo json_encode([
  'success' => true,
  'menunggu' => $menunggu,
  'diterima' => $diterima,
  'ditolak' => $ditolak
]);