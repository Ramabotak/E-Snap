<?php include 'koneksi.php'; ?>
<?php

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['username']);
    $no_hp = $_POST['no_hp'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'siswa'; // default role user

    // Cek apakah email sudah terdaftar
    $cek = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $cek->bind_param("s", $email);
    $cek->execute();
    $result = $cek->get_result();

    if ($result->num_rows > 0) {
        $error = "Email sudah digunakan";
    } else {
        
        $stmt = $conn->prepare("INSERT INTO user (nama, no_hp, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama,$no_hp, $email, $password, $role);
        if ($stmt->execute()) {
            $success = "Registrasi berhasil. Silakan login.";
        } else {
            $error = "Gagal mendaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - E-Snap</title>
</head>
<body>
    <h2>REGISTRASI</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php elseif ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Nama:</label><br>
        <input type="text" name="username" required><br><br>

        <label>No_hp:</label><br>
        <input type="no_hp" name="no_hp" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Daftar</button>
    </form>

    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</body>
</html>
