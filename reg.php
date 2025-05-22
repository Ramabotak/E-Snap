<?php
include 'koneksi.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['username']);
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'siswa';

    $cek = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $cek->bind_param("s", $email);
    $cek->execute();
    $result = $cek->get_result();

    if ($result->num_rows > 0) {
        $error = "Email sudah digunakan";
    } else {
        $stmt = $conn->prepare("INSERT INTO user (nama, gender, email, password, role) VALUES (?, ?, ?, ?, ?)");
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
  <meta charset="UTF-8" />
  <title>Register - E-Snap</title>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Jost', sans-serif;
    }

    body {
      margin: 0;
      padding: 0;
      overflow: hidden;
    }

    .reg-container {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    .left-pane {
      flex: 1;
      background: #D3EBFF;
      border-top-right-radius: 60% 100%;
      border-bottom-right-radius: 60% 100%;
      padding: 60px 100px;
      position: relative;
    }

    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      padding: 12px;
      box-sizing: border-box;
      border-radius: 25px;
      border: 1px solid rgba(0,0,0,0.5);
      outline: none;
    }

    /* Wrapper untuk input password + toggle icon */
    .input-wrapper {
      position: relative;
      width: 100%;
    }

    .input-wrapper input {
      width: 80%;
      padding-right: 40px;/* ruang buat icon */
      position: relative;
      
    }

    .toggle-password {
      position: absolute;
      right: 12px;
      top: 30%;
      transform: translateX(-470%);
      cursor: pointer;
      width: 20px;
      height: 20px;
    }

    input[type="text"],
    input[type="email"] {
      width: 80%;
      box-sizing: border-box;
    }

    .gender-options {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .gender-options label {
      margin-left: 5px;
    }

    .btn-submit {
      background: #66b728;
      color: white;
      border: none;
      padding: 12px;
      width: 60%;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      display: block;
      margin: 20px auto;
      margin-left: 40px;
    }

    .login-link {
      margin-top: -10px;
      text-align: center;
    }

    .login-link a {
      display: inline-block;
      background: #97c9fd;
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      margin-top: -10px;
      margin-left: -90px;
    }

    .login-link p {
      margin: 30px;
      margin-left: -60px;
    }

    .right-pane {
      flex: 1;
      padding: 100px 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
      overflow: hidden;
    }

    .right-pane img {
      width: 180px;
      margin-bottom: 20px;
    }

    .quote {
      text-align: left;
      max-width: 400px;
    }

    .quote h2 {
      font-size: 24px;
      font-weight: 500;
      margin: 0;
    }

    .quote h3 {
      font-size: 28px;
      font-weight: 600;
    }

    .quote p {
      font-size: 16px;
      margin: 10px 0 0;
    }

    .message {
      margin-bottom: 15px;
      font-weight: bold;
    }

    .message.success {
      color: green;
    }

    .message.error {
      color: red;
    }

    .asset {
      position: absolute;
      right: 0px;
      bottom: 0px;
      display: flex;
      align-items: flex-end;
    }

    .asset img {
      margin-top: -40px;
      width: 100px;
      height: 100px;
      margin-left: -20px;
      transform: scale(7);
    }

    .asset img:first-child {
      margin-top: 0;
    }
  </style>
</head>
<body>
  <div class="reg-container">
    <div class="left-pane">

      <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
      <?php elseif ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="username">Nama</label>
          <input type="text" name="username" id="username" required>
        </div>

        <div class="form-group">
          <label>Gender</label>
          <div class="gender-options">
            <input type="radio" id="male" name="gender" value="laki-laki" required>
            <label for="male">Laki-laki</label>

            <input type="radio" id="female" name="gender" value="perempuan" required>
            <label for="female">Perempuan</label>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <input type="password" name="password" id="password" required>
            <img src="Eye off.png" class="toggle-password" data-target="password" alt="Toggle Eye" />
          </div>
        </div>

        <button type="submit" class="btn-submit">Register Sekarang</button>

        <div class="login-link">
          <p>Sudah punya akun?</p>
          <a href="login.php">Masuk Disini</a>
        </div>
      </form>
    </div>

    <div class="right-pane">
      <img src="logo_e-snap-removebg-preview.png" alt="Logo E-Snap" />
      <div class="quote">
        <h2>Register Anggota E-Snap</h2>
        <h3>"Scientia potentia est."</h3>
        <p>Pengetahuan adalah kekuatan.</p>
        <p>- Francis Bacon</p>
      </div>
    </div>

    <div class="asset">
      <img src="Ellipse 13.png" alt="ELLipse" />
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-password').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input.type === 'password') {
          input.type = 'text';
          this.src = 'Eye.png'; // ganti ke ikon eye on
        } else {
          input.type = 'password';
          this.src = 'Eye off.png'; // ganti ke ikon eye off
        }
      });
    });
  </script>
</body>
</html>
