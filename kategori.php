<?php
include 'koneksi.php';  // pastikan file ini berisi koneksi $conn dan cek session admin

$error   = '';
$success = '';

// Tambah kategori
if (isset($_POST['add'])) {
    $nama = trim($_POST['nama_kategori']);
    $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
    $stmt->bind_param("s", $nama);
    if ($stmt->execute()) {
        $success = 'Kategori berhasil ditambahkan';
    } else {
        $error = 'Gagal menambahkan kategori: ' . $stmt->error;
    }
}

// Hapus kategori
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // debug: pastikan $id sudah benar
    // var_dump($id);
    $stmt = $conn->prepare("DELETE FROM kategori WHERE id_kategori = ?");
    if (! $stmt) {
        die("Prepare gagal: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    if (! $stmt->execute()) {
        die("Execute gagal: " . $stmt->error);
    }
    header('Location: kategori.php');
    exit;
}

// Ambil daftar kategori
$result = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");
if (! $result) {
    die("Query gagal: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kategori</title>
</head>
<body>
    <h2>Kelola Kategori</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php elseif ($success): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" action="kategori.php">
        <input type="text" name="nama_kategori" placeholder="Nama Kategori" required>
        <button type="submit" name="add">Tambah</button>
    </form>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Nama Kategori</th>
            <th>Aksi</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_kategori'] ?></td>
            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
            <td>
                <a href="kategori.php?delete=<?= $row['id_kategori'] ?>"
                   onclick="return confirm('Yakin ingin hapus kategori ini?')">
                   Hapus
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <p><a href="dashboard_admin.php">← Kembali ke Dashboard</a></p>
</body>
</html>
