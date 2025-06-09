<?php
include 'koneksi.php';

$error = '';
$success = '';

// Handle forgot password request
if (isset($_POST['forgot_password'])) {
    $email = trim($_POST['forgot_email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id_user, nama FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Generate OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // Store OTP in database (you need to create this table)
        $stmt = $conn->prepare("INSERT INTO password_reset (user_id, email, otp, expires_at, is_used) VALUES (?, ?, ?, ?, 0) ON DUPLICATE KEY UPDATE otp = ?, expires_at = ?, is_used = 0");
        $stmt->bind_param("isssss", $user['id_user'], $email, $otp, $expires_at, $otp, $expires_at);
        $stmt->execute();
        
        // Send email (you need to implement this)
        $subject = "Kode OTP Reset Password - E-Snap";
        $message = "Halo " . $user['nama'] . ",\n\n";
        $message .= "Kode OTP untuk reset password Anda adalah: " . $otp . "\n\n";
        $message .= "Kode ini akan berlaku selama 30 menit.\n\n";
        $message .= "Jika Anda tidak meminta reset password, abaikan email ini.\n\n";
        $message .= "Terima kasih,\nTim E-Snap";
        
        $headers = "From: noreply@e-snap.com\r\n";
        $headers .= "Reply-To: noreply@e-snap.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'Kode OTP telah dikirim ke email Anda']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengirim email. Silakan coba lagi.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email tidak ditemukan dalam sistem']);
    }
    exit;
}

// Handle OTP verification
if (isset($_POST['verify_otp'])) {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    
    $stmt = $conn->prepare("SELECT * FROM password_reset WHERE email = ? AND otp = ? AND is_used = 0 AND expires_at > NOW()");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $reset_data = $result->fetch_assoc();
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $token_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // Update with reset token
        $stmt = $conn->prepare("UPDATE password_reset SET reset_token = ?, token_expires = ? WHERE id = ?");
        $stmt->bind_param("ssi", $reset_token, $token_expires, $reset_data['id']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'reset_token' => $reset_token]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa']);
    }
    exit;
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $token = trim($_POST['token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Password konfirmasi tidak cocok']);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        exit;
    }
    
    // Verify reset token
    $stmt = $conn->prepare("SELECT * FROM password_reset WHERE reset_token = ? AND is_used = 0 AND token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $reset_data = $result->fetch_assoc();
        
        // Update password
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id_user = ?");
        $stmt->bind_param("si", $new_password, $reset_data['user_id']); // Note: You should hash the password
        $stmt->execute();
        
        // Mark token as used
        $stmt = $conn->prepare("UPDATE password_reset SET is_used = 1 WHERE id = ?");
        $stmt->bind_param("i", $reset_data['id']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Password berhasil direset']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kedaluwarsa']);
    }
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['forgot_password']) && !isset($_POST['verify_otp']) && !isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        session_start();
        $user = $result->fetch_assoc();
        
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

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

    .forgot-password-link {
      display: block;
      text-align: center;
      color: #4285f4;
      text-decoration: none;
      font-size: 14px;
      margin: 10px 0;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .forgot-password-link:hover {
      color: #3367d6;
      text-decoration: underline;
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

    .message.success {
      color: #4caf50;
      background: rgba(76, 175, 80, 0.1);
      border: 1px solid #4caf50;
    }

    /* Modal Styles */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .modal {
      background: white;
      padding: 30px;
      border-radius: 20px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      transform: translateY(-50px) scale(0.9);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .modal-overlay.active .modal {
      transform: translateY(0) scale(1);
    }

    .modal h3 {
      margin: 0 0 20px 0;
      color: #333;
      text-align: center;
      font-size: 20px;
    }

    .modal .form-group {
      margin-bottom: 15px;
      opacity: 1;
      transform: none;
    }

    .modal .form-group label {
      color: #555;
      font-size: 14px;
      margin-bottom: 8px;
    }

    .modal input {
      width: 100%;
      max-width: none;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-size: 14px;
    }

    .modal-buttons {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .modal-btn {
      flex: 1;
      padding: 12px;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .modal-btn.primary {
      background: linear-gradient(135deg, #4285f4, #3367d6);
      color: white;
    }

    .modal-btn.primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(66, 133, 244, 0.4);
    }

    .modal-btn.secondary {
      background: #f5f5f5;
      color: #666;
    }

    .modal-btn.secondary:hover {
      background: #e0e0e0;
    }

    .modal-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .modal .message {
      max-width: none;
      margin: 15px 0;
    }

    .otp-input {
      text-align: center;
      font-size: 18px;
      letter-spacing: 2px;
      font-weight: bold;
    }

    .timer {
      text-align: center;
      color: #ff5722;
      font-size: 14px;
      font-weight: bold;
      margin: 10px 0;
    }

    .resend-link {
      display: block;
      text-align: center;
      color: #4285f4;
      text-decoration: none;
      font-size: 14px;
      margin: 10px 0;
      cursor: pointer;
    }

    .resend-link:hover {
      text-decoration: underline;
    }

    .resend-link.disabled {
      color: #ccc;
      cursor: not-allowed;
      pointer-events: none;
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
    .form-group.stagger-5 { transition-delay: 0.5s; }

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

      .modal {
        width: 95%;
        padding: 20px;
      }

      .modal h3 {
        font-size: 18px;
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

    @keyframes fadeInSuccess {
      0% {
        opacity: 0;
        transform: translateY(-20px) scale(0.8);
      }
      50% {
        transform: translateY(0) scale(1.05);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .success-animation {
      animation: fadeInSuccess 0.6s cubic-bezier(0.4, 0, 0.2, 1);
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
          <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
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
            <a href="#" class="forgot-password-link" onclick="openForgotPasswordModal()">Lupa Password?</a>
          </div>

          <div class="form-group stagger-4">
            <button type="submit" class="btn-submit" id="submit-btn">Login Sekarang</button>
          </div>

          <div class="form-group stagger-5">
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

  <!-- Forgot Password Modal -->
  <div id="forgotPasswordModal" class="modal-overlay">
    <div class="modal">
      <h3>Lupa Password</h3>
      <div id="forgot-message-container"></div>
      <form id="forgotPasswordForm">
        <div class="form-group">
          <label for="forgot_email">Email</label>
          <input type="email" id="forgot_email" name="forgot_email" required placeholder="Masukkan email Anda">
        </div>
        <div class="modal-buttons">
          <button type="button" class="modal-btn secondary" onclick="closeForgotPasswordModal()">Batal</button>
          <button type="submit" class="modal-btn primary" id="send-otp-btn">Kirim OTP</button>
        </div>
      </form>
    </div>
  </div>

  <!-- OTP Verification Modal -->
  <div id="otpModal" class="modal-overlay">
    <div class="modal">
      <h3>Verifikasi OTP</h3>
      <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 20px;">
        Kode OTP telah dikirim ke email Anda
      </p>
      <div id="otp-message-container"></div>
      <form id="otpForm">
        <div class="form-group">
          <label for="otp_code">Kode OTP</label>
          <input type="text" id="otp_code" name="otp" required placeholder="Masukkan 6 digit kode OTP" maxlength="6" class="otp-input">
        </div>
        <div id="timer" class="timer"></div>
        <a href="#" id="resend-otp" class="resend-link disabled">Kirim Ulang OTP</a>
        <div class="modal-buttons">
          <button type="button" class="modal-btn secondary" onclick="closeOtpModal()">Batal</button>
          <button type="submit" class="modal-btn primary" id="verify-otp-btn">Verifikasi</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Reset Password Modal -->
  <div id="resetPasswordModal" class="modal-overlay">
    <div class="modal">
      <h3>Reset Password</h3>
      <div id="reset-message-container"></div>
      <form id="resetPasswordForm">
        <input type="hidden" id="reset_token" name="token">
        <div class="form-group">
          <label for="new_password">Password Baru</label>
          <input type="password" id="new_password" name="new_password" required placeholder="Masukkan password baru" minlength="6">
        </div>
        <div class="form-group">
          <label for="confirm_password">Konfirmasi Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required placeholder="Konfirmasi password baru" minlength="6">
        </div>
        <div class="modal-buttons">
          <button type="button" class="modal-btn secondary" onclick="closeResetPasswordModal()">Batal</button>
          <button type="submit" class="modal-btn primary" id="reset-password-btn">Reset Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="successModal" class="modal-overlay">
    <div class="modal">
      <div style="text-align: center;">
        <div style="width: 60px; height: 60px; background: #4caf50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
        </div>
        <h3 style="color: #4caf50; margin-bottom: 15px;">Password Berhasil Direset!</h3>
        <p style="color: #666; font-size: 14px; margin-bottom: 25px;">
          Password Anda telah berhasil direset. Silakan login dengan password baru Anda.
        </p>
        <button type="button" class="modal-btn primary" onclick="closeSuccessModal()" style="width: 100%;">
          Login Sekarang
        </button>
      </div>
    </div>
  </div>

  <script>
    let otpTimer;
    let otpCountdown = 300; // 5 minutes
    let currentEmail = '';

    function showMessage(message, type, container = 'message-container') {
      const messageContainer = document.getElementById(container);
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
      
      setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login Sekarang';
      }, 3000);
    }

    // Modal Functions
    function openForgotPasswordModal() {
      document.getElementById('forgotPasswordModal').classList.add('active');
      document.getElementById('forgot_email').focus();
    }

    function closeForgotPasswordModal() {
      document.getElementById('forgotPasswordModal').classList.remove('active');
      document.getElementById('forgotPasswordForm').reset();
      document.getElementById('forgot-message-container').innerHTML = '';
    }

    function openOtpModal(email) {
      currentEmail = email;
      document.getElementById('otpModal').classList.add('active');
      document.getElementById('otp_code').focus();
      startOtpTimer();
    }

    function closeOtpModal() {
      document.getElementById('otpModal').classList.remove('active');
      document.getElementById('otpForm').reset();
      document.getElementById('otp-message-container').innerHTML = '';
      clearInterval(otpTimer);
    }

    function openResetPasswordModal(token) {
      document.getElementById('resetPasswordModal').classList.add('active');
      document.getElementById('reset_token').value = token;
      document.getElementById('new_password').focus();
    }

    function closeResetPasswordModal() {
      document.getElementById('resetPasswordModal').classList.remove('active');
      document.getElementById('resetPasswordForm').reset();
      document.getElementById('reset-message-container').innerHTML = '';
    }

    function openSuccessModal() {
      document.getElementById('successModal').classList.add('active');
      document.querySelector('#successModal .modal').classList.add('success-animation');
    }

    function closeSuccessModal() {
      document.getElementById('successModal').classList.remove('active');
      // Optionally refresh page or redirect to login
      window.location.reload();
    }

    // OTP Timer
    function startOtpTimer() {
      otpCountdown = 300; // Reset to 5 minutes
      const timerElement = document.getElementById('timer');
      const resendLink = document.getElementById('resend-otp');
      
      resendLink.classList.add('disabled');
      
      otpTimer = setInterval(() => {
        const minutes = Math.floor(otpCountdown / 60);
        const seconds = otpCountdown % 60;
        
        timerElement.textContent = `Kode akan kedaluwarsa dalam ${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (otpCountdown <= 0) {
          clearInterval(otpTimer);
          timerElement.textContent = 'Kode OTP telah kedaluwarsa';
          resendLink.classList.remove('disabled');
          resendLink.textContent = 'Kirim Ulang OTP';
        }
        
        otpCountdown--;
      }, 1000);
    }

    // Form Submissions
    document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const submitBtn = document.getElementById('send-otp-btn');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Mengirim...';
      
      const formData = new FormData();
      formData.append('forgot_password', '1');
      formData.append('forgot_email', document.getElementById('forgot_email').value);
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          showMessage(result.message, 'success', 'forgot-message-container');
          setTimeout(() => {
            closeForgotPasswordModal();
            openOtpModal(document.getElementById('forgot_email').value);
          }, 2000);
        } else {
          showMessage(result.message, 'error', 'forgot-message-container');
        }
      } catch (error) {
        showMessage('Terjadi kesalahan. Silakan coba lagi.', 'error', 'forgot-message-container');
      }
      
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    });

    document.getElementById('otpForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const submitBtn = document.getElementById('verify-otp-btn');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Memverifikasi...';
      
      const formData = new FormData();
      formData.append('verify_otp', '1');
      formData.append('email', currentEmail);
      formData.append('otp', document.getElementById('otp_code').value);
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          showMessage('OTP berhasil diverifikasi!', 'success', 'otp-message-container');
          setTimeout(() => {
            closeOtpModal();
            openResetPasswordModal(result.reset_token);
          }, 1500);
        } else {
          showMessage(result.message, 'error', 'otp-message-container');
        }
      } catch (error) {
        showMessage('Terjadi kesalahan. Silakan coba lagi.', 'error', 'otp-message-container');
      }
      
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    });

    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (newPassword !== confirmPassword) {
        showMessage('Password konfirmasi tidak cocok', 'error', 'reset-message-container');
        return;
      }
      
      if (newPassword.length < 6) {
        showMessage('Password minimal 6 karakter', 'error', 'reset-message-container');
        return;
      }
      
      const submitBtn = document.getElementById('reset-password-btn');
      const originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Mereset...';
      
      const formData = new FormData();
      formData.append('reset_password', '1');
      formData.append('token', document.getElementById('reset_token').value);
      formData.append('new_password', newPassword);
      formData.append('confirm_password', confirmPassword);
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          closeResetPasswordModal();
          openSuccessModal();
        } else {
          showMessage(result.message, 'error', 'reset-message-container');
        }
      } catch (error) {
        showMessage('Terjadi kesalahan. Silakan coba lagi.', 'error', 'reset-message-container');
      }
      
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    });

    // Resend OTP
    document.getElementById('resend-otp').addEventListener('click', async function(e) {
      e.preventDefault();
      
      if (this.classList.contains('disabled')) return;
      
      this.textContent = 'Mengirim...';
      
      const formData = new FormData();
      formData.append('forgot_password', '1');
      formData.append('forgot_email', currentEmail);
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          showMessage('OTP baru telah dikirim', 'success', 'otp-message-container');
          startOtpTimer();
        } else {
          showMessage(result.message, 'error', 'otp-message-container');
        }
      } catch (error) {
        showMessage('Gagal mengirim ulang OTP', 'error', 'otp-message-container');
      }
    });

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

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal-overlay')) {
        if (e.target.id === 'forgotPasswordModal') {
          closeForgotPasswordModal();
        } else if (e.target.id === 'otpModal') {
          closeOtpModal();
        } else if (e.target.id === 'resetPasswordModal') {
          closeResetPasswordModal();
        }
      }
    });

    // OTP input formatting
    document.getElementById('otp_code').addEventListener('input', function(e) {
      this.value = this.value.replace(/\D/g, '');
    });

    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function(e) {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = this.value;
      
      if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Password tidak cocok');
      } else {
        this.setCustomValidity('');
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

      // Show success message if exists
      const successMessage = document.querySelector('.message.success');
      if (successMessage) {
        setTimeout(() => {
          successMessage.classList.add('show');
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

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        if (document.getElementById('forgotPasswordModal').classList.contains('active')) {
          closeForgotPasswordModal();
        } else if (document.getElementById('otpModal').classList.contains('active')) {
          closeOtpModal();
        } else if (document.getElementById('resetPasswordModal').classList.contains('active')) {
          closeResetPasswordModal();
        } else if (document.getElementById('successModal').classList.contains('active')) {
          closeSuccessModal();
        }
      }
    });
  </script>
  
</body>
</html>