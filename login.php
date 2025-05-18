<?php include 'koneksi.php'; ?>
<?php
 $error = '';  
 if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
 }
 $stmt = $conn->prepare('SELECT * FROM user WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        if ($password === $data['password']) {
            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = $data['role'];
            if ($data['role'] == 'admin') {
                header("location: admin_dashboard.php");
            } else {
                header("location: user_dashboard.php");
            }
            exit();
        } else {
            $error = "password salah";
        }
    } else {
        $error = "email tidak terdaftar";
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Snap</title>
</head>
<body>
    <h2>LOGIN</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label>EMAIL:</label> <br>
            <input type="email" name="email" required> <br>
            <label>PASSWORD:</label> <br>
            <input type="password" name="password" required> <br>
            <button type="submit">LOGIN</button>
        </form>

        <p>Belum punya akun? <a href="register.php">Login di sini</a></p>
</body>
</html>
    