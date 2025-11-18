<?php
include 'koneksi.php';

if (isset($_POST['submit'])) {
    $nama  = $_POST['nama'];
    $email = $_POST['email'];
    $no_wa = $_POST['no_wa'];
    $jumlah_orang = $_POST['jumlah_orang'];

    // insert hanya nama, email, no_wa (sesuai tabel pelanggan)
    $sql = "INSERT INTO pelanggan (nama, email, no_wa) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nama, $email, $no_wa);

    if ($stmt->execute()) {
        // bisa tambahkan redirect atau pesan
        header("Location: form.php?status=sukses");
    } else {
        header("Location: form.php?status=gagal");
    }

    $stmt->close();
}
?>
