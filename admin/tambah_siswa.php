<?php
include_once "../src/koneksi.php";

$pesanError = array();

if (isset($_POST['btnSimpan'])) {

    $nisn     = trim($_POST['nisn'] ?? '');
    $nis      = trim($_POST['nis'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $id_kelas = trim($_POST['id_kelas'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $no_telp  = trim($_POST['no_telp'] ?? '');
    $id_spp   = trim($_POST['id_spp'] ?? '');

    // VALIDASI DATA
    if ($nisn == "") {
        $pesanError[] = "NISN tidak boleh kosong!";
    }

    if ($nis == "") {
        $pesanError[] = "NIS tidak boleh kosong!";
    }

    if ($nama == "") {
        $pesanError[] = "Nama tidak boleh kosong!";
    }

    if ($id_kelas == "" || $id_kelas == "Kosong") {
        $pesanError[] = "Kelas belum dipilih!";
    }

    if ($alamat == "") {
        $pesanError[] = "Alamat tidak boleh kosong!";
    }

    if ($no_telp == "") {
        $pesanError[] = "Nomor telepon tidak boleh kosong!";
    }

    if ($id_spp == "" || $id_spp == "Kosong") {
        $pesanError[] = "Nominal SPP belum dipilih!";
    }

    if (count($pesanError) > 0) {

        foreach ($pesanError as $pesan) {
            echo "<p style='color:red;'>" .
                 htmlspecialchars($pesan) .
                 "</p>";
        }

    } else {

        // MULAI TRANSAKSI
        mysqli_begin_transaction($koneksi);

        try {

            // 1. SIMPAN DATA SISWA
            $stmtSiswa = mysqli_prepare(
                $koneksi,
                "INSERT INTO siswa
                (nisn, nis, nama, id_kelas, alamat, no_telp, id_spp)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmtSiswa) {
                throw new Exception(mysqli_error($koneksi));
            }

            mysqli_stmt_bind_param(
                $stmtSiswa,
                "sssssss",
                $nisn,
                $nis,
                $nama,
                $id_kelas,
                $alamat,
                $no_telp,
                $id_spp
            );

            if (!mysqli_stmt_execute($stmtSiswa)) {
                throw new Exception(mysqli_stmt_error($stmtSiswa));
            }

            mysqli_stmt_close($stmtSiswa);

            /*
             * PENTING:
             * Data tagihan dibuat untuk 12 bulan.
             * Tahun tagihan mengikuti tahun ajaran
             * Juli sampai Juni.
             */

            $bulanTagihan = array(
                "Juli",
                "Agustus",
                "September",
                "Oktober",
                "Nopember",
                "Desember",
                "Januari",
                "Februari",
                "Maret",
                "April",
                "Mei",
                "Juni"
            );

            $tahunSekarang = (int) date("Y");
            $bulanSekarang = (int) date("n");

            // Jika Januari-Juni, tahun ajaran dimulai tahun sebelumnya.
            $tahunAjaran = ($bulanSekarang < 7)
                ? $tahunSekarang - 1
                : $tahunSekarang;

            // 2. BUAT TAGIHAN 12 BULAN
            $stmtTagihan = mysqli_prepare(
                $koneksi,
                "INSERT INTO pembayaran
                (nisn, tgl_pembayaran, bulan_tagihan,
                 tahun_tagihan, id_spp, jumlah_bayar, no_bayar)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmtTagihan) {
                throw new Exception(mysqli_error($koneksi));
            }

            /*
             * Tagihan yang belum dibayar:
             * tgl_pembayaran diisi tanggal standar database.
             * jumlah_bayar = 0.
             * no_bayar = NULL.
             *
             * Catatan: tgl_pembayaran pada struktur tabel kamu
             * wajib diisi dan bertipe DATE.
             */

            $tanggalAwal = $tahunAjaran . "-07-01";
            $jumlahBayar = 0;
            $noBayar = null;

            mysqli_stmt_bind_param(
                $stmtTagihan,
                "sssisis",
                $nisn,
                $tanggalAwal,
                $bulanTagihan[0],
                $tahunAjaran,
                $id_spp,
                $jumlahBayar,
                $noBayar
            );

            /*
             * Buat satu tagihan untuk setiap bulan.
             * Juli-Desember menggunakan tahun ajaran awal.
             * Januari-Juni menggunakan tahun berikutnya.
             */
            for ($i = 0; $i < 12; $i++) {

                if ($i < 6) {
                    $tahunTagihan = $tahunAjaran;
                } else {
                    $tahunTagihan = $tahunAjaran + 1;
                }

                $bulan = $bulanTagihan[$i];
                $tanggal = sprintf(
                    "%04d-%02d-01",
                    $tahunTagihan,
                    ($i < 6) ? $i + 7 : $i - 5
                );

                mysqli_stmt_bind_param(
                    $stmtTagihan,
                    "sssisis",
                    $nisn,
                    $tanggal,
                    $bulan,
                    $tahunTagihan,
                    $id_spp,
                    $jumlahBayar,
                    $noBayar
                );

                if (!mysqli_stmt_execute($stmtTagihan)) {
                    throw new Exception(mysqli_stmt_error($stmtTagihan));
                }
            }

            mysqli_stmt_close($stmtTagihan);

            // SIMPAN SEMUA PERUBAHAN
            mysqli_commit($koneksi);

            echo "<script>
                alert('Data siswa dan 12 tagihan berhasil dibuat!');
                window.location.href='halaman_admin.php?open=data_siswa';
            </script>";

            exit;

        } catch (Exception $e) {

            mysqli_rollback($koneksi);

            echo "<p style='color:red;'>
                Gagal menyimpan data: " .
                htmlspecialchars($e->getMessage()) .
                "</p>";
        }
    }
}
?>

<form action="" method="post">
<table cellspacing="1" cellpadding="3">

    <tr>
        <td colspan="3" bgcolor="#CCCCCC">
            <strong>TAMBAH DATA SISWA</strong>
        </td>
    </tr>

    <tr>
        <td>NISN</td>
        <td>:</td>
        <td>
            <input name="nisn" type="text"
                   size="10" maxlength="10" required>
        </td>
    </tr>

    <tr>
        <td>NIS</td>
        <td>:</td>
        <td>
            <input name="nis" type="text"
                   size="20" maxlength="8" required>
        </td>
    </tr>

    <tr>
        <td>NAMA</td>
        <td>:</td>
        <td>
            <input name="nama" type="text"
                   size="50" maxlength="35" required>
        </td>
    </tr>

    <tr>
        <td>KELAS</td>
        <td>:</td>
        <td>
            <select name="id_kelas" required>
                <option value="">-- Pilih Kelas --</option>

                <?php
                $tampilkanKelas = mysqli_query(
                    $koneksi,
                    "SELECT * FROM kelas"
                );

                while ($data = mysqli_fetch_assoc($tampilkanKelas)) {
                    echo "<option value='" .
                        htmlspecialchars($data['id_kelas']) .
                        "'>" .
                        htmlspecialchars($data['nama_kelas']) .
                        "</option>";
                }
                ?>
            </select>
        </td>
    </tr>

    <tr>
        <td>Alamat</td>
        <td>:</td>
        <td>
            <input name="alamat" type="text"
                   size="50" maxlength="50" required>
        </td>
    </tr>

    <tr>
        <td>No. Telepon</td>
        <td>:</td>
        <td>
            <input name="no_telp" type="text"
                   size="20" maxlength="15" required>
        </td>
    </tr>

    <tr>
        <td>Nominal SPP</td>
        <td>:</td>
        <td>
            <select name="id_spp" required>
                <option value="">-- Pilih SPP --</option>

                <?php
                $tampilkanSPP = mysqli_query(
                    $koneksi,
                    "SELECT * FROM spp"
                );

                while ($data = mysqli_fetch_assoc($tampilkanSPP)) {
                    echo "<option value='" .
                        htmlspecialchars($data['id_spp']) .
                        "'>" .
                        htmlspecialchars($data['id_spp']) .
                        " - Rp " .
                        number_format($data['nominal'], 0, ',', '.') .
                        "</option>";
                }
                ?>
            </select>
        </td>
    </tr>

    <tr>
        <td colspan="3">
            <input name="btnSimpan" type="submit" value="Simpan">
        </td>
    </tr>

</table>
</form>