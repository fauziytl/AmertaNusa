<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../koneksi.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Invalid method']); exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$reservasi_id = (int)($payload['reservasi_id'] ?? 0);

// Terima kedua format: action ('accept'|'reject') atau status ('confirmed'|'canceled')
$action = $payload['action'] ?? '';
$status_in = strtolower($payload['status'] ?? '');

if (!$action && $status_in === 'confirmed') $action = 'accept';
if (!$action && $status_in === 'canceled')  $action = 'reject';

if ($reservasi_id <= 0 || !in_array($action, ['accept','reject'], true)) {
  echo json_encode(['success'=>false,'error'=>'Bad request']); exit;
}

$newReservasi = ($action === 'accept') ? 'confirmed' : 'canceled';
$newBayar     = ($action === 'accept') ? 'sukses'   : 'gagal';
$newKonfirm   = ($action === 'accept') ? 'diterima' : 'ditolak';
$admin_id     = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

$conn->begin_transaction();

try {
  // 1) Update reservasi
  $st = $conn->prepare("UPDATE reservasi SET status=? WHERE reservasi_id=?");
  $st->bind_param('si', $newReservasi, $reservasi_id);
  $st->execute();
  $st->close();

  // 2) Ambil pembayaran terbaru untuk reservasi ini (kalau ada)
  $pay_id = null;
  $st = $conn->prepare("
    SELECT pembayaran_id
    FROM pembayaran
    WHERE reservasi_id = ?
    ORDER BY tanggal_pembayaran DESC
    LIMIT 1
  ");
  $st->bind_param('i', $reservasi_id);
  $st->execute();
  $st->bind_result($pay_id);
  $st->fetch();
  $st->close();

  if ($pay_id !== null) {
    // 2a) Update status di pembayaran
    $st = $conn->prepare("UPDATE pembayaran SET status=? WHERE pembayaran_id=?");
    $st->bind_param('si', $newBayar, $pay_id);
    $st->execute();
    $st->close();

    // 2b) Cek ada baris konfirmasi untuk pembayaran ini
    $konf_id = null;
    $st = $conn->prepare("SELECT konfirmasi_id FROM konfirmasi_pembayaran WHERE pembayaran_id=? ORDER BY tanggal_konfirmasi DESC LIMIT 1");
    $st->bind_param('i', $pay_id);
    $st->execute();
    $st->bind_result($konf_id);
    $st->fetch();
    $st->close();

    if ($konf_id) {
      // Update konfirmasi
      if ($admin_id) {
        $st = $conn->prepare("
          UPDATE konfirmasi_pembayaran
          SET status_konfirmasi=?, admin_id=?, tanggal_konfirmasi=NOW()
          WHERE konfirmasi_id=?
        ");
        $st->bind_param('sii', $newKonfirm, $admin_id, $konf_id);
      } else {
        $st = $conn->prepare("
          UPDATE konfirmasi_pembayaran
          SET status_konfirmasi=?, tanggal_konfirmasi=NOW()
          WHERE konfirmasi_id=?
        ");
        $st->bind_param('si', $newKonfirm, $konf_id);
      }
      $st->execute(); $st->close();
    } else {
      // Tidak ada? insert konfirmasi baru
      if ($admin_id) {
        $st = $conn->prepare("
          INSERT INTO konfirmasi_pembayaran (pembayaran_id, admin_id, status_konfirmasi, tanggal_konfirmasi)
          VALUES (?, ?, ?, NOW())
        ");
        $st->bind_param('iis', $pay_id, $admin_id, $newKonfirm);
      } else {
        $st = $conn->prepare("
          INSERT INTO konfirmasi_pembayaran (pembayaran_id, status_konfirmasi, tanggal_konfirmasi)
          VALUES (?, ?, NOW())
        ");
        $st->bind_param('is', $pay_id, $newKonfirm);
      }
      $st->execute(); $st->close();
    }
  }

  // 3) Log (opsional)
  if ($admin_id) {
    $msg = ($action === 'accept')
      ? "Reservasi #$reservasi_id diterima"
      : "Reservasi #$reservasi_id ditolak";
    $st = $conn->prepare("INSERT INTO dashboard_log (admin_id, aktivitas) VALUES (?, ?)");
    $st->bind_param('is', $admin_id, $msg);
    $st->execute(); $st->close();
  }

  $conn->commit();
  echo json_encode(['success'=>true, 'status'=>$newReservasi]);
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(['success'=>false, 'error'=>'DB error']);
}
