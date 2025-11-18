<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../koneksi.php';
header('Content-Type: application/json');

// status dari UI: all | menunggu | diterima | ditolak
$filter = $_GET['status'] ?? 'all';

// Ambil daftar reservasi (terbaru dulu)
$sql = "
SELECT
  r.reservasi_id,
  r.kode_booking,
  r.tanggal_reservasi,
  r.waktu_reservasi,
  r.slot_id,
  r.jumlah_orang,
  r.deposito,
  r.status          AS status_db,
  p.nama,
  p.email,
  p.no_wa,
  sw.label          AS slot_label
FROM reservasi r
JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
LEFT JOIN slot_waktu sw ON sw.slot_id = r.slot_id
WHERE r.kode_booking NOT LIKE 'MAN-%'
ORDER BY r.tanggal_reservasi DESC, r.waktu_reservasi DESC, r.reservasi_id DESC
";
$res = $conn->query($sql);

$out = [];
while ($row = $res->fetch_assoc()) {
  // mapping status DB -> label UI
  $st_db = $row['status_db'];
  $st_ui = 'menunggu';
  if ($st_db === 'confirmed') $st_ui = 'diterima';
  elseif ($st_db === 'canceled') $st_ui = 'ditolak';
  // unpaid & pending kita satukan ke "menunggu"
  // Walk-in (MAN-*) otomatis "diterima"
  if (strpos($row['kode_booking'], 'MAN-') === 0) $st_ui = 'diterima';

  if ($filter !== 'all' && $filter !== $st_ui) continue;

  $out[] = [
    'reservasi_id'      => (int)$row['reservasi_id'],
    'kode_booking'      => $row['kode_booking'],
    'nama'              => $row['nama'],
    'email'             => $row['email'],
    'no_wa'             => $row['no_wa'],
    'tanggal'           => $row['tanggal_reservasi'],
    'waktu'             => $row['waktu_reservasi'],
    'slot_label'        => $row['slot_label'],
    'jumlah_orang'      => (int)$row['jumlah_orang'],
    'deposito'          => (float)$row['deposito'],
    'status'            => $st_ui
  ];
}

echo json_encode($out);
