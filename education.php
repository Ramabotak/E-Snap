<?php
// ===== PHP LOGIC SECTION =====
include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}
// Ambil parameter kategori dari URL
$kategori = isset($_GET['category']) ? $_GET['category'] : 'education';
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk mengambil buku berdasarkan kategori
$categoryQuery = "
    SELECT b.id_buku, b.judul, b.penulis, b.cover_buku, k.nama_kategori
    FROM buku b 
    JOIN kategori k ON b.id_kategori = k.id_kategori 
    WHERE k.nama_kategori = ?
";

// Tambahkan kondisi pencarian jika ada keyword
if (!empty($keyword)) {
    $categoryQuery .= " AND (b.judul LIKE ? OR b.penulis LIKE ?)";
}

$categoryQuery .= " ORDER BY b.id_buku DESC";

$stmt = $conn->prepare($categoryQuery);

if (!empty($keyword)) {
    $searchTerm = "%$keyword%";
    $stmt->bind_param("sss", $kategori, $searchTerm, $searchTerm);
} else {
    $stmt->bind_param("s", $kategori);
}

$stmt->execute();
$categoryResult = $stmt->get_result();

// Mengubah kategori untuk display
$displayCategory = strtoupper($kategori);

// Function untuk format waktu
function formatTimeAgo($datetime) {
    $latestDate = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($latestDate);
    
    if ($diff->days == 0) {
        return 'Hari ini';
    } elseif ($diff->days == 1) {
        return '1 hari lalu';
    } else {
        return $diff->days . ' hari lalu';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $displayCategory; ?> - Perpustakaan Digital SMKN 8 Semarang</title>
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
            position: relative;
        }

        .nav-item:hover {
            color: #000;
        }

        .nav-item a {
            text-decoration: none;
            color: inherit;
        }

        /* Categories Dropdown */
        .categories-dropdown {
            position: relative;
            display: inline-block;
        }

        .categories-dropdown .nav-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dropdown-arrow {
            font-size: 12px;
            transition: transform 0.3s;
        }

        .categories-dropdown:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            margin-top: 10px;
        }

        .categories-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            margin: 4px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #007bff;
        }

        .dropdown-item:first-child {
            margin-top: 8px;
        }

        .dropdown-item:last-child {
            margin-bottom: 8px;
        }

        .dropdown-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        /* Icon styles */
        .icon-popular::before { content: "🔥"; }
        .icon-education::before { content: "🎓"; }
        .icon-kids::before { content: "👶"; }
        .icon-fiction::before { content: "📚"; }

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
            background: linear-gradient( #7cb3e661);
            border-radius: 30px;
            margin: 0 50px;
            padding: 40px;
            min-height: 800px;
        }

        .category-title {
            font-size: 48px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
        }

        .breadcrumb {
            font-size: 14px;
            color: #888;
        }

        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Search Results Info */
        .search-info {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .results-count {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .clear-search {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .clear-search:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Books Grid */
        .books-container {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            justify-items: center;
        }

        .buku-link {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
            max-width: 200px;
        }

        .buku-card {
            background: #fff;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            width: 100%;
            border: 2px solid transparent;
        }

        .buku-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.2);
        }

        .buku-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .buku-card:hover img {
            transform: scale(1.05);
        }

        .judul {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            line-height: 1.4;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .penulis {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-bottom: 5px;
        }

        .no-books {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
            font-style: italic;
        }

        .no-books-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Footer */
        .footer {
            background-color: #c9e4ff;
            padding: 40px 50px;
            margin-top: 50px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 20px;
        }

        .footer-logo {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-logo-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
            border-radius: 12px;
            padding: 8px;
        }

        .company-info h3 {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .company-info p {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 5px;
        }

        .footer-hours {
            text-align: left;
        }

        .footer-hours h4 {
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .hours-info {
            margin-bottom: 20px;
        }

        .hours-info p {
            font-size: 14px;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .contact-info h4 {
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .contact-info p {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .contact-info a {
            color: black;
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .footer-categories {
            text-align: left;
        }

        .footer-categories h4 {
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .categories-list {
            list-style: none;
        }

        .categories-list li {
            margin-bottom: 10px;
        }

        .categories-list a,
        .categories-list span {
            color: black;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .categories-list a:hover {
            color: #b8d4f0;
            text-decoration: underline;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            color: rgba(0,0,0,0.8);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 5px;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background-color: #333;
            margin: 3px 0;
            transition: 0.3s;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .books-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .header {
                padding: 15px 30px;
                margin: 30px 30px;
            }
            
            .main-content {
                margin: 0 30px;
                padding: 30px;
            }
        }

        @media (max-width: 992px) {
            .books-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .nav-menu {
                gap: 20px;
            }
            
            .nav-item {
                font-size: 16px;
            }
            
            .search-container {
                width: 250px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                margin: 20px;
                padding: 20px;
                border-radius: 20px;
            }

            .nav-menu {
                flex-direction: column;
                gap: 15px;
                width: 100%;
            }

            .search-container {
                width: 100%;
                max-width: 300px;
            }

            .auth-buttons {
                justify-content: center;
                width: 100%;
            }

            .main-content {
                margin: 0 20px;
                padding: 20px;
                border-radius: 20px;
            }

            .category-title {
                font-size: 32px;
            }

            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .books-container {
                padding: 20px;
            }

            .buku-card img {
                height: 180px;
            }

            .search-info {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .footer {
                padding: 30px 20px;
            }

            .footer-container {
                padding: 0 20px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }

            .footer-logo {
                align-items: center;
            }

            .footer-hours,
            .footer-categories {
                text-align: center;
            }
        }

        @media (max-width: 600px) {
            .header {
                margin: 15px;
                padding: 15px;
            }
            
            .main-content {
                margin: 0 15px;
                padding: 15px;
            }
            
            .category-title {
                font-size: 28px;
                margin-bottom: 20px;
            }
            
            .books-grid {
                gap: 12px;
            }
            
            .books-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .buku-card {
                padding: 10px;
            }
            
            .buku-card img {
                height: 140px;
            }
            
            .judul {
                font-size: 12px;
                height: 35px;
            }
            
            .penulis {
                font-size: 11px;
            }
            
            .category-title {
                font-size: 24px;
            }
            
            .nav-item {
                font-size: 14px;
            }
            
            .search-input {
                font-size: 12px;
            }
        }

        @media (max-width: 360px) {
            .header {
                margin: 10px;
                padding: 12px;
            }
            
            .main-content {
                margin: 0 10px;
                padding: 12px;
            }
            
            .books-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            
            .buku-card img {
                height: 120px;
            }
            
            .category-title {
                font-size: 20px;
            }
            
            .footer {
                padding: 20px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <img class="logo" src="logo_e-snap-removebg-preview.png" alt="Logo SMKN 8 Semarang">
        
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="homepage.php">HOME</a>
            </div>
            
            <div class="categories-dropdown">
                <div class="nav-item">
                    CATEGORIES
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="dropdown-menu">
                    <a href="?category=education" class="dropdown-item">
                        <span class="dropdown-icon icon-education"></span>
                        Education
                    </a>
                    <a href="?category=kids" class="dropdown-item">
                        <span class="dropdown-icon icon-kids"></span>
                        Kids
                    </a>
                    <a href="?category=fiction" class="dropdown-item">
                        <span class="dropdown-icon icon-fiction"></span>
                        Fiction
                    </a>
                </div>
            </div>
            
            <div class="nav-item">HISTORY</div>
            
            <form method="GET" style="display: flex;">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($kategori); ?>">
                <div class="search-container">
                    <input type="text" name="search" class="search-input" placeholder="Cari buku dalam kategori <?php echo $displayCategory; ?>.." 
                           value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" style="background: none; border: none; cursor: pointer;">
                        <img class="search-icon" src="Search 3.png" alt="Search">
                    </button>
                </div>
            </form>
        </nav>
        
        <div class="auth-buttons">
            <button class="btn btn-login">Login</button>
            <button class="btn btn-signup">Sign Up</button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Category Header -->
        <div class="category-header">
            <h1 class="category-title"><?php echo $displayCategory; ?></h1>
            </div>
        </div>

        <!-- Search Info -->
        <?php if (!empty($keyword)): ?>
            <div class="search-info">
                <div class="results-count">
                    <?php if ($categoryResult && $categoryResult->num_rows > 0): ?>
                        Ditemukan <?php echo $categoryResult->num_rows; ?> buku dengan kata kunci "<?php echo htmlspecialchars($keyword); ?>" dalam kategori <?php echo $displayCategory; ?>
                    <?php else: ?>
                        Tidak ada buku yang ditemukan dengan kata kunci "<?php echo htmlspecialchars($keyword); ?>" dalam kategori <?php echo $displayCategory; ?>
                    <?php endif; ?>
                </div>
                <a href="?category=<?php echo $kategori; ?>" class="clear-search">Hapus Pencarian</a>
            </div>
        <?php endif; ?>

        <!-- Books Container -->
        <div class="books-container">
            <div class="books-grid">
                <?php if ($categoryResult && $categoryResult->num_rows > 0): ?>
                    <?php while ($buku = $categoryResult->fetch_assoc()): ?>
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
                <?php else: ?>
                    <div class="no-books">
                        <div class="no-books-icon">📚</div>
                        <?php if (!empty($keyword)): ?>
                            Tidak ada buku yang ditemukan dengan kata kunci "<?php echo htmlspecialchars($keyword); ?>" dalam kategori <?php echo $displayCategory; ?>
                        <?php else: ?>
                            Belum ada buku dalam kategori <?php echo $displayCategory; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <!-- Logo dan Info Perusahaan -->
                <div class="footer-logo">
                    <img src="logo_e-snap-removebg-preview.png" alt="Logo E-SNAP" class="footer-logo-img" onerror="this.style.display='none'">
                    <div class="company-info">
                        <p>Jalan Pandanaran 2 No.12,</p>
                        <p>Mugassari, Semarang Sel.,</p>
                        <p>Kota Semarang, Jawa Tengah</p>
                        <p>50249</p>
                    </div>
                </div>

                <!-- Jam Operasional dan Kontak -->
                <div class="footer-hours">
                    <h4>Jam Operasional</h4>
                    <div class="hours-info">
                        <p>Senin - Kamis : 07:00 - 16:00</p>
                        <p>Jumat : 07:00-14:00</p>
                        <p>Sabtu - Minggu : LIBUR</p>
                    </div>
                    
                    <div class="contact-info">
                        <h4>Kontak</h4>
                        <p>Telepon: <a href="tel:024-8312190">(024) 8312190</a></p>
                        <p>E-mail: <a href="mailto:smkn8semarang@gmail.com">smkn8semarang@gmail.com</a></p>
                    </div>
                </div>

                <!-- Kategori -->
                <div class="footer-categories">
                    <h4>Categories</h4>
                    <ul class="categories-list">
                        <li><a href="?category=education">EDUCATION</a></li>
                        <li><a href="?category=kids">KIDS</a></li>
                        <li><a href="?category=fiction">FICTION</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© All Rights Reserved By Kelompok 3</p>
            </div>
        </div>
    </footer>
</body>
</html>