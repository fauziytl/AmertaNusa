<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../koneksi.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['error'=>'Bad request']); exit; }

// --- PERUBAHAN DI SQL ---
// 1. Menambahkan LEFT JOIN ke tabel 'pembayaran'
// 2. Menambahkan 'pb.tanggal_pembayaran' ke SELECT
// 3. Mengurutkan berdasarkan tanggal pembayaran (DESC) jika ada banyak
$sql = "
SELECT
  r.reservasi_id, r.kode_booking, r.tanggal_reservasi, r.waktu_reservasi, r.slot_id,
  r.jumlah_orang, r.deposito, r.status AS status_db,
  p.nama, p.email, p.no_wa, sw.label AS slot_label,
  pb.tanggal_pembayaran -- <-- AMBIL INI
FROM reservasi r
JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
LEFT JOIN slot_waktu sw ON sw.slot_id = r.slot_id
LEFT JOIN pembayaran pb ON pb.reservasi_id = r.reservasi_id -- <-- TAMBAH JOIN INI
WHERE r.reservasi_id = ?
ORDER BY pb.tanggal_pembayaran DESC -- <-- Ambil pembayaran terbaru
LIMIT 1 -- <-- Pastikan hanya 1 baris
";
// --- AKHIR PERUBAHAN SQL ---

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$h = $stmt->get_result()->fetch_assoc();
if (!$h) { echo json_encode(['error'=>'Not found']); exit; }

// Mapping status
$st_db = $h['status_db'];
$st_ui = 'menunggu';
if ($st_db === 'confirmed') $st_ui = 'diterima';
elseif ($st_db === 'canceled') $st_ui = 'ditolak';
if (strpos($h['kode_booking'], 'MAN-') === 0) $st_ui = 'diterima';


echo json_encode([
  'reservasi_id' => (int)$h['reservasi_id'],
  'kode_booking' => $h['kode_booking'],
  'nama' => $h['nama'],
  'email' => $h['email'],
  'no_wa' => $h['no_wa'],
  'tanggal' => $h['tanggal_reservasi'],
  'waktu' => $h['waktu_reservasi'],
  'slot_label' => $h['slot_label'],
  'jumlah_orang' => (int)$h['jumlah_orang'],
  'deposito' => (float)$h['deposito'],
  'status' => $st_ui,
  // --- TAMBAHKAN DATA INI KE JSON ---
  'tanggal_pembayaran' => $h['tanggal_pembayaran'] 
]);

?>