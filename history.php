<?php
// ===== PHP LOGIC SECTION =====
include 'koneksi.php';

// Simulasi session user - dalam implementasi nyata, ambil dari session
$user_id = 12311; // Contoh user ID, sesuaikan dengan session login

// Query untuk mengambil history buku yang pernah dibaca user
$historyQuery = $conn->query("
    SELECT 
        h.id_history,
        h.tanggal_baca,
        b.id_buku,
        b.judul,
        b.penulis,
        b.cover_buku,
        k.nama_kategori,
        DATEDIFF(NOW(), h.tanggal_baca) as days_ago
    FROM history h
    JOIN buku b ON h.id_buku = b.id_buku
    LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
    WHERE h.id_user = $user_id
    ORDER BY h.tanggal_baca DESC
");

// Function untuk format waktu
function formatTimeAgo($days) {
    if ($days == 0) {
        return 'Hari ini';
    } elseif ($days == 1) {
        return '1 hari lalu';
    } elseif ($days < 7) {
        return $days . ' hari lalu';
    } elseif ($days < 30) {
        $weeks = floor($days / 7);
        return $weeks == 1 ? '1 minggu lalu' : $weeks . ' minggu lalu';
    } else {
        $months = floor($days / 30);
        return $months == 1 ? '1 bulan lalu' : $months . ' bulan lalu';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Perpustakaan Digital SMKN 8 Semarang</title>
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
            text-decoration: none;
        }

        .nav-item:hover {
            color: #000;
        }

        .nav-item.active {
            color: #007bff;
            font-weight: 700;
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
            background-color: #7cb3e661;
            border-radius: 30px;
            margin: 0 50px;
            padding: 40px;
            min-height: 800px;
        }

        /* History Section */
        .history-section {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .history-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .history-title {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .history-subtitle {
            font-size: 16px;
            color: #666;
            font-style: italic;
        }

        .history-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #007bff;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .buku-link {
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
        }

        .buku-card {
            background: #fff;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            position: relative;
            border: 2px solid transparent;
        }

        .buku-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            border-color: #007bff;
        }

        .buku-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .judul {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.3;
        }

        .penulis {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-bottom: 8px;
        }

        .category-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .read-date {
            font-size: 11px;
            color: #999;
            font-style: italic;
        }

        .history-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            font-size: 8px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 5px;
            z-index: 10;
        }

        .no-history {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            grid-column: 1 / -1;
        }

        .no-history-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-history h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .no-history p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .browse-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
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

            .books-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .history-stats {
                flex-direction: column;
                gap: 15px;
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <img class="logo" src="logo_e-snap-removebg-preview.png" alt="Logo SMKN 8 Semarang">
        
        <nav class="nav-menu">
            <a href="homepage.php" class="nav-item">HOME</a>
            
            <div class="categories-dropdown">
                <div class="nav-item">
                    CATEGORIES
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="dropdown-menu">
                    <a href="education.php?category=education" class="dropdown-item">
                        <span class="dropdown-icon icon-education"></span>
                        Education
                    </a>
                    <a href="kids.php?category=kids" class="dropdown-item">
                        <span class="dropdown-icon icon-kids"></span>
                        Kids
                    </a>
                    <a href="fiction.php?category=fiction" class="dropdown-item">
                        <span class="dropdown-icon icon-fiction"></span>
                        Fiction
                    </a>
                </div>
            </div>
            
            <div class="nav-item active">HISTORY</div>
            
            <form method="GET" action="homepage.php">
                <div class="search-container">
                    <input type="text" name="search" class="search-input" placeholder="Cari buku favoritmu..">
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
        <div class="history-section">
            <div class="history-header">
                <h1 class="history-title">HISTORY</h1>
                <p class="history-subtitle">Riwayat buku yang pernah Anda baca</p>
                
                <?php if ($historyQuery && $historyQuery->num_rows > 0): ?>
                    <div class="history-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $historyQuery->num_rows; ?></div>
                            <div class="stat-label">Total Buku Dibaca</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php
                                // Hitung buku yang dibaca bulan ini
                                $monthlyCount = 0;
                                $tempResult = $conn->query("
                                    SELECT COUNT(*) as monthly_count 
                                    FROM history 
                                    WHERE id_user = $user_id 
                                    AND MONTH(tanggal_baca) = MONTH(NOW()) 
                                    AND YEAR(tanggal_baca) = YEAR(NOW())
                                ");
                                if ($tempResult) {
                                    $monthlyData = $tempResult->fetch_assoc();
                                    $monthlyCount = $monthlyData['monthly_count'];
                                }
                                echo $monthlyCount;
                                ?>
                            </div>
                            <div class="stat-label">Bulan Ini</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php
                                // Hitung kategori favorit
                                $favCategoryResult = $conn->query("
                                    SELECT k.nama_kategori, COUNT(*) as count
                                    FROM history h
                                    JOIN buku b ON h.id_buku = b.id_buku
                                    JOIN kategori k ON b.id_kategori = k.id_kategori
                                    WHERE h.id_user = $user_id
                                    GROUP BY k.id_kategori
                                    ORDER BY count DESC
                                    LIMIT 1
                                ");
                                $favCategory = "N/A";
                                if ($favCategoryResult && $favCategoryResult->num_rows > 0) {
                                    $favData = $favCategoryResult->fetch_assoc();
                                    $favCategory = ucfirst($favData['nama_kategori']);
                                }
                                echo $favCategory;
                                ?>
                            </div>
                            <div class="stat-label">Kategori Favorit</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="books-grid">
                <?php if ($historyQuery && $historyQuery->num_rows > 0): ?>
                    <?php while ($buku = $historyQuery->fetch_assoc()): ?>
                        <a href="baca.php?id=<?php echo $buku['id_buku']; ?>" class="buku-link">
                            <div class="buku-card">
                                <span class="history-badge">READ</span>
                                <img src="<?php echo htmlspecialchars($buku['cover_buku']); ?>" 
                                     alt="Cover <?php echo htmlspecialchars($buku['judul']); ?>"
                                     onerror="this.src='img/no-image.jpg'">
                                <div class="judul"><?php echo htmlspecialchars($buku['judul']); ?></div>
                                <div class="penulis"><?php echo htmlspecialchars($buku['penulis']); ?></div>
                                <?php if ($buku['nama_kategori']): ?>
                                    <div class="category-tag"><?php echo htmlspecialchars($buku['nama_kategori']); ?></div>
                                <?php endif; ?>
                                <div class="read-date">
                                    Dibaca <?php echo formatTimeAgo($buku['days_ago']); ?>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-history">
                        <div class="no-history-icon">📚</div>
                        <h3>Belum Ada Riwayat Baca</h3>
                        <p>Anda belum membaca buku apapun. Mulai jelajahi koleksi kami!</p>
                        <a href="homepage.php" class="browse-btn">Jelajahi Buku</a>
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
                        <li><a href="education.php">EDUCATION</a></li>
                        <li><a href="kids.php">KIDS</a></li>
                        <li><a href="fiction.php">FICTION</a></li>
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