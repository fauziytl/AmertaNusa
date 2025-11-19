<?php
include 'koneksi.php';

$code = $_GET['code'] ?? ''; // ambil kode booking dari URL
$toast = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_booking = $_POST['kode_booking'];
    $admin_id = 1; // sementara tetap 1 (bisa ubah sesuai login)
    $status_konfirmasi = 'menunggu';

    // Cek reservasi dan pembayaran
    $query = $conn->prepare("
        SELECT p.pembayaran_id 
        FROM reservasi_resto.pembayaran p
        JOIN reservasi_resto.reservasi r ON p.reservasi_id = r.reservasi_id
        WHERE r.kode_booking = ?
        ORDER BY p.tanggal_pembayaran DESC
        LIMIT 1
    ");
    $query->bind_param("s", $kode_booking);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $pembayaran_id = $row['pembayaran_id'];

        // Upload bukti pembayaran
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
          $targetDir = "uploads/";
          if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
          
          $fileName = uniqid() . "_" . basename($_FILES["bukti"]["name"]);
          $targetFile = $targetDir . $fileName;
          
          if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $targetFile)) {
                // Simpan data konfirmasi
                $stmt = $conn->prepare("INSERT INTO reservasi_resto.konfirmasi_pembayaran 
                    (pembayaran_id, admin_id, status_konfirmasi, bukti_pembayaran)
                    VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $pembayaran_id, $admin_id, $status_konfirmasi, $fileName);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Bukti pembayaran berhasil diunggah']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan konfirmasi']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File belum dipilih']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Data pembayaran tidak ditemukan']);
    }

    exit;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran AmertaNUSA</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <link href="styles.css" rel="stylesheet">
  <link href="img/favicon.ico" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

</head>
<body>
  <div class="container">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-4 px-lg-5 py-3 py-lg-0">
      <a href="LandingPage.html" class="navbar-brand p-0">
        <h1 class="text-primary m-0"><img src="img/logo-amerta.png" class="logo-navbar" alt=""> AmertaNUSA</h1>
      </a>
    </nav>

    <div class="page-header">
      <h1>Konfirmasi Pembayaran</h1>
      <p>Upload bukti pembayaran Anda untuk verifikasi.</p>
    </div>

    <div class="card">
      <div class="booking-code-display">
        <p class="label">Kode Booking Anda</p>
        <div class="code-wrapper">
          <p class="code" id="bookingCode"><?php echo htmlspecialchars($code, ENT_QUOTES); ?></p>
          <button id="copyBtn" class="copy-btn" title="Salin kode"><i class="bi bi-clipboard"></i></button>
        </div>
        <p class="hint">Simpan kode ini untuk cek status</p>
      </div>

      <?php if ($toast): ?>
        <div class="alert alert-warning" role="alert" style="margin-bottom:10px;"><?php echo htmlspecialchars($toast, ENT_QUOTES); ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="form-section">
        <label class="form-label">Upload Bukti Pembayaran</label>

        <div id="dropZone" class="drop-zone">
                    <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                    </svg>
                    <p>Drag & drop file di sini atau</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                    <p class="file-hint">Format: JPG, PNG (Max 5MB)</p>
                </div>

                <div id="preview" class="preview-container" style="display: none;">
                    <button id="removeBtn" class="remove-btn">&times;</button>
                    <img id="previewImage" src="" alt="Preview">
                    <div class="file-success">
                        <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span id="fileName"></span>
                    </div>
                </div>

        <div class="info-box">
          <p class="info-title">Informasi Penting:</p>
          <ul>
            <li>Pastikan bukti pembayaran terlihat jelas.</li>
            <li>Verifikasi membutuhkan waktu 1–24 jam.</li>
            <li>Anda dapat memantau status pada halaman cek status.</li>
          </ul>
        </div>

        <button type="button" id="submitBtn" class="btn btn-primary btn-large">Kirim Bukti Pembayaran</button>
        <a id="cekStatusLink" class="btn btn-ghost" href="cek-status.php?code=<?php echo urlencode($code); ?>">Cek Status Reservasi</a>
      </form>
    </div>
  </div>

  <div id="toast" class="toast"></div>

    <script src="konfirmasi-pembayaran.js"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>
