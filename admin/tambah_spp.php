```php
<?php
include_once "../src/koneksi.php";

$pesanError = array();

$id_spp = "";
$tahun = "";
$nominal = "";

if (isset($_POST['btnSimpan'])) {

    $id_spp = trim($_POST['id_spp'] ?? '');
    $tahun = trim($_POST['tahun'] ?? '');
    $nominal = trim($_POST['nominal'] ?? '');

    // Validasi data
    if ($id_spp == "") {
        $pesanError[] = "ID SPP tidak boleh kosong!";
    }

    if ($tahun == "") {
        $pesanError[] = "Tahun tidak boleh kosong!";
    }

    if ($nominal == "") {
        $pesanError[] = "Nominal tidak boleh kosong!";
    }

    if (count($pesanError) > 0) {

        foreach ($pesanError as $pesan) {
            echo htmlspecialchars($pesan) . "<br>";
        }

    } else {

        // Menyimpan data SPP
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO spp (id_spp, tahun, nominal)
             VALUES (?, ?, ?)"
        );

        if (!$stmt) {

            echo "Gagal menyiapkan query: " .
                htmlspecialchars(mysqli_error($koneksi));

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $id_spp,
                $tahun,
                $nominal
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                echo "<script>
                    alert('Data SPP berhasil disimpan!');
                    window.location.href='halaman_admin.php?open=data_spp';
                </script>";

                exit;

            } else {

                echo "<p style='color:red;'>
                    Data SPP gagal disimpan: " .
                    htmlspecialchars(mysqli_stmt_error($stmt)) .
                    "</p>";

                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<h3>TAMBAH DATA SPP</h3>

<form action="" method="post">

    <table cellspacing="1" cellpadding="5">

        <tr>
            <td>ID SPP</td>
            <td>:</td>
            <td>
                <input
                    name="id_spp"
                    type="text"
                    maxlength="4"
                    value="<?php echo htmlspecialchars($id_spp); ?>"
                    required
                >
            </td>
        </tr>

        <tr>
            <td>Tahun</td>
            <td>:</td>
            <td>
                <input
                    name="tahun"
                    type="number"
                    min="2000"
                    max="2100"
                    value="<?php echo htmlspecialchars($tahun); ?>"
                    required
                >
            </td>
        </tr>

        <tr>
            <td>Nominal</td>
            <td>:</td>
            <td>
                <input
                    name="nominal"
                    type="number"
                    min="1"
                    value="<?php echo htmlspecialchars($nominal); ?>"
                    required
                >
            </td>
        </tr>

        <tr>
            <td></td>
            <td></td>
            <td>
                <input
                    name="btnSimpan"
                    type="submit"
                    value="Simpan"
                >

                <a href="halaman_admin.php?open=data_spp">
                    Batal
                </a>
            </td>
        </tr>

    </table>

</form>
```