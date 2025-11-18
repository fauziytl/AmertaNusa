<?php
require_once '_guard.php';
require_once __DIR__ . '/../../koneksi.php';

header('Content-Type: application/json');

// Ambil 50 log terbaru
$sql = "
SELECT dl.aktivitas, dl.timestamp, a.nama AS admin_nama
FROM dashboard_log dl
LEFT JOIN admin a ON a.admin_id = dl.admin_id
ORDER BY dl.timestamp DESC
LIMIT 50
";
$res = $conn->query($sql);

$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = [
    'aktivitas' => $row['aktivitas'] . ($row['admin_nama'] ? " (oleh {$row['admin_nama']})" : ''),
    'timestamp' => $row['timestamp']
  ];
}

echo json_encode($out);
