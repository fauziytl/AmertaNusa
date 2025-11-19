<?php
require __DIR__ . '/../../koneksi.php';
require __DIR__ . '/_guard.php';

header('Content-Type: application/json');

// helper: array tanggal 7 hari terakhir (hari ini paling kanan)
$labels = [];
for ($i = 6; $i >= 0; $i--) {
  $d = new DateTime();
  $d->modify("-$i day");
  $labels[] = $d->format('Y-m-d');
}

// ================== Trend Reservasi (7 hari) – online saja ==================
$sqlTrend = "
  SELECT DATE(tanggal_reservasi) d, COUNT(*) c
  FROM reservasi r
  WHERE r.pelanggan_id IS NOT NULL
    AND (r.kode_booking IS NULL OR r.kode_booking NOT LIKE 'MAN-%')
    AND DATE(tanggal_reservasi) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
  GROUP BY DATE(tanggal_reservasi)
";
$trendRaw = $conn->query($sqlTrend);
$trendMap = array_fill_keys($labels, 0);
while ($row = $trendRaw->fetch_assoc()) {
  $trendMap[$row['d']] = (int)$row['c'];
}
$trendData = array_values($trendMap);

// ================== Pendapatan Mingguan (7 hari) ==================
// dihitung dari tanggal pembayaran (meski tanggal reservasi beda)
$sqlRev = "
  SELECT DATE(p.tanggal_pembayaran) d, SUM(r.deposito) c
  FROM reservasi r
  JOIN pembayaran p ON p.reservasi_id = r.reservasi_id
  LEFT JOIN konfirmasi_pembayaran k ON k.pembayaran_id = p.pembayaran_id
  WHERE DATE(p.tanggal_pembayaran) BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    AND r.pelanggan_id IS NOT NULL
    AND (r.kode_booking IS NULL OR r.kode_booking NOT LIKE 'MAN-%')
    AND (r.status IN ('pending','confirmed') OR k.status_konfirmasi='diterima')
  GROUP BY DATE(p.tanggal_pembayaran)
";
$revRaw = $conn->query($sqlRev);
$revMap = array_fill_keys($labels, 0);
while ($row = $revRaw->fetch_assoc()) {
  $revMap[$row['d']] = (int)$row['c'];
}
$revData = array_values($revMap);

// ================== Distribusi Status (ALL time / bisa kamu ubah) ==================
$dist = ['unpaid'=>0,'pending'=>0,'confirmed'=>0,'canceled'=>0];
$res = $conn->query("SELECT status, COUNT(*) c FROM reservasi GROUP BY status");
while ($r = $res->fetch_assoc()) {
  $key = strtolower($r['status']);
  if (isset($dist[$key])) $dist[$key] = (int)$r['c'];
}

// ================== Jam Sibuk (hari ini, by slot) ==================
$slotLabels = [];
$slotCount  = [];
$qr = $conn->query("SELECT slot_id, label FROM slot_waktu ORDER BY slot_id");
$slotIndex = [];
while ($s = $qr->fetch_assoc()) {
  $slotLabels[] = $s['label'];
  $slotCount[$s['slot_id']] = 0;
  $slotIndex[$s['slot_id']] = $s['label'];
}
// hitung dari reservasi hari ini (online & manual sama-sama boleh)
$busy = $conn->query("
  SELECT slot_id, COUNT(*) c
  FROM reservasi
  WHERE DATE(tanggal_reservasi) = CURDATE() 
  GROUP BY slot_id
");
while ($b = $busy->fetch_assoc()) {
  if (isset($slotCount[$b['slot_id']])) $slotCount[$b['slot_id']] = (int)$b['c'];
}
$peakData = [];
foreach ($slotCount as $sid => $cnt) $peakData[] = $cnt;

// format label human readable (dd Mon)
function human($ymd) {
  $d = DateTime::createFromFormat('Y-m-d', $ymd);
  return $d->format('d M');
}

echo json_encode([
  'trend' => [
    'labels' => array_map('human', $labels),
    'data'   => $trendData
  ],
  'revenue' => [
    'labels' => array_map('human', $labels),
    'data'   => $revData
  ],
  'status_distribution' => $dist,
  'peak_hours' => [
    'labels' => $slotLabels,
    'data'   => $peakData
  ],
]);
