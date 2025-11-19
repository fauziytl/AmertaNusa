<?php
include 'koneksi.php';

$tanggal = $_POST['tanggal_reservasi'] ?? '';
$waktu   = $_POST['waktu_reservasi'] ?? '';
$jumlah  = $_POST['jumlah_orang'] ?? 0;

if ($tanggal == '' || $waktu == '' || $jumlah <= 0) {
    echo "<p style='color:red;'>Semua data wajib diisi!</p>";
    exit;
}

$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total_meja FROM meja");
$data_total = mysqli_fetch_assoc($result_total);
$total_meja = $data_total['total_meja'] ?? 0;

// hitung meja yang sudah dipesan di tanggal & waktu ini
$sql = "SELECT COUNT(*) AS total_dipesan 
        FROM reservasi
        WHERE tanggal_reservasi = '$tanggal'
          AND waktu_reservasi = '$waktu'
          AND status != 'canceled'";

$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);
$total_dipesan = $data['total_dipesan'] ?? 0;

// hitung sisa meja
$sisa_meja = $total_meja - $total_dipesan;

if ($sisa_meja > 0) {
    echo "<p>✅ Masih tersedia <b>$sisa_meja meja</b> untuk pukul <b>$waktu</b>.</p>";
    echo "<form action='identitas.html' method='POST'>
            <input type='hidden' name='tanggal_reservasi' value='$tanggal'>
            <input type='hidden' name='waktu_reservasi' value='$waktu'>
            <input type='hidden' name='jumlah_orang' value='$jumlah'>
            <button type='submit' class='available w-100 py-3 border-0'>Booking Sekarang</button>
          </form>";
} else {
    echo "<p style='color:red;'>❌ Semua meja untuk jam tersebut sudah penuh.</p>";
}
?>
