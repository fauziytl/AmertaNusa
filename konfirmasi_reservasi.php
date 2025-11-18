<?php
// Ambil parameter dari URL
$kode_booking = isset($_GET['kode_booking']) ? $_GET['kode_booking'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'success';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Konfirmasi Reservasi - AmertaNUSA</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="konfirmasi-page">
    <div class="overlay-konfirmasi"></div>
    
    <div class="popup-konfirmasi">
        <?php if ($status === 'success'): ?>
        <!-- SUKSES: Reservasi Berhasil -->
        <div class="popup-header">
            <div class="success-icon">
                <svg viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25"/>
                    <path class="checkmark-check" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h2>Reservasi Berhasil!</h2>
            <p>Terima kasih telah melakukan reservasi</p>
        </div>

        <div class="popup-body">
            <div class="booking-code">
                <label>Kode Booking Anda</label>
                <div class="code"><?php echo htmlspecialchars($kode_booking); ?></div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Informasi Penting</h3>
                <p><i class="fas fa-check-circle"></i> Simpan kode booking Anda dengan baik</p>
                <p><i class="fas fa-check-circle"></i> Email konfirmasi telah dikirim</p>
                <p><i class="fas fa-check-circle"></i> Lakukan pembayaran untuk konfirmasi reservasi</p>
            </div>

            <div class="action-buttons single-button">
                <a href="pembayaran.php?kode_booking=<?php echo urlencode($kode_booking); ?>" class="btn-konfirmasi btn-primary-konfirmasi">
                    <i class="fas fa-credit-card"></i> Lanjut Pembayaran
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- GAGAL: Meja Tidak Tersedia -->
        <div class="popup-header popup-header-error">
            <div class="error-icon">
                <svg viewBox="0 0 52 52">
                    <circle class="error-circle" cx="26" cy="26" r="25"/>
                    <line class="error-line1" x1="18" y1="18" x2="34" y2="34"/>
                    <line class="error-line2" x1="34" y1="18" x2="18" y2="34"/>
                </svg>
            </div>
            <h2>Meja Tidak Tersedia</h2>
            <p>Maaf, tidak ada meja yang tersedia saat ini</p>
        </div>

        <div class="popup-body">
            <div class="info-section info-section-error">
                <h3><i class="fas fa-exclamation-triangle"></i> Informasi</h3>
                <p><i class="fas fa-times-circle"></i> Semua meja sudah terpesan pada waktu yang Anda pilih</p>
                <p><i class="fas fa-calendar-alt"></i> Silakan pilih tanggal atau waktu lain</p>
                <p><i class="fas fa-phone-alt"></i> Atau hubungi kami untuk bantuan lebih lanjut</p>
            </div>

            <div class="suggestion-box">
                <h4><i class="fas fa-lightbulb"></i> Saran untuk Anda</h4>
                <ul>
                    <li>Coba pilih waktu yang berbeda (pagi/siang/malam)</li>
                    <li>Pilih tanggal alternatif</li>
                    <li>Hubungi kami di <strong>+62 821-xxxx-xxxx</strong></li>
                </ul>
            </div>

            <div class="action-buttons single-button">
                <a href="reservasi.php" class="btn-konfirmasi btn-primary-konfirmasi">
                    <i class="fas fa-calendar-alt"></i> Ubah Jadwal Reservasi
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>


</body>
</html>