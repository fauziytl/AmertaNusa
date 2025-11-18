<?php
include "koneksi.php";

// === Tambahkan baris ini sebelum $mail = new PHPMailer(true); === 
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        
        require 'phpmailer/PHPMailer.php';
        require 'phpmailer/SMTP.php';
        require 'phpmailer/Exception.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Data pelanggan
    $nama   = $_POST['form_name'];
    $email  = $_POST['email'];
    $no_wa  = $_POST['phone'];
    $catatan = $_POST['occasion'];

    // Data reservasi
    $tanggal  = $_POST['tanggal_reservasi'];
    $waktu    = $_POST['waktu_reservasi'];
    $jumlah   = $_POST['jumlah_orang'];
    $deposito = $_POST['deposito'];

    // Konversi waktu ke slot_id (berdasarkan tabel slot_waktu)
    $query_slot = "SELECT slot_id FROM slot_waktu WHERE jam_mulai = '$waktu' LIMIT 1";
    $result_slot = mysqli_query($conn, $query_slot);
    $row_slot = mysqli_fetch_assoc($result_slot);
    $slot_id = $row_slot ? $row_slot['slot_id'] : NULL;

    // 1️⃣ Simpan pelanggan baru
    $sql_pelanggan = "INSERT INTO pelanggan (nama, email, no_wa) 
                      VALUES ('$nama', '$email', '$no_wa')";
    mysqli_query($conn, $sql_pelanggan);
    $pelanggan_id = mysqli_insert_id($conn);

    // 2️⃣ Pilih meja yang belum teralokasi di tanggal & waktu tersebut
$sql_meja = "
    SELECT m.meja_id 
    FROM meja m
    WHERE m.jumlah >= $jumlah
      AND m.meja_id NOT IN (
          SELECT am.meja_id
          FROM alokasi_meja am
          JOIN reservasi r ON am.reservasi_id = r.reservasi_id
          WHERE am.tanggal = '$tanggal'
            AND am.slot_id = '$slot_id'
            AND r.status != 'canceled'
      )
    ORDER BY m.jumlah ASC
    LIMIT 1
";
$result_meja = mysqli_query($conn, $sql_meja);
$row_meja = mysqli_fetch_assoc($result_meja);
$meja_id = $row_meja ? $row_meja['meja_id'] : NULL;

if ($meja_id) {
    // lanjut simpan data reservasi seperti biasa

        // 4️⃣ Buat kode booking unik
        $kode_booking = "BK" . time();

        // 5️⃣ Simpan data ke tabel reservasi
        $sql_reservasi = "INSERT INTO reservasi 
            (pelanggan_id, meja_id, kode_booking, tanggal_reservasi, waktu_reservasi, slot_id, jumlah_orang, deposito, status)
            VALUES 
            ('$pelanggan_id', '$meja_id', '$kode_booking', '$tanggal', '$waktu', '$slot_id', '$jumlah', '$deposito', 'unpaid')";
        mysqli_query($conn, $sql_reservasi);
        $reservasi_id = mysqli_insert_id($conn);

        // 6️⃣ Simpan ke tabel alokasi_meja
        $sql_alokasi = "INSERT INTO alokasi_meja (reservasi_id, meja_id, tanggal, slot_id)
                        VALUES ('$reservasi_id', '$meja_id', '$tanggal', '$slot_id')";
        mysqli_query($conn, $sql_alokasi);


        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // atau server SMTP lain
            $mail->SMTPAuth   = true;
            $mail->Username   = 'fauziyatul9@gmail.com'; // ganti
            $mail->Password   = 'irfdbohyknckbqtv'; // gunakan App Password Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('fauziyatul9@gmail.com', 'Reservasi AmertaNUSA');
            $mail->addAddress($email, $nama);

            $mail->isHTML(true);
            $mail->Subject = 'Konfirmasi Reservasi Anda';
            $mail->Body    = "
                <h3>Halo, $nama!</h3>
                <p>Terima kasih telah melakukan reservasi di restoran kami.</p>
                <p><b>Detail Reservasi Anda:</b></p>
                <ul>
                    <li>Kode Booking: <b>$kode_booking</b></li>
                    <li>Tanggal: $tanggal</li>
                    <li>Waktu: $waktu</li>
                    <li>Jumlah Orang: $jumlah</li>
                    <li>Deposito: Rp " . number_format($deposito, 0, ',', '.') . "</li>
                </ul>
                <p>Silakan lanjutkan ke halaman pembayaran untuk menyelesaikan reservasi Anda.</p>
                <p>Salam hangat,<br>Tim Reservasi AmertaNUSA</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            // Kalau gagal kirim email, lanjutkan saja ke redirect
            error_log("Email gagal dikirim: {$mail->ErrorInfo}");
        }

       
    // Redirect ke halaman konfirmasi SUKSES
    header("Location: konfirmasi_reservasi.php?status=success&kode_booking=$kode_booking");
    exit();
    
} else {
    // Redirect ke halaman konfirmasi GAGAL
    header("Location: konfirmasi_reservasi.php?status=failed");
    exit();
}
}
?>
