
<?php
include_once "../src/koneksi.php";

$nisn = trim($_GET['nisn'] ?? '');
?>

<h3>TRANSAKSI PEMBAYARAN SPP</h3>

<form method="get" action="halaman_admin.php">

    NISN:

    <input
        type="text"
        name="nisn"
        maxlength="10"
        value="<?php echo htmlspecialchars($nisn); ?>"
        required
    >

    <input
        type="hidden"
        name="open"
        value="data_pembayaran"
    >

    <input
        type="submit"
        name="cari"
        value="Cari Siswa"
    >

</form>

<?php

if (isset($_GET['cari']) && $nisn != '') {

    // Mencari data siswa, kelas, dan nominal SPP
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT siswa.*, kelas.nama_kelas, spp.nominal
         FROM siswa
         INNER JOIN kelas
            ON siswa.id_kelas = kelas.id_kelas
         INNER JOIN spp
            ON siswa.id_spp = spp.id_spp
         WHERE siswa.nisn = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nisn);

    mysqli_stmt_execute($stmt);

    $hasilSiswa = mysqli_stmt_get_result($stmt);

    $dataSiswa = mysqli_fetch_assoc($hasilSiswa);

    mysqli_stmt_close($stmt);

    if (!$dataSiswa) {

        echo "<p style='color:red;'>
            Data siswa dengan NISN tersebut tidak ditemukan.
        </p>";

    } else {

?>

<h3>BIODATA SISWA</h3>

<table cellpadding="5">

    <tr>
        <td>NISN</td>
        <td>:</td>
        <td>
            <?php echo htmlspecialchars($dataSiswa['nisn']); ?>
        </td>
    </tr>

    <tr>
        <td>Nama Siswa</td>
        <td>:</td>
        <td>
            <?php echo htmlspecialchars($dataSiswa['nama']); ?>
        </td>
    </tr>

    <tr>
        <td>Kelas</td>
        <td>:</td>
        <td>
            <?php echo htmlspecialchars($dataSiswa['nama_kelas']); ?>
        </td>
    </tr>

    <tr>
        <td>Nominal SPP</td>
        <td>:</td>
        <td>
            Rp <?php echo number_format(
                (float) $dataSiswa['nominal'],
                0,
                ',',
                '.'
            ); ?>
        </td>
    </tr>

</table>

<h3>TAGIHAN SPP SISWA</h3>

<table border="1" cellpadding="6" cellspacing="0">

    <tr>
        <th>No</th>
        <th>Bulan</th>
        <th>Tahun</th>
        <th>No. Bayar</th>
        <th>Tanggal Bayar</th>
        <th>Jumlah</th>
        <th>Keterangan</th>
        <th>Bayar</th>
    </tr>

    <?php

    // Mencari tagihan siswa
    $stmtBayar = mysqli_prepare(
        $koneksi,
        "SELECT pembayaran.*, spp.nominal
         FROM pembayaran
         INNER JOIN spp
            ON pembayaran.id_spp = spp.id_spp
         WHERE pembayaran.nisn = ?
         ORDER BY pembayaran.tahun_tagihan,
                  FIELD(
                      pembayaran.bulan_tagihan,
                      'Juli',
                      'Agustus',
                      'September',
                      'Oktober',
                      'Nopember',
                      'November',
                      'Desember',
                      'Januari',
                      'Februari',
                      'Maret',
                      'April',
                      'Mei',
                      'Juni'
                  ),
                  pembayaran.id_pembayaran"
    );

    mysqli_stmt_bind_param(
        $stmtBayar,
        "s",
        $dataSiswa['nisn']
    );

    mysqli_stmt_execute($stmtBayar);

    $hasilBayar = mysqli_stmt_get_result($stmtBayar);

    $no = 1;

    if (mysqli_num_rows($hasilBayar) > 0) {

        while ($data = mysqli_fetch_assoc($hasilBayar)) {

            echo "<tr>";

            echo "<td>" . $no . "</td>";

            echo "<td>" .
                htmlspecialchars($data['bulan_tagihan']) .
                "</td>";

            echo "<td>" .
                htmlspecialchars($data['tahun_tagihan']) .
                "</td>";

            echo "<td>" .
                htmlspecialchars($data['no_bayar'] ?? '') .
                "</td>";

            echo "<td>" .
                htmlspecialchars($data['tgl_pembayaran'] ?? '') .
                "</td>";

            echo "<td>Rp " .
                number_format(
                    (float) $data['nominal'],
                    0,
                    ',',
                    '.'
                ) .
                "</td>";

            echo "<td>" .
                htmlspecialchars($data['keterangan'] ?? '') .
                "</td>";

            echo "<td align='center'>";

            if (
                empty($data['no_bayar']) &&
                empty($data['keterangan'])
            ) {

                echo "<a href='proses_pembayaran.php?act=bayar&id_pembayaran=" .
                    urlencode($data['id_pembayaran']) .
                    "&nisn=" .
                    urlencode($dataSiswa['nisn']) .
                    "' onclick=\"return confirm('Proses pembayaran SPP bulan ini?')\">Bayar</a>";

            } else {

                echo "Lunas";

            }

            echo "</td>";

            echo "</tr>";

            $no++;
        }

    } else {

        echo "<tr>
            <td colspan='8' align='center'>
                Tagihan belum tersedia.
            </td>
        </tr>";

    }

    mysqli_stmt_close($stmtBayar);

    ?>

</table>

<?php

    }
}

?>

<br>

<p>
    Pembayaran SPP dilakukan dengan mencari tagihan siswa
    menggunakan NISN, kemudian klik tombol Bayar pada bulan
    yang ingin dibayarkan.
</p>
```