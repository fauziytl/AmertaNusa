<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../koneksi.php';
require __DIR__ . '/_guard.php';

$today = date('Y-m-d');
$now = date('H:i:s');

$q = mysqli_query($conn, "
  SELECT r.reservasi_id, p.nama, r.jumlah_orang, r.status, sw.jam_mulai, sw.jam_selesai
  FROM reservasi r
  JOIN pelanggan p ON p.pelanggan_id=r.pelanggan_id
  JOIN slot_waktu sw ON sw.slot_id=r.slot_id
  WHERE r.tanggal_reservasi='$today'
  ORDER BY sw.jam_mulai
");

$ongoing=[]; $done=[]; $cancel=[];
while($r=mysqli_fetch_assoc($q)){
  $mulai=$r['jam_mulai']; $selesai=$r['jam_selesai'];
  if($r['status']==='confirmed' && $now>=$mulai && $now<=$selesai) $ongoing[]=$r;
  elseif($r['status']==='confirmed' && $now>$selesai) $done[]=$r;
  elseif($r['status']==='canceled') $cancel[]=$r;
}

echo json_encode([
  'sedang_berjalan'=>$ongoing,
  'sudah_terlaksana'=>$done,
  'dibatalkan'=>$cancel
]);
