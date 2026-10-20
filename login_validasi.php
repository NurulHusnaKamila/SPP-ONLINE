<?php

session_start();

require_once "src/koneksi.php";

// Memastikan formulir login dikirim
if (
    !isset($_POST['username']) ||
    !isset($_POST['password'])
) {
    header("Location: index.php?pesan=gagal");
    exit;
}

// Mengambil data login
$username = trim($_POST['username']);
$password = $_POST['password'];

// Memeriksa apakah username dan password cocok
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM petugas WHERE username = ? AND password = ?"
);

if (!$stmt) {
    header("Location: index.php?pesan=gagal");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $username,
    $password
);

mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

// Jika akun ditemukan
if ($data) {

    // Menyimpan informasi pengguna
    $_SESSION['username'] = $data['username'];
    $_SESSION['level'] = $data['level'];
    $_SESSION['id_petugas'] = $data['id_petugas'];

    // Mengarahkan pengguna berdasarkan level
    if ($data['level'] == "admin") {

        echo '<script>
            alert("Anda berhasil Login Admin!");
            window.location.href = "admin/halaman_admin.php";
        </script>';

        exit;

    } elseif ($data['level'] == "petugas") {

        echo '<script>
            alert("Anda berhasil Login Petugas!");
            window.location.href = "petugas/halaman_petugas.php";
        </script>';

        exit;

    } else {

        session_unset();
        session_destroy();

        header("Location: index.php?pesan=gagal");
        exit;

    }

} else {

    // Username atau password salah
    header("Location: index.php?pesan=gagal");
    exit;

}

?>