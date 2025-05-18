<?php
include 'koneksi.php';  // koneksi $conn dan cek session admin

$error   = '';
$success = '';

// Ambil daftar kategori untuk dropdown
$catRes = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
if (! $catRes) die('Query kategori gagal: ' . $conn->error);

// Tambah buku
if (isset($_POST['add'])) {
    $judul        = trim($_POST['judul']);
    $penulis      = trim($_POST['penulis']);
    $kategori_id  = intval($_POST['kategori_id']);

    // Handle upload file
    if (isset($_FILES['file_buku']) && $_FILES['file_buku']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['file_buku']['tmp_name'];
        $filename = basename($_FILES['file_buku']['name']);
        $target_dir = 'uploads/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . time() . '_' . $filename;
        if (move_uploaded_file($tmp_name, $target_file)) {
            $file_path = $target_file;
        } else {
            $error = 'Gagal mengunggah file.';
        }
    } else {
        $error = 'File buku wajib diunggah.';
    }

    if (empty($error)) {
        $stmt = $conn->prepare(
            "INSERT INTO buku (judul, penulis, file_buku, kategori_id) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $judul, $penulis, $file_path, $kategori_id);
        if ($stmt->execute()) {
            $success = 'Buku berhasil ditambahkan';
        } else {
            $error = 'Gagal menambahkan buku: ' . $stmt->error;
        }
    }
}

// Hapus buku
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM buku WHERE id_buku = ?");
    if (! $stmt) die('Prepare gagal: ' . $conn->error);
    $stmt->bind_param("i", $id);
    if (! $stmt->execute()) {
        die('Execute gagal: ' . $stmt->error);
    }
    header('Location: buku_admin.php');
    exit;
}

// Ambil daftar buku dengan kategori
$sql = "
    SELECT
        b.id_buku,
        b.judul,
        b.penulis,
        b.file_buku,
        k.nama_kategori AS kategori
    FROM buku AS b
    LEFT JOIN kategori AS k
      ON b.id_kategori= k.id_kategori
    ORDER BY b.id_buku ASC
";
$bookRes = $conn->query($sql);
if (! $bookRes) die('Query buku gagal: ' . $conn->error);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Buku</title>
</head>
<body>
    <h2>Kelola Buku</h2>
    <?php if ($error): ?><p style="color:red;"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color:green;"><?php echo $success; ?></p><?php endif; ?>

    <form method="POST" action="buku.php" enctype="multipart/form-data">
        <input type="text" name="judul" placeholder="Judul Buku" required><br>
        <input type="text" name="penulis" placeholder="Penulis" required><br>
        <input type="file" name="file_buku" accept=".pdf,.doc,.docx" required><br>
        <select name="kategori_id" required>
            <option value="">Pilih Kategori</option>
            <?php while ($c = $catRes->fetch_assoc()): ?>
            <option value="<?php echo $c['id_kategori']; ?>"><?php echo $c['nama_kategori']; ?></option>
            <?php endwhile; ?>
        </select><br>
        <button type="submit" name="add">Tambah Buku</button>
    </form>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Judul</th>
            <th>Penulis</th>
            <th>File</th>
            <th>Kategori</th>
            <th>Aksi</th>
        </tr>
        <?php while ($b = $bookRes->fetch_assoc()): ?>
        <tr>
            <td><?php echo $b['id_buku']; ?></td>
            <td><?php echo htmlspecialchars($b['judul']); ?></td>
            <td><?php echo htmlspecialchars($b['penulis']); ?></td>
            <td><a href="<?php echo $b['file_buku']; ?>" target="_blank">Unduh</a></td>
            <td><?php echo htmlspecialchars($b['kategori']); ?></td>
            <td>
                <a href="buku.php?delete=<?php echo $b['id_buku']; ?>" onclick="return confirm('Yakin ingin hapus?')">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <p><a href="dashboard_admin.php">Kembali ke Dashboard</a></p>
</body>
</html>
