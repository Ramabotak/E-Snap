<?php
include 'koneksi.php';

$error   = '';
$success = '';

// Ambil daftar kategori untuk dropdown
$catRes = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
if (! $catRes) die('Query kategori gagal: ' . $conn->error);

if (isset($_GET['edit'])) {
    $id_buku = intval($_GET['edit']);
    // Ambil data buku yang mau diedit
    $stmt = $conn->prepare("SELECT * FROM buku WHERE id_buku = ?");
    $stmt->bind_param("i", $id_buku);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        die('Buku tidak ditemukan');
    }
    $buku_edit = $result->fetch_assoc();

    // Jika form edit disubmit
    if (isset($_POST['update'])) {
        $judul       = trim($_POST['judul']);
        $penulis     = trim($_POST['penulis']);
        $kategori_id = intval($_POST['kategori_id']);

        $file_path  = $buku_edit['file_buku'];
        $cover_path = $buku_edit['cover_buku'];

        // Upload file buku baru jika ada
        if (isset($_FILES['file_buku']) && $_FILES['file_buku']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['file_buku']['tmp_name'];
            $filename = basename($_FILES['file_buku']['name']);
            $target_dir = 'uploads/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            $target_file = $target_dir . time() . '_' . $filename;
            if (move_uploaded_file($tmp_name, $target_file)) {
                $file_path = $target_file;
            } else {
                $error = 'Gagal mengunggah file buku.';
            }
        }

        // Upload cover baru jika ada
        if (isset($_FILES['cover_buku']) && $_FILES['cover_buku']['error'] === UPLOAD_ERR_OK) {
            $tmp_cover = $_FILES['cover_buku']['tmp_name'];
            $cover_name = basename($_FILES['cover_buku']['name']);
            $cover_dir = 'uploads/covers/';
            if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);
            $cover_file = $cover_dir . time() . '_' . $cover_name;
            if (move_uploaded_file($tmp_cover, $cover_file)) {
                $cover_path = $cover_file;
            } else {
                $error = 'Gagal mengunggah cover buku.';
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE buku SET judul=?, penulis=?, id_kategori=?, file_buku=?, cover_buku=? WHERE id_buku=?");
            $stmt->bind_param("sssissi", $judul, $penulis, $kategori_id, $file_path, $cover_path, $id_buku);
            if ($stmt->execute()) {
                $success = 'Data buku berhasil diperbarui.';
                // Refresh data buku_edit
                header("Location: buku_admin.php?edit=$id_buku&success=1");
                exit;
            } else {
                $error = 'Gagal memperbarui data buku: ' . $stmt->error;
            }
        }
    }

    // Refresh data untuk ditampilkan di form edit
    $stmt = $conn->prepare("SELECT * FROM buku WHERE id_buku = ?");
    $stmt->bind_param("i", $id_buku);
    $stmt->execute();
    $result = $stmt->get_result();
    $buku_edit = $result->fetch_assoc();

    // Ambil ulang kategori untuk dropdown (karena sudah dipakai di atas)
    $catResEdit = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    if (! $catResEdit) die('Query kategori gagal: ' . $conn->error);
}

