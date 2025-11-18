<?php
require __DIR__ . '/../../koneksi.php';
require __DIR__ . '/_guard.php';

header('Content-Type: application/json');

// === 1. Total reservasi (ONLINE SAJA, tanpa manual/walk-in) ===
$total_reservasi = $conn->query("
  SELECT COUNT(*) AS total
  FROM reservasi r
  WHERE r.pelanggan_id IS NOT NULL
    AND (r.kode_booking IS NULL OR r.kode_booking NOT LIKE 'MAN-%')
")->fetch_assoc()['total'] ?? 0;

// === 2. Meja terisi hari ini (semua meja yg terisi — online & manual) ===
$meja_terisi = $conn->query("
  SELECT COUNT(DISTINCT am.meja_id) AS terisi
  FROM alokasi_meja am
  WHERE am.tanggal = CURDATE()
")->fetch_assoc()['terisi'] ?? 0;

// === 3. Total meja ===
$total_meja = $conn->query("SELECT COUNT(*) AS total FROM meja")->fetch_assoc()['total'] ?? 0;

// === 4. Pendapatan hari ini (reservasi via web, pembayaran dilakukan hari ini) ===
$pendapatan = $conn->query("
  SELECT COALESCE(SUM(r.deposito), 0) AS total
  FROM reservasi r
  JOIN pembayaran p ON p.reservasi_id = r.reservasi_id
  LEFT JOIN konfirmasi_pembayaran k
    ON k.pembayaran_id = p.pembayaran_id
  WHERE DATE(p.tanggal_pembayaran) = CURDATE()
    AND r.pelanggan_id IS NOT NULL
    AND (r.kode_booking IS NULL OR r.kode_booking NOT LIKE 'MAN-%')
    AND (
         r.status IN ('pending', 'confirmed')
         OR k.status_konfirmasi = 'diterima'
    )
")->fetch_assoc()['total'] ?? 0;

// === 5. Kirim hasil dalam JSON ===
echo json_encode([
  'total_reservasi' => (int)$total_reservasi,
  'meja_terisi'     => (int)$meja_terisi,
  'total_meja'      => (int)$total_meja,
  'pendapatan'      => (float)$pendapatan,
]);
?>
