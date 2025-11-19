<?php
include "koneksi.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// pastikan path ke folder phpmailer benar
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $nama    = trim($_POST['from_name'] ?? '');
    $email   = trim($_POST['from_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $pesan   = trim($_POST['message'] ?? '');

    // Validasi sederhana
    if ($nama === '' || $email === '' || $subject === '' || $pesan === '') {
        echo "<script>alert('Semua field wajib diisi.'); history.back();</script>";
        exit;
    }

    // ====== Konfigurasi penerima uji coba ======
    // ganti alamat di bawah ini dengan email anggota tim yang akan menerima pesan
    $email_tujuan = 'pendaftaranfauziyatul@gmail.com';

    // Inisialisasi PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fauziyatul9@gmail.com';   // akun pengirim
        $mail->Password   = 'irfdbohyknckbqtv';        // App Password Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Penerima
        $mail->setFrom('fauziyatul9@gmail.com', 'AmertaNUSA Website');
        $mail->addAddress($email_tujuan, 'Tim AmertaNUSA');
        // agar penerima bisa membalas langsung ke pengisi form
        $mail->addReplyTo($email, $nama);

        // Konten email
        $mail->isHTML(true);
        $mail->Subject = 'Kontak AmertaNUSA';
        $mail->Body = "
            <h3>Pesan baru dari halaman kontak AmertaNUSA</h3>
            <p><b>Nama:</b> " . htmlspecialchars($nama) . "</p>
            <p><b>Email:</b> " . htmlspecialchars($email) . "</p>
            <p><b>Subjek:</b> " . htmlspecialchars($subject) . "</p>
            <p><b>Pesan:</b><br>" . nl2br(htmlspecialchars($pesan)) . "</p>
            <hr>
            <small>Pesan ini dikirim otomatis dari halaman kontak website AmertaNUSA.</small>
        ";
        $mail->AltBody = "Pesan baru dari halaman kontak AmertaNUSA\n\n"
            . "Nama: $nama\nEmail: $email\nSubjek: $subject\n\n$pesan";

        // Kirim
        $mail->send();

        echo "<script>alert('Pesan berhasil dikirim ke email anggota tim Anda!'); window.location='../Kontak.html';</script>";
    } catch (Exception $e) {
        error_log('Email gagal dikirim: ' . $mail->ErrorInfo);
        echo "<script>alert('Gagal mengirim pesan. Silakan periksa konfigurasi email.'); history.back();</script>";
    }
}
?>
