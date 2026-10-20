<?php

session_start();

require_once "../src/koneksi.php";

// Memastikan pengguna sudah login
if (
    !isset($_SESSION['id_petugas']) ||
    !isset($_SESSION['level'])
) {
    header("Location: ../index.php?pesan=gagal");
    exit;
}

// Memastikan permintaan pembayaran benar
if (
    !isset($_GET['act']) ||
    $_GET['act'] != 'bayar' ||
    !isset($_GET['id_pembayaran']) ||
    !isset($_GET['nisn'])
) {
    header("Location: halaman_admin.php?open=data_pembayaran");
    exit;
}

$id_pembayaran = (int) $_GET['id_pembayaran'];
$nisn = trim($_GET['nisn']);
$id_petugas = (int) $_SESSION['id_petugas'];

if ($id_pembayaran <= 0 || $nisn == '') {
    header("Location: halaman_admin.php?open=data_pembayaran");
    exit;
}

mysqli_begin_transaction($koneksi);

try {

    // Mengunci dan memeriksa tagihan
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_pembayaran, no_bayar
         FROM pembayaran
         WHERE id_pembayaran = ?
           AND nisn = ?
           AND no_bayar IS NULL
           AND keterangan IS NULL
         FOR UPDATE"
    );

    if (!$stmt) {
        throw new Exception(mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $id_pembayaran,
        $nisn
    );

    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);

    $tagihan = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$tagihan) {
        throw new Exception(
            "Tagihan tidak ditemukan atau sudah dibayar."
        );
    }

    // Membuat nomor pembayaran berdasarkan tanggal
    $today = date("ymd");

    $stmtNomor = mysqli_prepare(
        $koneksi,
        "SELECT no_bayar
         FROM pembayaran
         WHERE no_bayar LIKE CONCAT(?, '%')
         ORDER BY no_bayar DESC
         LIMIT 1
         FOR UPDATE"
    );

    if (!$stmtNomor) {
        throw new Exception(mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param(
        $stmtNomor,
        "s",
        $today
    );

    mysqli_stmt_execute($stmtNomor);

    $hasilNomor = mysqli_stmt_get_result($stmtNomor);

    $dataNomor = mysqli_fetch_assoc($hasilNomor);

    mysqli_stmt_close($stmtNomor);

    $nomorTerakhir = $dataNomor['no_bayar'] ?? '';

    if ($nomorTerakhir != '') {

        $nomorUrut = (int) substr($nomorTerakhir, 6, 4);
        $nomorUrut++;

    } else {

        $nomorUrut = 1;

    }

    if ($nomorUrut > 9999) {
        throw new Exception(
            "Nomor pembayaran hari ini sudah mencapai batas."
        );
    }

    $no_bayar = $today . sprintf('%04d', $nomorUrut);

    $tgl_pembayaran = date("Y-m-d");

    $keterangan = "LUNAS";

    // Memperbarui tagihan menjadi lunas
    $stmtUpdate = mysqli_prepare(
        $koneksi,
        "UPDATE pembayaran
         SET no_bayar = ?,
             tgl_pembayaran = ?,
             keterangan = ?,
             id_petugas = ?
         WHERE id_pembayaran = ?
           AND nisn = ?
           AND no_bayar IS NULL
           AND keterangan IS NULL"
    );

    if (!$stmtUpdate) {
        throw new Exception(mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param(
        $stmtUpdate,
        "sssiis",
        $no_bayar,
        $tgl_pembayaran,
        $keterangan,
        $id_petugas,
        $id_pembayaran,
        $nisn
    );

    if (!mysqli_stmt_execute($stmtUpdate)) {
        throw new Exception(mysqli_stmt_error($stmtUpdate));
    }

    if (mysqli_stmt_affected_rows($stmtUpdate) != 1) {
        throw new Exception(
            "Pembayaran gagal diproses. Silakan periksa kembali tagihan."
        );
    }

    mysqli_stmt_close($stmtUpdate);

    // Menyelesaikan transaksi
    mysqli_commit($koneksi);

    header(
        "Location: halaman_admin.php?open=data_pembayaran&nisn=" .
        urlencode($nisn) .
        "&cari=" . urlencode("Cari Siswa")
    );

    exit;

} catch (Exception $e) {

    mysqli_rollback($koneksi);

    echo "<h3>Pembayaran gagal diproses!</h3>";

    echo "<p>" .
        htmlspecialchars($e->getMessage()) .
        "</p>";

    echo "<a href='halaman_admin.php?open=data_pembayaran&nisn=" .
        urlencode($nisn) .
        "&cari=" . urlencode("Cari Siswa") .
        "'>Kembali ke Pembayaran</a>";

}

?>