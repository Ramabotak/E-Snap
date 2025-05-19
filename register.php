<?php include 'koneksi.php'; ?>
<?php

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['username']);
    $gender = $_POST['gender'];
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
        
       $stmt= $conn->prepare("INSERT INTO user (nama, gender, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $gender, $email, $password, $role);
   
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
      
      <label>Gender:</label><br>
    <input type="radio" id="male" name="gender" value="laki-laki" required>
    <label for="laki-laki">Laki-laki</label><br>

    <input type="radio" id="female" name="gender" value="perempuan" required>
    <label for="perempuan">Perempuan</label><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Daftar</button>
    </form>

    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</body>
</html>
