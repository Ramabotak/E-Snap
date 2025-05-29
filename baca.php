<?php
include 'koneksi.php'; // pastikan ini file koneksi database kamu

if (!isset($_GET['id'])) {
    echo "ID buku tidak ditemukan.";
    exit;
}

$id_buku = $_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM buku WHERE id_buku = ?");
mysqli_stmt_bind_param($stmt, "i", $id_buku);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "Buku tidak ditemukan.";
    exit;
}

$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Baca Buku - <?= htmlspecialchars($row['judul']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        iframe {
            width: 100%;
            height: 600px;
            border: none;
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($row['judul']); ?></h1>
    <p><strong>Penulis:</strong> <?= htmlspecialchars($row['penulis']); ?></p>
    <h2>Pratinjau Buku</h2>

    <?php if (!empty($row['file_buku'])): ?>
        <iframe src="file/<?= $row['file_buku']; ?>"></iframe>
    <?php else: ?>
        <p>File buku tidak tersedia.</p>
    <?php endif; ?>

    <br>
    <a href="homepage.php">← Kembali ke Beranda</a>
</body>
</html>
