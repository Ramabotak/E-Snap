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

    .auth-container {
      display: flex;
      height: 100vh;
      width: 100%;
      position: relative;
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
      transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateX(0);
      opacity: 1;
    }

    .form-container {
      width: 100%;
      max-width: 400px;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .form-group {
      margin-bottom: 20px;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: #333;
      font-weight: 500;
      font-size: 14px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      padding: 12px 15px;
      width: 100%;
      max-width: 320px;
      border-radius: 25px;
      border: 1px solid #ddd;
      outline: none;
      background: white;
      font-size: 14px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: #4285f4;
      box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
      transform: translateY(-2px);
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
      transition: all 0.3s ease;
    }

    .toggle-password:hover {
      background: #3367d6;
      transform: translateY(-50%) scale(1.1);
    }

    .toggle-password img {
      width: 16px;
      height: 16px;
    
    }

    .btn-submit {
      background: linear-gradient(135deg, #4caf50, #45a049);
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
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
      background: linear-gradient(135deg, #45a049, #388e3c);
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
      background: linear-gradient(135deg, #64b5f6, #42a5f5);
      color: white;
      padding: 10px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-size: 14px;
      width: 100%;
      max-width: 320px;
      text-align: center;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(100, 181, 246, 0.3);
    }

    .register-section a:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(100, 181, 246, 0.4);
      background: linear-gradient(135deg, #42a5f5, #1e88e5);
    }

    .register-section a:active {
      transform: translateY(-1px);
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
      transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .logo {
      width: 120px;
      height: 120px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 30px;
      font-weight: bold;
      font-size: 24px;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      position: relative;
    }

    .logo:hover {
      transform: scale(1.05) rotate(2deg);
      box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4);
    }

    .logo img {
      width: 80px;
      height: 80px;
      object-fit: contain;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .logo:hover img {
      transform: scale(1.1);
    }

    .quote {
      text-align: center;
      max-width: 400px;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .quote h2 {
      font-size: 24px;
      font-weight: 600;
      margin: 0 0 30px 0;
      color: #333;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .quote h3 {
      font-size: 32px;
      font-weight: 700;
      margin: 0 0 10px 0;
      color: #1a1a1a;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .quote p {
      font-size: 16px;
      margin: 5px 0;
      color: #666;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
      opacity: 0;
      transform: translateY(-20px);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .message.show {
      opacity: 1;
      transform: translateY(0);
    }

    .message.error {
      color: #f44336;
      background: rgba(244, 67, 54, 0.1);
      border: 1px solid #f44336;
    }

    /* Animation States */
    .form-container.fade-out {
      opacity: 0;
      transform: translateX(-30px);
    }

    .form-container.fade-in {
      opacity: 1;
      transform: translateX(0);
    }

    .quote.fade-out {
      opacity: 0;
      transform: translateX(30px);
    }

    .quote.fade-in {
      opacity: 1;
      transform: translateX(0);
    }

    .form-group.stagger-1 { transition-delay: 0.1s; }
    .form-group.stagger-2 { transition-delay: 0.2s; }
    .form-group.stagger-3 { transition-delay: 0.3s; }
    .form-group.stagger-4 { transition-delay: 0.4s; }

    .form-group.animate-in {
      opacity: 1;
      transform: translateY(0);
    }

    .form-group.animate-out {
      opacity: 0;
      transform: translateY(20px);
    }

    /* Mobile Responsive Styles */
    @media screen and (max-width: 768px) {
      body {
        overflow-y: auto;
        background: rgba(211, 235, 255, 0.85);
        min-height: 100vh;
      }

      .auth-container {
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

      .logo img {
        width: 90px;
        height: 90px;
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

      input[type="text"],
      input[type="email"],
      input[type="password"] {
        width: 100%;
        max-width: 100%;
        padding: 14px 16px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
      }

      .toggle-password img {
        width: 20px;
        height: 20px;
      }

      .btn-submit {
        width: 100%;
        max-width: 100%;
        margin: 20px 0;
        padding: 16px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 30px;
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
        padding: 12px 30px;
        border-radius: 25px;
        font-size: 14px;
        max-width: 100%;
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
      .auth-container {
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

      .logo img {
        width: 50px;
        height: 50px;
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

    /* Loading animation */
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    .btn-submit:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      animation: pulse 1.5s infinite;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <div class="left-pane">
      <div class="form-container">
        <div id="message-container">
          <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
          <?php endif; ?>
        </div>

        <form method="POST" action="" onsubmit="handleSubmit(event)">
          <div class="form-group stagger-1">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required placeholder="Masukkan email anda">
          </div>

          <div class="form-group stagger-2">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <input type="password" name="password" id="password" required placeholder="Masukkan password anda">
              <div class="toggle-password" data-target="password">
                <img src="Eye off.png" alt="Toggle Eye" id="eye-icon">
              </div>
            </div>
          </div>

          <div class="form-group stagger-3">
            <button type="submit" class="btn-submit" id="submit-btn">Login Sekarang</button>
          </div>

          <div class="form-group stagger-4">
            <div class="register-section">
              <p>Belum punya akun?</p>
              <a href="reg.php">Daftar di sini</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="right-pane">
      <div class="logo">
        <img src="logo_e-snap-removebg-preview.png" alt="Logo E-Snap">
      </div>
      <div class="quote">
        <h2>Login Anggota E-Snap</h2>
        <h3>"Veni, vidi, vici."</h3>
        <p>Aku datang, aku lihat, aku menang.</p>
        <p>- Julius Caesar</p>
      </div>
    </div>
  </div>

  <script>
    function showMessage(message, type) {
      const messageContainer = document.getElementById('message-container');
      messageContainer.innerHTML = `<div class="message ${type}">${message}</div>`;
      setTimeout(() => {
        const messageEl = messageContainer.querySelector('.message');
        if (messageEl) {
          messageEl.classList.add('show');
        }
      }, 100);
      
      setTimeout(() => {
        const messageEl = messageContainer.querySelector('.message');
        if (messageEl) {
          messageEl.classList.remove('show');
          setTimeout(() => {
            messageContainer.innerHTML = '';
          }, 400);
        }
      }, 5000);
    }

    function animateFormGroups(direction) {
      const formGroups = document.querySelectorAll('.form-group');
      
      formGroups.forEach((group, index) => {
        setTimeout(() => {
          if (direction === 'in') {
            group.classList.add('animate-in');
            group.classList.remove('animate-out');
          }
        }, index * 100);
      });
    }

    function handleSubmit(event) {
      const submitBtn = document.getElementById('submit-btn');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Memproses...';
      
      // Re-enable after 3 seconds if form doesn't redirect
      setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login Sekarang';
      }, 3000);
    }

    // Password toggle functionality
    document.addEventListener('click', function(e) {
      if (e.target.closest('.toggle-password')) {
        const toggle = e.target.closest('.toggle-password');
        const targetId = toggle.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const eyeIcon = document.getElementById('eye-icon');
        
        if (input.type === 'password') {
          input.type = 'text';
          eyeIcon.src = 'Eye.png';
        } else {
          input.type = 'password';
          eyeIcon.src = 'Eye off.png';
        }
      }
    });

    // Initialize animations when page loads
    window.addEventListener('load', function() {
      setTimeout(() => {
        animateFormGroups('in');
      }, 300);

      // Show error message if exists
      const errorMessage = document.querySelector('.message.error');
      if (errorMessage) {
        setTimeout(() => {
          errorMessage.classList.add('show');
        }, 500);
      }
    });

    // Add focus animations
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
      });
    });
  </script>
</body>
</html>