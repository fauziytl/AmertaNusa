<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../koneksi.php';
header('Content-Type: application/json');

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$slot_id = (int)($_GET['slot'] ?? 1);

// Ambil semua meja
$meja = [];
$resMeja = $conn->query("SELECT meja_id, jumlah FROM meja ORDER BY meja_id ASC");
while ($row = $resMeja->fetch_assoc()) {
  $meja[(int)$row['meja_id']] = [
    'meja_id' => (int)$row['meja_id'],
    'status'  => 'tersedia' // Semua meja awalnya 'tersedia'
  ];
}

// Ambil alokasi untuk tanggal+slot tsb, join reservasi untuk tahu jenisnya
$sql = "
SELECT am.meja_id, r.status AS r_status, r.kode_booking, p.nama, r.jumlah_orang
FROM alokasi_meja am
LEFT JOIN reservasi r ON r.reservasi_id = am.reservasi_id
LEFT JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
WHERE am.tanggal = ? AND am.slot_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $tanggal, $slot_id);
$stmt->execute();
$r = $stmt->get_result();

$mejaTerisi = 0;
$reservasiDiterima = 0;

// --- AWAL LOGIKA BARU YANG DIPERBAIKI ---
while ($row = $r->fetch_assoc()) {
  $mid = (int)$row['meja_id'];
  if (!isset($meja[$mid])) continue;

  $kode_booking = $row['kode_booking'] ?? '';
  $status_db = $row['r_status'] ?? ''; // e.g., 'unpaid', 'confirmed', 'canceled'

  // Cek jika manual (walk-in)
  // (Reservasi 'MAN-' dibuat dengan status 'confirmed' oleh update_table_status.php)
  if (strpos($kode_booking, 'MAN-') === 0) {
    // Kuning = manual (walk-in)
    $meja[$mid]['status'] = 'manual';
    $mejaTerisi++;
  }
  // Cek jika reservasi online YANG SUDAH DIKONFIRMASI
  else if ($status_db === 'confirmed') {
    // Merah = reservasi confirmed
    $meja[$mid]['status'] = 'reservasi';
    $meja[$mid]['nama_pelanggan'] = $row['nama'] ?? '';
    $meja[$mid]['jumlah_orang'] = (int)($row['jumlah_orang'] ?? 0);
    $reservasiDiterima++;
    $mejaTerisi++;
  }
  // JIKA status 'unpaid', 'pending', atau 'canceled',
  // JANGAN LAKUKAN APA-APA. Biarkan status meja tetap 'tersedia'
}
// --- AKHIR LOGIKA BARU ---

echo json_encode([
  'total_meja' => count($meja),
  'meja_terisi' => $mejaTerisi,
  'reservasi_diterima' => $reservasiDiterima,
  'daftar_meja' => array_values($meja)
]);