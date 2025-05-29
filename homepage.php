<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perpustakaan Digital - SMKN 8 Semarang</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', Arial, sans-serif;
      background-color: #ffffff;
      line-height: 1.6;
    }

    /* Header Styles */
    .header {
      background-color: #eeeeee;
      padding: 20px 50px;
      border-radius: 40px;
      margin: 40px 50px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .logo {
      width: 80px;
      height: 80px;
      object-fit: cover;
    }

    .nav-menu {
      display: flex;
      gap: 30px;
      align-items: center;
    }

    .nav-item {
      font-weight: 600;
      color: #121010b2;
      font-size: 18px;
      cursor: pointer;
      transition: color 0.3s;
    }

    .nav-item:hover {
      color: #000;
    }

    .search-container {
      display: flex;
      align-items: center;
      background: #fff;
      padding: 8px 15px;
      border-radius: 25px;
      width: 300px;
    }

    .search-input {
      border: none;
      outline: none;
      flex: 1;
      padding: 5px;
      font-size: 14px;
    }

    .search-icon {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }

    .auth-buttons {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 5px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      border: none;
      transition: all 0.3s;
    }

    .btn-login {
      background-color: #ffffffba;
      color: #000;
    }

    .btn-signup {
      background-color: #151510;
      color: #fff;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* Main Content */
    .main-content {
      background-color: #7cb3e661;
      border-radius: 30px;
      margin: 0 50px;
      padding: 40px;
      min-height: 800px;
    }

    .kategori-section {
      margin-bottom: 50px;
    }

    .kategori-title {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 20px;
      color: #000;
      padding-bottom: 10px;
      border-bottom: 2px solid #ddd;
    }

    .buku-slider {
      display: flex;
      gap: 20px;
      overflow-x: auto;
      padding: 20px 0;
      scroll-behavior: smooth;
    }

    .buku-slider::-webkit-scrollbar {
      height: 8px;
    }

    .buku-slider::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .buku-slider::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .buku-slider::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    /* Link wrapper untuk buku card */
    .buku-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .buku-card {
      flex: 0 0 auto;
      width: 180px;
      background: #fff;
      padding: 15px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }

    .buku-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }

    .buku-card img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 10px;
    }

    .judul {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 5px;
      color: #333;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .penulis {
      font-size: 12px;
      color: #666;
      font-style: italic;
    }

    .no-books {
      text-align: center;
      padding: 40px;
      color: #666;
      font-style: italic;
    }

    /* Footer */
    .footer {
      background-color: #c9e4ff;
      padding: 40px 50px;
      margin-top: 50px;
    }

    .footer-content {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 40px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer-section h3 {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #000;
    }

    .footer-section p {
      color: #333;
      margin-bottom: 10px;
    }

    .footer-section a {
      color: #0066cc;
      text-decoration: none;
    }

    .footer-section a:hover {
      text-decoration: underline;
    }

    .footer-bottom {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #ddd;
      color: #666;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        gap: 20px;
        margin: 20px;
        padding: 20px;
      }

      .nav-menu {
        flex-direction: column;
        gap: 15px;
      }

      .search-container {
        width: 100%;
        max-width: 300px;
      }

      .main-content {
        margin: 0 20px;
        padding: 20px;
      }

      .buku-card {
        width: 150px;
      }

      .footer-content {
        grid-template-columns: 1fr;
        gap: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <img class="logo" src="logo_e-snap-removebg-preview.png" alt="Logo SMKN 8 Semarang">
    
    <nav class="nav-menu">
      <div class="nav-item">CATEGORIES</div>
      <div class="nav-item">HISTORY</div>
    </nav>

    <div class="search-container">
      <input type="text" class="search-input" placeholder="Cari buku favoritmu..">
      <img class="search-icon" src="img/search-3.svg" alt="Search">
    </div>

    <div class="auth-buttons">
      <button class="btn btn-login">Login</button>
      <button class="btn btn-signup">Sign Up</button>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-content">
    <?php
    // Query untuk mengambil semua kategori
    $kategoriQuery = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    
    if ($kategoriQuery && $kategoriQuery->num_rows > 0):
      while ($kategori = $kategoriQuery->fetch_assoc()):
        $idKategori = $kategori['id_kategori'];
        $namaKategori = htmlspecialchars(ucfirst($kategori['nama_kategori']));

        // Query untuk mengambil buku berdasarkan kategori - TAMBAHKAN id_buku
        $stmt = $conn->prepare("SELECT id_buku, judul, penulis, cover_buku FROM buku WHERE id_kategori = ? ORDER BY id_buku DESC LIMIT 10");
        $stmt->bind_param("i", $idKategori);
        $stmt->execute();
        $result = $stmt->get_result();
    ?>
        <section class="kategori-section">
          <h2 class="kategori-title"><?php echo $namaKategori; ?></h2>
          
          <?php if ($result && $result->num_rows > 0): ?>
            <div class="buku-slider">
              <?php while ($buku = $result->fetch_assoc()): ?>
                <!-- Wrap buku card dengan link -->
                <a href="baca.php?id=<?php echo $buku['id_buku']; ?>" class="buku-link">
                  <div class="buku-card">
                    <img src="<?php echo htmlspecialchars($buku['cover_buku']); ?>" 
                         alt="Cover <?php echo htmlspecialchars($buku['judul']); ?>"
                         onerror="this.src='img/no-image.jpg'">
                    <div class="judul"><?php echo htmlspecialchars($buku['judul']); ?></div>
                    <div class="penulis"><?php echo htmlspecialchars($buku['penulis']); ?></div>
                  </div>
                </a>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="no-books">Belum ada buku dalam kategori ini</div>
          <?php endif; ?>
        </section>
    <?php 
      endwhile;
    else: 
    ?>
      <div class="no-books">Belum ada kategori yang tersedia</div>
    <?php endif; ?>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <h3>KONTAK</h3>
        <p>Telepon: <a href="tel:024-8312190">(024) 8312190</a></p>
        <p>E-mail: <a href="mailto:smkn8semarang@gmail.com">smkn8semarang@gmail.com</a></p>
        <p>Alamat: Jalan Pandanaran 2 No.12, Mugassari, Kec. Semarang Sel., Kota Semarang, Jawa Tengah 50249</p>
      </div>
      
      <div class="footer-section">
        <h3>JAM OPERASIONAL</h3>
        <p>Senin - Kamis: 07.00 - 16.00</p>
        <p>Jumat: 07.00 - 14.00</p>
        <p style="color: #bc3e3e;">Sabtu - Minggu: LIBUR</p>
      </div>
      
      <div class="footer-section">
        <h3>CATEGORIES</h3>
        <p>EDUCATION</p>
        <p>KIDS</p>
        <p>POPULER</p>
        <p>FICTION</p>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>© All Rights Reserved By Kelompok 3</p>
    </div>
  </footer>
</body>
</html>