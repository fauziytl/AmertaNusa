<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../koneksi.php';
require __DIR__ . '/_guard.php';

$q = mysqli_query($conn, "SELECT * FROM dashboard_log ORDER BY timestamp DESC LIMIT 50");
$data=[];
while($r=mysqli_fetch_assoc($q)){
  $data[]=[
    'log_id'=>$r['log_id'],
    'admin_id'=>$r['admin_id'],
    'aktivitas'=>$r['aktivitas'],
    'timestamp'=>$r['timestamp']
  ];
}
echo json_encode(['logs'=>$data]);