// ==== TAMBAH BUKU ====
if (isset($_POST['add'])) {
    $judul        = trim($_POST['judul']);
    $penulis      = trim($_POST['penulis']);

    $kategori_id  = intval($_POST['kategori_id']);

    // Upload file buku
    if (isset($_FILES['file_buku']) && $_FILES['file_buku']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['file_buku']['tmp_name'];
        $filename = basename($_FILES['file_buku']['name']);
        $target_dir = 'uploads/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . time() . '_' . $filename;
        if (move_uploaded_file($tmp_name, $target_file)) {
            $file_path = $target_file;
        } else {
            $error = 'Gagal mengunggah file buku.';
        }
    } else {
        $error = 'File buku wajib diunggah.';
    }

    // Upload cover buku
    if (isset($_FILES['cover_buku']) && $_FILES['cover_buku']['error'] === UPLOAD_ERR_OK) {
        $tmp_cover = $_FILES['cover_buku']['tmp_name'];
        $cover_name = basename($_FILES['cover_buku']['name']);
        $cover_dir = 'uploads/covers/';
        if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);
        $cover_file = $cover_dir . time() . '_' . $cover_name;
        if (move_uploaded_file($tmp_cover, $cover_file)) {
            $cover_path = $cover_file;
        } else {
            $error = 'Gagal mengunggah cover buku.';
        }
    } else {
        $error = 'Cover buku wajib diunggah.';
    }

    if (empty($error)) {
        $stmt = $conn->prepare(
            "INSERT INTO buku (judul, penulis, file_buku, cover_buku, id_kategori) VALUES (?,?,?, ?)"
        );
        $stmt->bind_param("ssssi", $judul, $penulis,  $file_path, $cover_path, $kategori_id);
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


// Ambil daftar buku
$sql = "
    SELECT
        b.id_buku,
        b.judul,
        b.penulis,
        b.file_buku,
        b.cover_buku,
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

    <?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <?php if ($success): ?><p style="color:green;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

    <?php if (isset($_GET['edit'])): ?>
        <!-- FORM EDIT -->
        <h3>Edit Buku ID <?php echo $buku_edit['id_buku']; ?></h3>
        <form method="POST" action="buku_admin.php?edit=<?php echo $buku_edit['id_buku']; ?>" enctype="multipart/form-data">
            <input type="text" name="judul" placeholder="Judul Buku" required value="<?php echo htmlspecialchars($buku_edit['judul']); ?>"><br>
            <input type="text" name="penulis" placeholder="Penulis" required value="<?php echo htmlspecialchars($buku_edit['penulis']); ?>"><br>
            <input type="file" name="file_buku" accept=".pdf,.doc,.docx"><br>
            File saat ini:  <a href="<?php echo htmlspecialchars($row['file_buku']); ?>" target="_blank">Lihat File</a>

            <input type="file" name="cover_buku" accept=".jpg,.jpeg,.png,.webp"><br>
            Cover saat ini:
            <?php if (!empty($buku_edit['cover_buku'])): ?>
                <img src="<?php echo htmlspecialchars($buku_edit['cover_buku']); ?>" alt="Cover" style="height:80px;">
            <?php else: ?>
                Tidak ada
            <?php endif; ?>
            <br>

            <select name="kategori_id" required>
                <option value="">Pilih Kategori</option>
                <?php while ($c = $catResEdit->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id_kategori']; ?>" <?php if ($c['id_kategori'] == $buku_edit['id_kategori']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($c['nama_kategori']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select><br>
                                <button type="submit" name="update">Update Buku</button>
                            </form>
                            <a href="buku_admin.php">Batal Edit</a>
                        <?php else: ?>
                            <!-- FORM TAMBAH -->
                            <h3>Tambah Buku</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="text" name="judul" placeholder="Judul Buku" required><br>
                                <input type="text" name="penulis" placeholder="Penulis" require><br>
                                <input type="file" name="file_buku" accept=".pdf,.doc,.docx" required><br>
                                <input type="file" name="cover_buku" accept=".jpg,.jpeg,.png,.webp" required><br>
                                <select name="kategori_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php while ($c = $catRes->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id_kategori']; ?>">
                                            <?php echo htmlspecialchars($c['nama_kategori']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select><br>
                                <button type="submit" name="add">Tambah Buku</button>
                            </form>
                        <?php endif; ?>
                    
                        <h3>Daftar Buku</h3>
                        <table border="1" cellpadding="5" cellspacing="0">
                            <tr>
                                <th>ID</th>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Kategori</th>
                                <th>File</th>
                                <th>Cover</th>
                                <th>Aksi</th>
                            </tr>
                            <?php while ($row = $bookRes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id_buku']; ?></td>
                                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                    <td><?php echo htmlspecialchars($row['penulis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                                    <td>
                                        <?php if (!empty($row['file_buku'])): ?>
                                            
                                     <a href="<?php echo htmlspecialchars($row['file_buku']); ?>" target="_blank">Lihat File</a>
                                        <?php else: ?>
                                            Tidak ada
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['cover_buku'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['cover_buku']); ?>" alt="Cover" style="height:50px;">
                                        <?php else: ?>
                                            Tidak ada
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="buku_admin.php?edit=<?php echo $row['id_buku']; ?>">Edit</a> |
                                        <a href="buku_admin.php?delete=<?php echo $row['id_buku']; ?>" onclick="return confirm('Yakin hapus buku ini?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    </body>
                    </html>
                    