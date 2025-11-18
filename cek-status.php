<?php
session_start();
include 'koneksi.php';

// ==== MODE AJAX UNTUK FETCH VIA JS ====
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $code = strtoupper(trim($_GET['code'] ?? ''));

    if ($code === '') {
        echo json_encode(['found' => false, 'message' => 'Kode booking kosong']);
        exit;
    }

    // --- PERUBAHAN SQL ---
    // 1. Mengambil 'r.status' sebagai status utama.
    // 2. Tetap LEFT JOIN untuk mengambil tanggal upload bukti jika ada.
    $stmt = $conn->prepare("
        SELECT 
            r.kode_booking AS code,
            r.status,  -- <-- MENGAMBIL STATUS DARI TABEL RESERVASI
            k.tanggal_konfirmasi AS uploadedAt
        FROM reservasi_resto.reservasi r
        LEFT JOIN reservasi_resto.pembayaran p ON r.reservasi_id = p.reservasi_id
        LEFT JOIN reservasi_resto.konfirmasi_pembayaran k ON p.pembayaran_id = k.pembayaran_id
        WHERE r.kode_booking = ?
        ORDER BY p.tanggal_pembayaran DESC, k.tanggal_konfirmasi DESC
        LIMIT 1
    ");
    // --- AKHIR PERUBAHAN SQL ---

    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();

    if ($data) {
        echo json_encode([
            'found' => true,
            'code' => $data['code'],
            'status' => $data['status'], // <-- Mengirim status dari 'reservasi'
            'uploadedAt' => $data['uploadedAt']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit;
}

// ==== MODE HALAMAN NORMAL (BUKAN AJAX) ====
$defaultCode = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
$initialData = null;

if ($defaultCode !== '') {
    // --- PERUBAHAN SQL (SAMA SEPERTI BLOK AJAX) ---
    $stmt = $conn->prepare("
        SELECT 
            r.kode_booking AS code,
            r.status, -- <-- MENGAMBIL STATUS DARI TABEL RESERVASI
            k.tanggal_konfirmasi AS uploadedAt
        FROM reservasi_resto.reservasi r
        LEFT JOIN reservasi_resto.pembayaran p ON r.reservasi_id = p.reservasi_id
        LEFT JOIN reservasi_resto.konfirmasi_pembayaran k ON p.pembayaran_id = k.pembayaran_id
        WHERE r.kode_booking = ?
        ORDER BY p.tanggal_pembayaran DESC, k.tanggal_konfirmasi DESC
        LIMIT 1
    ");
    // --- AKHIR PERUBAHAN SQL ---

    $stmt->bind_param("s", $defaultCode);
    $stmt->execute();
    $res = $stmt->get_result();
    $initialData = $res->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Status Reservasi</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<link href="img/favicon.ico" rel="icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

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
      <h1>Cek Status Reservasi</h1>
      <p>Silahkan cek kode booking anda disini!</p>
    </div>

    <div class="card">
      <div class="search-container">
        <input
          type="text"
          id="searchInput"
          class="search-input"
          placeholder="Masukkan KODE BOOKING"
          value="<?php echo htmlspecialchars($defaultCode, ENT_QUOTES); ?>"
          <?php echo $defaultCode ? 'disabled' : ''; ?>>
        <button id="searchBtn" class="btn btn-primary">Cek</button>
        <button id="changeBtn" class="btn btn-ghost">Ganti Kode Booking?</button>
      </div>

      <div id="resultContainer" style="display:none;">
        <div class="status-header">
          <div id="statusIcon" class="status-icon"></div>
          <div>
            <h2 id="statusTitle" class="status-title"></h2>
            <p class="status-code">Kode Booking: <span id="displayCode"></span></p>
          </div>
        </div>

        <p class="status-message" id="statusMessage"></p>

        <div class="status-details">
          <div class="detail-row">
            <span class="detail-label">Upload Bukti</span>
            <span class="detail-value" id="uploadDate">-</span>
          </div>
          <div class="detail-row" id="processedRow" style="display:none;">
            <span class="detail-label">Diproses</span>
            <span class="detail-value" id="processedDate">-</span>
          </div>
        </div>

        <div id="unpaidActions" class="text-center mt-3" style="display:none;">
          <a id="paymentButton" class="btn-payment">Lakukan Pembayaran</a>
        </div>


        <div id="approvedNote" class="note-box note-success" style="display:none;">
          <strong>Catatan:</strong> Silakan tunjukkan kode booking ini saat datang ke restoran.
        </div>
        <div id="rejectedActions" class="action-buttons" style="display:none;">
          <a class="btn btn-outline" href="https://wa.me/6281234567890">Hubungi Kami</a>
          <a class="btn btn-primary" href="reservasi.html">Coba Reservasi Lagi</a>
        </div>
        <div id="pendingNote" class="note-box note-warning" style="display:none;">
          Verifikasi biasanya memakan waktu 1–24 jam kerja.
        </div>
      </div>

      <div id="notFound" class="not-found" style="display:none;">
        <p>Kode booking tidak ditemukan.</p>
        <p class="hint">Pastikan Anda memasukkan kode yang benar.</p>
      </div>
    </div>
  </div>

  <div id="toast" class="toast"></div>

<script>
  // Mengirimkan data awal (hasil dari query PHP) ke JavaScript
  const initialData = <?= json_encode($initialData ?? null) ?>;
</script>
<script src="cek-status.js"></script>
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