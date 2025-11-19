<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
$kode_booking = $_GET['kode_booking'] ?? '';

// --- BAGIAN 1: TAMPILKAN DATA RESERVASI ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $kode_booking != '') {
    $sql = "
    SELECT 
        r.kode_booking,
        p.nama,
        p.email,
        p.no_wa,
        r.tanggal_reservasi,
        r.waktu_reservasi,
        r.jumlah_orang,
        r.deposito
    FROM reservasi r
    JOIN pelanggan p ON p.pelanggan_id = r.pelanggan_id
    WHERE r.kode_booking = '$kode_booking'
    LIMIT 1
    ";

    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) == 0) {
        die("Data reservasi tidak ditemukan untuk kode booking $kode_booking");
    }

    $data = mysqli_fetch_assoc($result);

    // Format tampilan
    $tanggal_tampil = date('l, d F Y', strtotime($data['tanggal_reservasi']));
    $jam_tampil = substr($data['waktu_reservasi'], 0, 5);
    $jumlah_tamu = (int)$data['jumlah_orang'];
    $total_deposit = $jumlah_tamu * 25000; // 25.000 per orang
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_booking = $_POST['kode_booking'] ?? '';
    $metode = $_POST['metode'] ?? '';

    if (empty($kode_booking) || empty($metode)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Ambil data reservasi dari database
    $query = $conn->prepare("SELECT reservasi_id, jumlah_orang FROM reservasi WHERE kode_booking = ?");
    $query->bind_param("s", $kode_booking);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $reservasi_id = $row['reservasi_id'];
        $jumlah = (int)$row['jumlah_orang'] * 25000; // hitung otomatis
        $tanggal_pembayaran = date('Y-m-d H:i:s');
        $status = 'menunggu';

        $stmt = $conn->prepare("INSERT INTO pembayaran (reservasi_id, jumlah, metode, status, tanggal_pembayaran)
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", $reservasi_id, $jumlah, $metode, $status, $tanggal_pembayaran);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pembayaran']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Kode booking tidak ditemukan']);
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Reservasi AmertaNUSA</title>

    <!-- Style dari reservasi -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="img/favicon.ico" rel="icon">

    <!-- Style khusus pembayaran -->
    <link rel="stylesheet" href="css/pembayaran.css">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
</head>
<body id="pembayaran-page">

    <!-- Header (Navbar dari reservasi) -->
    <div class="container-xxl position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-4 px-lg-5 py-3 py-lg-0">
            <a href="LandingPage.html" class="navbar-brand p-0">
                <h1 class="text-primary m-0"><img src="img/logo-amerta.png" alt="Logo" class="logo-navbar"> AmertaNUSA</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0 pe-4"></div>
            </div>
        </nav>

        <!-- Konten Pembayaran -->
        <div class="container py-5 mt-5">
            <div class="container">
                <div class="header text-center mb-4">
                    <h1>Pembayaran Reservasi</h1>
                    <p>Selesaikan pembayaran untuk mengonfirmasi reservasi Anda</p>
                </div>

                <!-- Konten asli pembayaran -->
                <div class="content-grid">
                    <div class="left-column">
                        <!-- Ringkasan Reservasi -->
                        <div class="card">
                            <h2><span class="accent-line"></span>Ringkasan Reservasi</h2>
                            <div class="info-group">
                                <div class="info-item">
                                    <p class="label">Nama</p>
                                    <p class="value" id="customer-name"><?= htmlspecialchars($data['nama']) ?></p>
                                </div>
                                <div class="info-item">
                                    <p class="label">Email</p>
                                    <p class="value" id="customer-email"><?= htmlspecialchars($data['email']) ?></p>
                                </div>
                                <div class="info-item">
                                    <p class="label">No. WhatsApp</p>
                                    <p class="value" id="customer-whatsapp"><?= htmlspecialchars($data['no_wa']) ?></p>
                                </div>
                                <div class="info-item border-top">
                                    <p class="label">Tanggal</p>
                                    <p class="value" id="reservation-date"><?= $tanggal_tampil ?></p>
                                </div>
                                <div class="info-item">
                                    <p class="label">Waktu</p>
                                    <p class="value" id="reservation-time"><?= $jam_tampil ?></p>
                                </div>
                                <div class="info-item">
                                    <p class="label">Jumlah Tamu</p>
                                    <p class="value" id="guest-count"><?= $jumlah_tamu ?> orang</p>
                                </div>
                            </div>
                        </div>

                        <!-- Kode Booking -->
                        <div class="card booking-code-card">
                            <h2>Kode Booking</h2>
                            <div class="booking-code-wrapper">
                                <div class="booking-code" id="booking-code"><?= htmlspecialchars($data['kode_booking']) ?></div>
                                <button class="btn-icon" onclick="copyBookingCode()" id="copy-btn">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="hint">Simpan kode ini untuk melacak status reservasi Anda</p>
                        </div>

                        <!-- Rincian Deposit -->
                        <div class="card">
                            <h2>Rincian Deposit</h2>
                            <div class="deposit-details">
                                <div class="deposit-row">
                                    <span class="label">Deposit per orang</span>
                                    <span class="value">Rp25.000</span>
                                </div>
                                <div class="deposit-row">
                                    <span class="label">Jumlah tamu</span>
                                    <span class="value" id="guest-count-2"><?= $jumlah_tamu ?> orang</span>
                                </div>
                                <div class="deposit-row total">
                                    <span class="label">Total Deposit</span>
                                    <span class="value total-amount" id="total-deposit">Rp<?= number_format($total_deposit, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="right-column">
                <div class="card">
                    <h2><span class="accent-line"></span>Pilih Metode Pembayaran</h2>

                    <!-- Payment Method Options -->
                    <div class="payment-methods">
                        <button class="payment-method" data-method="qris" onclick="selectPaymentMethod('qris')">
                            <div class="method-icon">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
                                </svg>
                            </div>
                            <div class="method-info">
                                <h3>QRIS</h3>
                                <p>Scan & bayar dengan e-wallet</p>
                            </div>
                        </button>

                        <button class="payment-method" data-method="bank" onclick="selectPaymentMethod('bank')">
                            <div class="method-icon">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 10v7h3v-7H4zm6 0v7h3v-7h-3zM2 22h19v-3H2v3zm14-12v7h3v-7h-3zm-4.5-9L2 6v2h19V6l-9.5-5z"/>
                                </svg>
                            </div>
                            <div class="method-info">
                                <h3>Transfer Bank</h3>
                                <p>BCA, Mandiri, BNI, BRI</p>
                            </div>
                        </button>

                        <button class="payment-method" data-method="va" onclick="selectPaymentMethod('va')">
                            <div class="method-icon">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                                </svg>
                            </div>
                            <div class="method-info">
                                <h3>Virtual Account</h3>
                                <p>Bayar via ATM atau m-banking</p>
                            </div>
                        </button>
                    </div>

                    <!-- Payment Details Area -->
                    <div id="payment-details" class="payment-details" style="display: none;">
                        <!-- QRIS Details -->
                        <div id="qris-details" class="payment-detail-content" style="display: none;">
                            <h3>Detail Pembayaran</h3>
                            <p class="instruction">Scan QR Code di bawah ini menggunakan aplikasi e-wallet Anda (GoPay, OVO, DANA, ShopeePay, dll)</p>
                            <div class="qris-container">
                                <img src="img/qris.jpg" alt="QRIS Payment Code" class="qris-image">
                            </div>
                            <div class="total-box">
                                <p>Total Pembayaran: <span class="total-amount" id="qris-total">Rp<?= number_format($total_deposit, 0, ',', '.') ?></span></p>
                            </div>
                        </div>

                        <!-- Bank Transfer Details -->
                        <div id="bank-details" class="payment-detail-content" style="display: none;">
                            <h3>Detail Pembayaran</h3>
                            <p class="instruction">Transfer ke salah satu rekening berikut:</p>
                            
                            <div class="bank-account">
                                <div class="bank-header">
                                    <p class="bank-name">Bank BNI</p>
                                    <button class="btn-copy" onclick="copyText('0534232457', 'BNI')">Salin</button>
                                </div>
                                <p class="label">No. Rekening</p>
                                <p class="account-number">053 423 2457</p>
                                <p class="account-name">a.n. Andi Chrysan Felisia</p>
                            </div>

                            <div class="bank-account">
                                <div class="bank-header">
                                    <p class="bank-name">Bank BSI</p>
                                    <button class="btn-copy" onclick="copyText('7234203163', 'BSI')">Salin</button>
                                </div>
                                <p class="label">No. Rekening</p>
                                <p class="account-number">723 420 3163</p>
                                <p class="account-name">a.n. Annisa Laga Sukmawati</p>
                            </div>

                            <div class="total-box">
                                <p>Total yang harus ditransfer: <span class="total-amount" id="bank-total">Rp<?= number_format($total_deposit, 0, ',', '.') ?></span></p>
                                <p class="hint">* Transfer sesuai jumlah total untuk verifikasi otomatis</p>
                            </div>
                        </div>

                        <!-- VA Details -->
                        <div id="va-details" class="payment-detail-content" style="display: none;">
                            <h3>Detail Pembayaran</h3>
                            <p class="instruction">Gunakan nomor Virtual Account di bawah untuk pembayaran:</p>
                            
                            <div class="va-account">
                                <p class="bank-name">Virtual Account BNI</p>
                                <div class="va-number-wrapper">
                                    <div>
                                        <p class="label">Nomor VA</p>
                                        <p class="va-number" id="va-number">8808 10151900</p>
                                        <p class="account-name">a.n. Andi Chrysan Felisia</p>
                                    </div>
                                    <button class="btn-copy" onclick="copyText('880810151900', 'VA')">Salin</button>
                                </div>
                            </div>

                            <div class="total-box">
                                <p>Total Pembayaran: <span class="total-amount" id="va-total">Rp<?= number_format($total_deposit, 0, ',', '.') ?></span></p>
                            </div>

                            <div class="instruction-box">
                                <p class="instruction-title">Cara Pembayaran:</p>
                                <ol>
                                    <li>Buka aplikasi mobile banking atau internet banking</li>
                                    <li>Pilih menu Transfer / Bayar</li>
                                    <li>Pilih Virtual Account BNI</li>
                                    <li>Masukkan nomor VA di atas</li>
                                    <li>Konfirmasi pembayaran</li>
                                </ol>
                            </div>
                        </div>

                    <!-- Payment Button -->
                    <button class="btn-primary" id="payment-btn" onclick="processPayment()" disabled>
                        Pilih Metode Pembayaran
                    </button>

                    <p class="terms">Dengan melanjutkan, Anda menyetujui syarat dan ketentuan reservasi kami</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <!-- Script -->
     <script>
     const reservationData = {
        name: "<?= addslashes($data['nama']) ?>",
        email: "<?= addslashes($data['email']) ?>",
        whatsapp: "<?= addslashes($data['no_wa']) ?>",
        date: "<?= $data['tanggal_reservasi'] ?>",
        time: "<?= $data['waktu_reservasi'] ?>",
        guests: <?= (int)$data['jumlah_orang'] ?>,
        bookingCode: "<?= $data['kode_booking'] ?>"
        };
        </script>
    <script src="pembayaran.js"></script>
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
