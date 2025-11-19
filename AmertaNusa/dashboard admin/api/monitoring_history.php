<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../koneksi.php';
require __DIR__ . '/_guard.php';

$today = date('Y-m-d');
$now = date('H:i:s');

// SEDANG BERJALAN: hari ini & jam sekarang masih di dalam slot
$sedangBerjalan = [];
$qBerjalan = mysqli_query($conn, "
  SELECT 
    r.reservasi_id,
    r.kode_booking,
    p.nama,
    p.no_wa,
    p.email,
    r.tanggal_reservasi,
    r.waktu_reservasi,
    r.jumlah_orang,
    r.status,
    sw.label as slot_label,
    sw.jam_mulai,
    sw.jam_selesai,
    m.meja_id
  FROM reservasi r
  JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
  LEFT JOIN slot_waktu sw ON sw.slot_id = r.slot_id
  LEFT JOIN alokasi_meja am ON am.reservasi_id = r.reservasi_id
  LEFT JOIN meja m ON m.meja_id = am.meja_id
  WHERE r.status = 'confirmed'
    AND r.tanggal_reservasi = '$today'
    AND sw.jam_mulai <= '$now'
    AND sw.jam_selesai >= '$now'
    AND r.kode_booking NOT LIKE 'MAN-%' -- <-- TAMBAHAN
  ORDER BY sw.jam_mulai
");

while ($row = mysqli_fetch_assoc($qBerjalan)) {
  $sedangBerjalan[] = [
    'reservasi_id' => $row['reservasi_id'],
    'kode_booking' => $row['kode_booking'],
    'nama' => $row['nama'],
    'no_wa' => $row['no_wa'],
    'email' => $row['email'],
    'tanggal' => $row['tanggal_reservasi'],
    'waktu' => $row['waktu_reservasi'],
    'slot_label' => $row['slot_label'],
    'jumlah_orang' => $row['jumlah_orang'],
    'meja' => $row['meja_id'] ? 'Meja ' . $row['meja_id'] : '-',
    'status' => $row['status']
  ];
}

// SELESAI: tanggal sudah lewat ATAU hari ini tapi jam sudah lewat
$selesai = [];
$qSelesai = mysqli_query($conn, "
  SELECT 
    r.reservasi_id,
    r.kode_booking,
    p.nama,
    p.no_wa,
    p.email,
    r.tanggal_reservasi,
    r.waktu_reservasi,
    r.jumlah_orang,
    r.status,
    sw.label as slot_label,
    sw.jam_mulai,
    sw.jam_selesai,
    m.meja_id
  FROM reservasi r
  JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
  LEFT JOIN slot_waktu sw ON sw.slot_id = r.slot_id
  LEFT JOIN alokasi_meja am ON am.reservasi_id = r.reservasi_id
  LEFT JOIN meja m ON m.meja_id = am.meja_id
  WHERE r.status = 'confirmed'
    AND (
      r.tanggal_reservasi < '$today'
      OR (r.tanggal_reservasi = '$today' AND sw.jam_selesai < '$now')
    )
    AND r.kode_booking NOT LIKE 'MAN-%' -- <-- TAMBAHAN
  ORDER BY r.tanggal_reservasi DESC, sw.jam_mulai DESC
  LIMIT 50
");

while ($row = mysqli_fetch_assoc($qSelesai)) {
  $selesai[] = [
    'reservasi_id' => $row['reservasi_id'],
    'kode_booking' => $row['kode_booking'],
    'nama' => $row['nama'],
    'no_wa' => $row['no_wa'],
    'email' => $row['email'],
    'tanggal' => $row['tanggal_reservasi'],
    'waktu' => $row['waktu_reservasi'],
    'slot_label' => $row['slot_label'],
    'jumlah_orang' => $row['jumlah_orang'],
    'meja' => $row['meja_id'] ? 'Meja ' . $row['meja_id'] : '-',
    'status' => $row['status']
  ];
}

// DITOLAK: semua yang status canceled
$ditolak = [];
$qDitolak = mysqli_query($conn, "
  SELECT 
    r.reservasi_id,
    r.kode_booking,
    p.nama,
    p.no_wa,
    p.email,
    r.tanggal_reservasi,
    r.waktu_reservasi,
    r.jumlah_orang,
    r.status,
    sw.label as slot_label,
    m.meja_id
  FROM reservasi r
  JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
  LEFT JOIN slot_waktu sw ON sw.slot_id = r.slot_id
  LEFT JOIN alokasi_meja am ON am.reservasi_id = r.reservasi_id
  LEFT JOIN meja m ON m.meja_id = am.meja_id
  WHERE r.status = 'canceled'
    AND r.kode_booking NOT LIKE 'MAN-%' -- <-- TAMBAHAN
  ORDER BY r.tanggal_reservasi DESC
  LIMIT 50
");

while ($row = mysqli_fetch_assoc($qDitolak)) {
  $ditolak[] = [
    'reservasi_id' => $row['reservasi_id'],
    'kode_booking' => $row['kode_booking'],
    'nama' => $row['nama'],
    'no_wa' => $row['no_wa'],
    'email' => $row['email'],
    'tanggal' => $row['tanggal_reservasi'],
    'waktu' => $row['waktu_reservasi'],
    'slot_label' => $row['slot_label'],
    'jumlah_orang' => $row['jumlah_orang'],
    'meja' => $row['meja_id'] ? 'Meja ' . $row['meja_id'] : '-',
    'status' => $row['status']
  ];
}

echo json_encode([
  'success' => true,
  'sedang_berjalan' => $sedangBerjalan,
  'selesai' => $selesai,
  'ditolak' => $ditolak
]);
?>