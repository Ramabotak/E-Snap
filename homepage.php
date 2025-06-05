<?php
// ===== PHP LOGIC SECTION =====
include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Ambil data user dari session
$user_id = $_SESSION['id_user'];
$user_nama = $_SESSION['nama'];
$user_role = $_SESSION['role'];


// Ambil keyword dari input
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk mengambil buku yang baru ditambahkan (dalam 7 hari terakhir) 
// ATAU buku yang baru diupdate (dalam 3 hari terakhir)
$newBooksQuery = $conn->query("
    SELECT 
        id_buku, 
        judul, 
        penulis, 
        cover_buku, 
        created_at,
        updated_at,
        CASE 
            WHEN DATEDIFF(NOW(), created_at) <= 7 THEN 'new'
            WHEN DATEDIFF(NOW(), updated_at) <= 3 THEN 'updated'
            ELSE 'old'
        END as status,
        GREATEST(created_at, COALESCE(updated_at, created_at)) as latest_activity
    FROM buku 
    WHERE 
        DATEDIFF(NOW(), created_at) <= 7 
        OR DATEDIFF(NOW(), COALESCE(updated_at, created_at)) <= 3
    ORDER BY latest_activity DESC 
    LIMIT 5
");

// Query untuk mengambil 5 buku dari kategori education
$educationQuery = $conn->query("
    SELECT b.id_buku, b.judul, b.penulis, b.cover_buku 
    FROM buku b 
    JOIN kategori k ON b.id_kategori = k.id_kategori 
    WHERE k.nama_kategori = 'education' 
    ORDER BY b.id_buku DESC 
    LIMIT 5
");

// Query untuk hasil pencarian
$searchResults = null;
if (!empty($keyword)) {
    $keyword_escaped = mysqli_real_escape_string($conn, $keyword);
    $searchQuery = "SELECT id_buku, judul, penulis, cover_buku FROM buku WHERE judul LIKE '%$keyword_escaped%' OR penulis LIKE '%$keyword_escaped%'";
    $searchResults = mysqli_query($conn, $searchQuery);
}

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
    <title>Perpustakaan Digital - SMKN 8 Semarang</title>
    <style>
         /* Base styles remain the same */
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

/* Hero Section */
.hero-section {
    margin: 0 50px 30px 50px;
    border-radius: 30px;
    overflow: hidden;
    position: relative;
    height: 400px;
}

.hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.9;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.hero-title {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 15px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-subtitle {
    font-size: 20px;
    font-weight: 400;
    opacity: 0.95;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
}

/* Main Content */
.main-content {
    background-color: #7cb3e661;
    border-radius: 30px;
    margin: 0 50px;
    padding: 40px;
    min-height: 800px;
}

/* Search Results Section */
.search-results-section {
    margin-bottom: 50px;
    border-radius: 20px;
    padding: 30px;
    background: #fff;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.search-results-header {
    margin-bottom: 25px;
}

.search-results-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.search-info {
    font-size: 14px;
    color: #666;
    font-style: italic;
}

/* Category Sections */
.category-section {
    margin-top: 50px;
    margin-bottom: 50px;
    border-radius: 20px;
    padding: 30px;
    background: #fff;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
}

.category-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    position: relative;
}

.view-all-btn {
    background: #007bff;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.view-all-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.books-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
}

.buku-link {
    text-decoration: none;
    color: inherit;
    display: block;
    position: relative;
}

.buku-card {
    flex: 0 0 auto;
    width: 100%;
    background: #fff;
    padding: 15px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    position: relative;
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
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.3;
}

.penulis {
    font-size: 10px;
    color: #666;
    font-style: italic;
}

.book-new-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: linear-gradient(45deg, #ff6b6b, #ee5a52);
    color: white;
    font-size: 8px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 5px;
    z-index: 10;
}

.book-updated-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: linear-gradient(45deg, #4ecdc4, #44a08d);
    color: white;
    font-size: 8px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 5px;
    z-index: 10;
}

.update-info {
    font-size: 10px;
    color: #666;
    margin-top: 2px;
    font-style: italic;
}

.no-books {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
    grid-column: 1 / -1;
}

.clear-search {
    background: #dc3545;
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    margin-left: 10px;
    transition: all 0.3s;
}

.clear-search:hover {
    background: #c82333;
    transform: translateY(-1px);
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

/* Enhanced Responsive Design */

/* Large Tablets (1024px and below) */
@media (max-width: 1024px) {
    .header {
        padding: 15px 30px;
        margin: 30px 30px;
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
    
    .hero-section {
        margin: 0 30px 20px 30px;
        height: 350px;
    }
    
    .hero-title {
        font-size: 42px;
    }
    
    .hero-subtitle {
        font-size: 18px;
    }
    
    .main-content {
        margin: 0 30px;
        padding: 30px;
    }
    
    .books-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    
    .footer {
        padding: 30px 30px;
    }
    
    .footer-container {
        padding: 0 30px;
    }
}

/* Medium Tablets (768px and below) */
@media (max-width: 768px) {
    .header {
        flex-direction: column;
        gap: 15px;
        margin: 20px 20px;
        padding: 20px;
        border-radius: 25px;
    }
    
    .logo {
        width: 60px;
        height: 60px;
    }

    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }
    
    .nav-item {
        font-size: 14px;
    }

    .search-container {
        width: 100%;
        max-width: 280px;
        order: 1;
    }
    
    .auth-buttons {
        order: 2;
        gap: 8px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 12px;
    }

    .hero-section {
        margin: 0 20px 20px 20px;
        height: 250px;
        border-radius: 20px;
    }

    .hero-title {
        font-size: 28px;
    }

    .hero-subtitle {
        font-size: 14px;
    }

    .main-content {
        margin: 0 20px;
        padding: 20px;
        border-radius: 20px;
    }
    
    .category-section {
        padding: 20px;
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    .category-title {
        font-size: 20px;
    }
    
    .search-results-section {
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .search-results-title {
        font-size: 20px;
    }

    .books-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .buku-card img {
        height: 180px;
    }
    
    .judul {
        font-size: 11px;
    }
    
    .penulis {
        font-size: 9px;
    }

    .footer {
        padding: 30px 20px;
    }
    
    .footer-container {
        padding: 0 20px;
    }

    .footer-content {
        grid-template-columns: 1fr;
        gap: 25px;
        text-align: center;
    }

    .footer-logo {
        align-items: center;
    }
    
    .footer-logo-img {
        width: 80px;
        height: 80px;
    }

    .footer-hours,
    .footer-categories {
        text-align: center;
    }

    .dropdown-menu {
        position: fixed;
        top: auto;
        left: 50%;
        transform: translateX(-50%);
        width: 90%;
        max-width: 300px;
        margin-top: 5px;
    }
}

/* Small Mobile (480px and below) */
@media (max-width: 480px) {
    .header {
        margin: 15px 15px;
        padding: 15px;
        border-radius: 20px;
    }
    
    .logo {
        width: 50px;
        height: 50px;
    }
    
    .nav-menu {
        gap: 10px;
    }
    
    .nav-item {
        font-size: 12px;
        padding: 5px 8px;
    }
    
    .search-container {
        max-width: 200px;
        padding: 6px 10px;
    }
    
    .search-input {
        font-size: 12px;
    }
    
    .search-icon {
        width: 16px;
        height: 16px;
    }
    
    .btn {
        padding: 5px 10px;
        font-size: 11px;
    }

    .hero-section {
        margin: 0 15px 15px 15px;
        height: 200px;
        border-radius: 15px;
    }

    .hero-title {
        font-size: 24px;
    }

    .hero-subtitle {
        font-size: 12px;
    }

    .main-content {
        margin: 0 15px;
        padding: 15px;
        border-radius: 15px;
    }
    
    .category-section {
        padding: 15px;
        margin-top: 20px;
        margin-bottom: 20px;
        border-radius: 15px;
    }
    
    .category-title {
        font-size: 18px;
    }
    
    .search-results-section {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 15px;
    }
    
    .search-results-title {
        font-size: 18px;
    }

    .books-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .buku-card {
        padding: 10px;
        border-radius: 10px;
    }
    
    .buku-card img {
        height: 150px;
        border-radius: 8px;
    }
    
    .judul {
        font-size: 10px;
        margin-bottom: 3px;
    }
    
    .penulis {
        font-size: 8px;
    }
    
    .update-info {
        font-size: 8px;
    }
    
    .book-new-badge,
    .book-updated-badge {
        font-size: 7px;
        padding: 1px 3px;
        border-radius: 3px;
    }

    .footer {
        padding: 20px 15px;
    }
    
    .footer-container {
        padding: 0 15px;
    }
    
    .footer-content {
        gap: 20px;
    }
    
    .footer-logo-img {
        width: 60px;
        height: 60px;
    }
    
    .company-info p,
    .hours-info p,
    .contact-info p {
        font-size: 12px;
    }
    
    .footer-hours h4,
    .footer-categories h4,
    .contact-info h4 {
        font-size: 14px;
    }
    
    .categories-list a,
    .categories-list span {
        font-size: 12px;
    }
    
    .dropdown-menu {
        width: 95%;
        max-width: 250px;
    }
    
    .dropdown-item {
        padding: 10px 12px;
        font-size: 14px;
    }
}

/* Extra Small Mobile (360px and below) */
@media (max-width: 360px) {
    .header {
        margin: 10px 10px;
        padding: 12px;
    }
    
    .nav-menu {
        gap: 8px;
    }
    
    .nav-item {
        font-size: 11px;
        padding: 4px 6px;
    }
    
    .search-container {
        max-width: 180px;
    }

    .hero-section {
        margin: 0 10px 10px 10px;
        height: 180px;
    }

    .hero-title {
        font-size: 20px;
    }

    .hero-subtitle {
        font-size: 11px;
    }

    .main-content {
        margin: 0 10px;
        padding: 12px;
    }
    
    .category-section,
    .search-results-section {
        padding: 12px;
        margin-top: 15px;
        margin-bottom: 15px;
    }

    .books-grid {
        gap: 6px;
    }
    
    .buku-card {
        padding: 8px;
    }
    
    .buku-card img {
        height: 120px;
    }

    .footer {
        padding: 15px 10px;
    }
    
    .footer-container {
        padding: 0 10px;
    }
}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <img class="logo" src="logo_e-snap-removebg-preview.png" alt="Logo SMKN 8 Semarang">
        
         <nav class="nav-menu">
            
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
                    <a href="fiction.php?category=fiksi" class="dropdown-item">
                        <span class="dropdown-icon icon-fiction"></span>
                        Fiction
                    </a>
                    
                </div>
            </div>
            <a class="nav-item" href="history.php">HISTORY</a>
            
        <form method="GET" action="homepage.php">
            <div class="search-container">
                <input type="text" name="search" class="search-input" placeholder="Cari buku favoritmu.." 
                       value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit" style="background: none; border: none; cursor: pointer;">
                    <img class="search-icon" src="Search 3.png" alt="Search">
                </button>
            </div>
        </form>
        
        <div class="auth-buttons">
            <button class="btn btn-login">Login</button>
            <button class="btn btn-signup">Sign Up</button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <img src="perpus.jpeg" alt="Perpustakaan SMKN 8 Semarang" class="hero-image" onerror="this.style.display='none'">
        <div class="hero-overlay">
            <div class="hero-content">
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Search Results Section -->
        <?php if (!empty($keyword)): ?>
            <div class="search-results-section">
                <div class="search-results-header">
                    <h3 class="search-results-title">Hasil Pencarian untuk "<?php echo htmlspecialchars($keyword); ?>"</h3>
                    <div class="search-info">
                        <?php if ($searchResults && mysqli_num_rows($searchResults) > 0): ?>
                            Ditemukan <?php echo mysqli_num_rows($searchResults); ?> buku
                        <?php else: ?>
                            Tidak ada buku yang ditemukan
                        <?php endif; ?>
                        <a href="homepage.php" class="clear-search">Hapus Pencarian</a>
                    </div>
                </div>
                <div class="books-grid">
                    <?php if ($searchResults && mysqli_num_rows($searchResults) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($searchResults)): ?>
                            <a href="baca.php?id=<?php echo $row['id_buku']; ?>" class="buku-link">
                                <div class="buku-card">
                                    <img src="<?php echo htmlspecialchars($row['cover_buku']); ?>" 
                                         alt="Cover <?php echo htmlspecialchars($row['judul']); ?>"
                                         onerror="this.src='img/no-image.jpg'">
                                    <div class="judul"><?php echo htmlspecialchars($row['judul']); ?></div>
                                    <div class="penulis"><?php echo htmlspecialchars($row['penulis']); ?></div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-books">
                            Tidak ada buku yang ditemukan dengan kata kunci "<?php echo htmlspecialchars($keyword); ?>"
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- New Books Section -->
        <?php if (empty($keyword)): ?>
        <div class="category-section">
            <div class="category-header">
                <h3 class="category-title">New</h3>
            </div>
            <div class="books-grid">
                <?php if ($newBooksQuery && $newBooksQuery->num_rows > 0): ?>
                    <?php while ($buku = $newBooksQuery->fetch_assoc()): ?>
                        <?php
                        // Menentukan badge yang akan ditampilkan
                        $badgeClass = '';
                        $badgeText = '';
                        if ($buku['status'] == 'new') {
                            $badgeClass = 'book-new-badge';
                        } elseif ($buku['status'] == 'updated') {
                            $badgeClass = 'book-updated-badge';
                            $badgeText = 'UPDATED';
                        }
                        
                        $timeInfo = formatTimeAgo($buku['latest_activity']);
                        ?>
                        <a href="baca.php?id=<?php echo $buku['id_buku']; ?>" class="buku-link">
                            <div class="buku-card">
                                <?php if ($badgeText): ?>
                                    <span class="<?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                                <?php endif; ?>
                                <img src="<?php echo htmlspecialchars($buku['cover_buku']); ?>" 
                                     alt="Cover <?php echo htmlspecialchars($buku['judul']); ?>"
                                     onerror="this.src='img/no-image.jpg'">
                                <div class="judul"><?php echo htmlspecialchars($buku['judul']); ?></div>
                                <div class="penulis"><?php echo htmlspecialchars($buku['penulis']); ?></div>
                                <div class="update-info"><?php echo $timeInfo; ?></div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-books">Belum ada buku terbaru</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Education Category Section -->
        <div class="category-section">
            <div class="category-header">
                <h3 class="category-title">Education</h3>
            </div>
            <div class="books-grid">
                <?php if ($educationQuery && $educationQuery->num_rows > 0): ?>
                    <?php while ($buku = $educationQuery->fetch_assoc()): ?>
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
                    <div class="no-books">Belum ada buku Education</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
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
                        <li><span>EDUCATION</span></li>
                        <li><span>KIDS</span></li>
                        <li><span>FICTION</span></li>
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