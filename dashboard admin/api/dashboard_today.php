<?php
require_once '_guard.php';
require_once __DIR__ . '/../../koneksi.php';

header('Content-Type: application/json; charset=utf-8');

/* Samakan zona waktu MySQL supaya CURDATE() = WIB */
mysqli_query($conn, "SET time_zone = '+07:00'");

/*
  Ambil reservasi HARI INI yang SUDAH DITERIMA ADMIN (status = confirmed).
  Jika pelanggan tidak ada (walk-in/manual), tampilkan nama “Walk-in”.
*/
$sql = "
SELECT 
  r.reservasi_id,
  r.kode_booking,
  r.tanggal_reservasi,
  r.waktu_reservasi,
  COALESCE(sw.label, DATE_FORMAT(r.waktu_reservasi, '%H:%i')) AS slot_label,
  r.status,
  r.jumlah_orang,
  COALESCE(p.nama, 'Walk-in') AS nama,
  p.email,
  p.no_wa,
  r.meja_id
FROM reservasi r
LEFT JOIN pelanggan  p  ON p.pelanggan_id = r.pelanggan_id
LEFT JOIN slot_waktu sw ON sw.slot_id     = r.slot_id
WHERE r.tanggal_reservasi = CURDATE()
  AND r.status = 'confirmed'
  AND r.kode_booking NOT LIKE 'MAN-%' 
ORDER BY COALESCE(sw.jam_mulai, r.waktu_reservasi), r.reservasi_id
LIMIT 5
";

$res = $conn->query($sql);
if (!$res) {
  echo json_encode(['error' => $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = [
    'reservasi_id'      => (int)$row['reservasi_id'],
    'kode_booking'      => $row['kode_booking'],
    'tanggal_reservasi' => $row['tanggal_reservasi'],
    'waktu_reservasi'   => $row['waktu_reservasi'],
    'slot_label'        => $row['slot_label'],
    'status'            => ucfirst($row['status']),
    'jumlah_orang'      => (int)$row['jumlah_orang'],
    'nama'              => $row['nama'],
    'email'             => $row['email'],
    'no_wa'             => $row['no_wa'],
    'meja'              => $row['meja_id'] ? "Meja {$row['meja_id']}" : '-',
  ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
