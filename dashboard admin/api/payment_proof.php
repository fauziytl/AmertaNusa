<?php
// C:\xampp\htdocs\restoran-1.0.0\dashboard terbaruu smoga fix bgt\api\payment_proof.php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../../koneksi.php';

// Ambil reservasi_id dari query
$reservasi_id = isset($_GET['reservasi_id']) ? intval($_GET['reservasi_id']) : 0;
if ($reservasi_id <= 0) {
    http_response_code(400);
    echo "Bad request";
    exit;
}

// 1. Ambil NAMA FILE dari kolom BLOB
$sql = "
  SELECT kp.bukti_pembayaran
  FROM pembayaran pb
  JOIN konfirmasi_pembayaran kp ON kp.pembayaran_id = pb.pembayaran_id
  WHERE pb.reservasi_id = ?
  ORDER BY kp.tanggal_konfirmasi DESC
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $reservasi_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(404);
    echo "Not found (no row)";
    exit;
}

// $filename_from_blob sekarang berisi string nama file, misal: "65f1_bukti.jpg"
$stmt->bind_result($filename_from_blob);
$stmt->fetch();
$stmt->close();

if (empty($filename_from_blob)) {
    http_response_code(404);
    echo "Not found (blob is empty)";
    exit;
}


// --- INI LOGIKA BARU YANG PENTING ---

// 2. Buat path lengkap ke file di folder /uploads/
// Script ini ada di: .../dashboard terbaruu smoga fix bgt/api/
// koneksi.php ada di: .../
// konfirmasi-pembayaran.php ada di root (...) dan menyimpan ke .../uploads/
// Jadi path dari sini ke folder uploads adalah: ../../uploads/
$filePath = __DIR__ . '/../../uploads/' . $filename_from_blob;

// 3. Cek apakah file-nya ada di disk
if (!file_exists($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    // Kirim pesan error yang jelas untuk debugging
    echo "Not found (File missing on disk: $filePath)";
    exit;
}

// 4. Ambil isi file (data gambar) dari disk
$fileContents = file_get_contents($filePath);
if ($fileContents === false) {
    http_response_code(500);
    echo "Failed to read file";
    exit;
}

// Deteksi mime type DARI ISI FILE
$mime = 'image/jpeg'; // default
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected = finfo_buffer($finfo, $fileContents);
    finfo_close($finfo);
    if (in_array($detected, ['image/jpeg', 'image/png', 'image/jpg'], true)) {
        $mime = $detected;
    }
}

ob_clean(); 

// 5. Kirim header dan ISI FILE (bukan nama file)
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . strlen($fileContents));
echo $fileContents;

exit;