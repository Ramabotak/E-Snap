<?php
include 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        session_start();
        $user = $result->fetch_assoc();
        
        // Gunakan nama yang konsisten untuk session
        $_SESSION['id_user'] = $user['id_user']; // Ubah dari user_id ke id_user
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email']; // Tambahkan email ke session

        if ($user['role'] === 'admin') {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: homepage.php");
        }
        exit();
    } else {
        $error = "Email atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - E-Snap</title>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      margin: 0;
      padding: 0;
      background: #fff;
      height: 100vh;
      overflow: hidden;
    }

    .reg-container {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    .left-pane {
      flex: 1;
      max-width: 50%;
      background: rgba(211, 235, 255, 0.85);
      border-top-right-radius: 50% 100%;
      border-bottom-right-radius: 50% 100%;
      padding: 40px 60px;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      padding-left: 80px;
    }

    .form-container {
      width: 100%;
      max-width: 400px;
    }

    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: #333;
      font-weight: 500;
      font-size: 14px;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      padding: 12px 15px;
      width: 100%;
      max-width: 320px;
      border-radius: 25px;
      border: 1px solid #ddd;
      outline: none;
      background: white;
      font-size: 14px;
      transition: none;
      box-shadow: none;
    }

    .input-wrapper {
      position: relative;
      width: 100%;
      max-width: 320px;
    }

    .input-wrapper input {
      width: 100%;
      padding-right: 45px;
    }

    .toggle-password {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      width: 20px;
      height: 20px;
      background: #4285f4;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-submit {
      background: #4caf50;
      color: white;
      border: none;
      padding: 12px 24px;
      width: 100%;
      max-width: 320px;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      font-size: 14px;
      margin: 25px 0 15px 0;
    }

    .register-section {
      text-align: center;
      max-width: 320px;
    }

    .register-section p {
      margin: 10px 0;
      font-size: 14px;
      color: #666;
    }

    .register-section a {
      display: inline-block;
      background: #64b5f6;
      color: white;
      padding: 10px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-size: 14px;
      width: 100%;
      max-width: 320px;
      text-align: center;
    }

    .right-pane {
      flex: 1;
      max-width: 50%;
      padding: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
      background: transparent;
    }

    .logo {
      width: 120px;
      height: 120px;
      background: none;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 30px;
      color: white;
      font-weight: bold;
      font-size: 24px;
    }

    .quote {
      text-align: center;
      max-width: 400px;
    }

    .quote h2 {
      font-size: 24px;
      font-weight: 600;
      margin: 0 0 30px 0;
      color: #333;
    }

    .quote h3 {
      font-size: 32px;
      font-weight: 700;
      margin: 0 0 10px 0;
      color: #1a1a1a;
    }

    .quote p {
      font-size: 16px;
      margin: 5px 0;
      color: #666;
    }

    .message {
      margin-bottom: 15px;
      font-weight: bold;
      padding: 10px 15px;
      border-radius: 20px;
      width: 100%;
      max-width: 320px;
      text-align: center;
      font-size: 14px;
    }

    .message.error {
      color: #f44336;
      background: rgba(244, 67, 54, 0.1);
      border: 1px solid #f44336;
    }

    /* Mobile Responsive Styles */
    @media screen and (max-width: 768px) {
      body {
        overflow-y: auto;
        background: #fff;
        min-height: 100vh;
      }

      .reg-container {
        flex-direction: column;
        height: auto;
        min-height: 100vh;
        background: transparent;
      }

      .left-pane {
        max-width: 100%;
        background: transparent;
        border-radius: 0;
        padding: 20px;
        order: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding-left: 20px;
      }

      .form-container {
        max-width: 350px;
        width: 100%;
      }

      .right-pane {
        max-width: 100%;
        background: transparent;
        padding: 30px 20px;
        order: 1;
        min-height: auto;
        text-align: center;
      }

      .logo {
        width: 100px;
        height: 100px;
        margin-bottom: 15px;
        font-size: 16px;
      }

      .quote {
        text-align: center;
        max-width: 100%;
        margin-bottom: 20px;
      }

      .quote h2 {
        font-size: 18px;
        color: #1565c0;
        margin-bottom: 10px;
      }

      .quote h3, .quote p {
        display: none;
      }

      .form-group {
        width: 100%;
        max-width: 350px;
        margin-bottom: 15px;
      }

      .form-group label {
        text-align: left;
        font-size: 14px;
        color: #1565c0;
        font-weight: 600;
        margin-bottom: 5px;
      }

      input[type="email"],
      input[type="password"],
      input[type="text"] {
        width: 100%;
        max-width: 100%;
        padding: 14px 16px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        outline: none;
        transition: none;
      }

      .input-wrapper {
        width: 100%;
        max-width: 100%;
      }

      .input-wrapper input {
        padding-right: 50px;
      }

      .toggle-password {
        right: 16px;
        width: 32px;
        height: 32px;
        background: #007bff;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
      }

      .btn-submit {
        width: 100%;
        max-width: 100%;
        margin: 20px 0;
        padding: 16px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 30px;
        background: #4caf50;
        box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        transition: all 0.3s ease;
      }

      .btn-submit:hover {
        background: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
      }

      .register-section {
        text-align: center;
        margin: 20px 0;
        width: 100%;
        max-width: 100%;
      }

      .register-section p {
        color: #666;
        font-size: 14px;
        margin: 10px 0;
      }

      .register-section a {
        background: #64b5f6;
        padding: 12px 30px;
        border-radius: 25px;
        font-size: 14px;
        box-shadow: 0 4px 15px rgba(100, 181, 246, 0.3);
        transition: all 0.3s ease;
        max-width: 100%;
      }

      .register-section a:hover {
        background: #42a5f5;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(100, 181, 246, 0.4);
      }

      .message {
        max-width: 100%;
        width: 100%;
        text-align: center;
        border-radius: 15px;
        margin-bottom: 20px;
      }
    }

    @media screen and (max-width: 480px) {
      .left-pane {
        padding: 20px 15px;
      }

      .right-pane {
        padding: 20px 15px;
      }

      .form-group {
        margin-bottom: 18px;
      }

      .quote h2 {
        font-size: 18px;
      }

      .quote h3 {
        font-size: 20px;
      }
    }

    /* Landscape orientation on mobile */
    @media screen and (max-width: 768px) and (orientation: landscape) {
      .reg-container {
        flex-direction: row;
      }

      .right-pane {
        order: 2;
        padding: 20px;
        max-width: 50%;
      }

      .left-pane {
        order: 1;
        padding: 20px;
        border-radius: 0;
        max-width: 50%;
      }

      .logo {
        width: 80px;
        height: 80px;
      }

      .quote h2 {
        font-size: 16px;
      }

      .quote h3 {
        font-size: 18px;
      }

      .quote p {
        font-size: 12px;
      }
    }
  </style>
</head>
<body>
  <div class="reg-container">
    <div class="left-pane">
      <div class="form-container">
        <?php if ($error): ?>
          <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id= "email" required placeholder="Masukkan email anda">
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <input type="password" name="password" id="password" required>
              <img src="Eye off.png" class="toggle-password" data-target="password" alt="Toggle Eye">
            </div>
          </div>

          <button type="submit" class="btn-submit">Login Sekarang</button>

          <div class="register-section">
            <p>Belum punya akun?</p>
            <a href="reg.php">Daftar di sini</a>
          </div>
        </form>
      </div>
    </div>

    <div class="right-pane">
      <img src="logo_e-snap-removebg-preview.png" alt="Logo E-Snap" class="logo">
      <div class="quote">
        <h2>Login Anggota E-Snap</h2>
        <h3>"Veni, vidi, vici."</h3>
        <p>Aku datang, aku lihat, aku menang.</p>
        <p>- Julius Caesar</p>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-password').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input.type === 'password') {
          input.type = 'text';
          this.src = 'Eye.png'; // Ganti dengan ikon mata terbuka
        } else {
          input.type = 'password';
          this.src = 'Eye off.png'; // Ganti dengan ikon mata tertutup
        }
      });
    });
  </script>
</body>
</html>