<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../koneksi.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['error' => 'Invalid method']); exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$meja_id = intval($payload['meja_id'] ?? 0);
$tanggal = $payload['tanggal'] ?? date('Y-m-d');
$slot_id = intval($payload['slot_id'] ?? 0);

if ($meja_id <= 0 || $slot_id <= 0 || !$tanggal) {
  echo json_encode(['error' => 'Bad request']); exit;
}

// Cek meja sudah teralokasi belum untuk tanggal+slot itu
$cek = $conn->prepare("SELECT 1 FROM alokasi_meja WHERE meja_id=? AND tanggal=? AND slot_id=?");
$cek->bind_param('isi', $meja_id, $tanggal, $slot_id);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
  echo json_encode(['error' => 'Meja sudah teralokasi pada tanggal & slot ini']); exit;
}
$cek->close();

// Ambil jam mulai utk isi waktu_reservasi
$w = $conn->prepare("SELECT jam_mulai FROM slot_waktu WHERE slot_id=?");
$w->bind_param('i', $slot_id);
$w->execute();
$w->bind_result($jam_mulai);
$w->fetch();
$w->close();
if (!$jam_mulai) $jam_mulai = '00:00:00';

// Pastikan ada pelanggan “Walk-in”
$walkinNama = 'Walk-in';
$walkin = $conn->prepare("SELECT pelanggan_id FROM pelanggan WHERE nama=? LIMIT 1");
$walkin->bind_param('s', $walkinNama);
$walkin->execute();
$walkin->bind_result($pelanggan_id);
if (!$walkin->fetch()) { // belum ada → buat
  $walkin->close();
  $insP = $conn->prepare("INSERT INTO pelanggan (nama, email, no_wa) VALUES (?, NULL, '-')");
  $insP->bind_param('s', $walkinNama);
  $insP->execute();
  $pelanggan_id = $insP->insert_id;
  $insP->close();
} else {
  $walkin->close();
}

// Buat reservasi “manual” (kode MAN-…)
$kode = 'MAN-' . date('YmdHis');
$jumlah_orang = 0; // walk-in tidak dihitung ke deposit
$deposito = 0.00;
$status = 'confirmed';

$insR = $conn->prepare("
INSERT INTO reservasi (pelanggan_id, meja_id, kode_booking, tanggal_reservasi, waktu_reservasi, slot_id, jumlah_orang, deposito, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insR->bind_param('iisssiids', $pelanggan_id, $meja_id, $kode, $tanggal, $jam_mulai, $slot_id, $jumlah_orang, $deposito, $status);
$insR->execute();
$reservasi_id = $insR->insert_id;
$insR->close();

// Alokasikan meja untuk tanggal+slot itu
$insA = $conn->prepare("INSERT INTO alokasi_meja (reservasi_id, meja_id, tanggal, slot_id) VALUES (?, ?, ?, ?)");
$insA->bind_param('iisi', $reservasi_id, $meja_id, $tanggal, $slot_id);
$insA->execute();
$insA->close();

echo json_encode(['success' => true, 'meja_id' => $meja_id, 'status' => 'manual']);
