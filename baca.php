<?php
include 'koneksi.php';

// Validasi dan ambil ID buku dari URL
$id_buku = null;
if (isset($_GET['id_buku']) && !empty($_GET['id_buku'])) {
    $id_buku = mysqli_real_escape_string($conn, $_GET['id_buku']);
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_buku = mysqli_real_escape_string($conn, $_GET['id']);
}

if (!$id_buku) {
    die("ID buku tidak valid atau tidak ditemukan");
}

// Query untuk mengambil data buku
$query = "SELECT * FROM buku WHERE id_buku = '$id_buku'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Buku tidak ditemukan dengan ID: " . htmlspecialchars($id_buku));
}

$row = mysqli_fetch_assoc($result);

// Cek apakah user sudah login
if (isset($_SESSION['id_user'])) {
    $id_user = mysqli_real_escape_string($conn, $_SESSION['id_user']);
    $cek = mysqli_query($conn, "SELECT * FROM history WHERE id_user='$id_user' AND id_buku='$id_buku'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO history (id_user, id_buku, tanggal_baca) VALUES ('$id_user', '$id_buku', NOW())");
    } else {
        mysqli_query($conn, "UPDATE history SET tanggal_baca=NOW() WHERE id_user='$id_user' AND id_buku='$id_buku'");
    }
}

// Deteksi perangkat mobile
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baca Buku - <?= htmlspecialchars($row['judul']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .controls {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s ease;
            text-align: center;
            min-width: 120px;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .pdf-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .pdf-viewer {
            width: 100%;
            height: 80vh;
            min-height: 600px;
            border: none;
            display: block;
        }

        .mobile-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .mobile-options {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .container {
                padding: 10px;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .pdf-viewer {
                height: 70vh;
                min-height: 400px;
            }

            .mobile-options {
                grid-template-columns: 1fr;
            }
        }

        /* Fullscreen styles */
        .pdf-container.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            border-radius: 0;
        }

        .pdf-container.fullscreen .pdf-viewer {
            height: 100vh;
        }

        .fallback-viewer {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .fallback-viewer h3 {
            color: #667eea;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?= htmlspecialchars($row['judul']); ?></h1>
            <p><strong>Penulis:</strong> <?= htmlspecialchars($row['penulis']); ?></p>
        </div>
    </div>

    <div class="container">
        <div class="controls">
            <div>
                <a href="homepage.php" class="btn btn-secondary">← Kembali ke Beranda</a>
            </div>
            <div>
                <?php if (!isMobile()): ?>
                    <button onclick="toggleFullscreen()" class="btn" id="fullscreenBtn">📖 Mode Fullscreen</button>
                <?php endif; ?>
                <button onclick="downloadPDF()" class="btn">📥 Download</button>
                <a href="file/<?= htmlspecialchars($row['file_buku']); ?>" target="_blank" class="btn btn-success">🔗 Buka di Tab Baru</a>
            </div>
        </div>

        <?php if (!empty($row['file_buku'])): ?>
            <?php if (isMobile()): ?>
                <!-- Mobile Warning dan Options -->
                <div class="mobile-warning">
                    <h3>📱 Tampilan Mobile Terdeteksi</h3>
                    <p>Beberapa browser mobile mungkin tidak dapat menampilkan PDF secara langsung. Gunakan salah satu opsi di bawah:</p>
                    <div class="mobile-options">
                        <a href="file/<?= htmlspecialchars($row['file_buku']); ?>" target="_blank" class="btn btn-success">
                            🔗 Buka PDF di Tab Baru
                        </a>
                        <button onclick="downloadPDF()" class="btn">
                            📥 Download PDF
                        </button>
                        <button onclick="showMobileViewer()" class="btn" id="showViewerBtn">
                            👁️ Coba Tampilkan di Sini
                        </button>
                    </div>
                </div>

                <!-- PDF Container for Mobile (Initially Hidden) -->
                <div class="pdf-container" id="pdfContainer" style="display: none;">
                    <div class="loading" id="loadingIndicator">
                        <div class="spinner"></div>
                        <p>Memuat PDF...</p>
                    </div>
                    <iframe 
                        src="" 
                        class="pdf-viewer" 
                        id="pdfViewer"
                        onload="hideLoading()"
                        onerror="showMobileError()"
                        title="PDF Viewer - <?= htmlspecialchars($row['judul']); ?>">
                    </iframe>
                </div>

                <!-- Fallback Viewer -->
                <div class="fallback-viewer" id="fallbackViewer" style="display: none;">
                    <h3>❌ PDF Tidak Dapat Ditampilkan</h3>
                    <p>Browser Anda tidak mendukung tampilan PDF secara langsung.</p>
                    <p>Silakan gunakan opsi download atau buka di tab baru.</p>
                    <div style="margin-top: 20px;">
                        <a href="file/<?= htmlspecialchars($row['file_buku']); ?>" target="_blank" class="btn btn-success">
                            🔗 Buka di Tab Baru
                        </a>
                        <button onclick="downloadPDF()" class="btn" style="margin-left: 10px;">
                            📥 Download PDF
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Desktop View -->
                <div class="pdf-container" id="pdfContainer">
                    <div class="loading" id="loadingIndicator">
                        <div class="spinner"></div>
                        <p>Memuat PDF...</p>
                    </div>
                    <iframe 
                        src="file/<?= htmlspecialchars($row['file_buku']); ?>#toolbar=1&navpanes=1&scrollbar=1&page=1&view=FitH" 
                        class="pdf-viewer" 
                        id="pdfViewer"
                        onload="hideLoading()"
                        onerror="showError()"
                        title="PDF Viewer - <?= htmlspecialchars($row['judul']); ?>">
                    </iframe>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="error-message">
                <h3>📚 File buku tidak tersedia</h3>
                <p>Maaf, file PDF untuk buku ini belum diupload atau tidak dapat diakses.</p>
                <a href="homepage.php" class="btn" style="margin-top: 15px;">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let isFullscreen = false;
        let isMobileDevice = <?= isMobile() ? 'true' : 'false'; ?>;

        function toggleFullscreen() {
            const container = document.getElementById('pdfContainer');
            const btn = document.getElementById('fullscreenBtn');
            
            if (!isFullscreen) {
                container.classList.add('fullscreen');
                btn.innerHTML = '🚪 Keluar Fullscreen';
                document.body.style.overflow = 'hidden';
                isFullscreen = true;
            } else {
                container.classList.remove('fullscreen');
                btn.innerHTML = '📖 Mode Fullscreen';
                document.body.style.overflow = 'auto';
                isFullscreen = false;
            }
        }

        function downloadPDF() {
            <?php if (!empty($row['file_buku'])): ?>
                const link = document.createElement('a');
                link.href = 'file/<?= htmlspecialchars($row['file_buku']); ?>';
                link.download = '<?= htmlspecialchars($row['judul']); ?>.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            <?php else: ?>
                alert('File tidak tersedia untuk didownload');
            <?php endif; ?>
        }

        function showMobileViewer() {
            const container = document.getElementById('pdfContainer');
            const viewer = document.getElementById('pdfViewer');
            const btn = document.getElementById('showViewerBtn');
            
            // Tampilkan container
            container.style.display = 'block';
            
            // Set src iframe dengan parameter minimal untuk mobile
            viewer.src = 'file/<?= htmlspecialchars($row['file_buku']); ?>';
            
            // Update button
            btn.innerHTML = '🔄 Refresh Viewer';
            btn.onclick = function() {
                viewer.src = viewer.src + '?t=' + new Date().getTime();
            };
            
            // Auto fallback after 15 seconds
            setTimeout(function() {
                if (document.getElementById('loadingIndicator').style.display !== 'none') {
                    showMobileError();
                }
            }, 15000);
        }

        function hideLoading() {
            document.getElementById('loadingIndicator').style.display = 'none';
        }

        function showError() {
            const loading = document.getElementById('loadingIndicator');
            loading.innerHTML = `
                <div style="color: #dc3545;">
                    <h3>❌ Gagal memuat PDF</h3>
                    <p>File PDF tidak dapat ditampilkan. Silakan coba download file atau buka di tab baru.</p>
                    <button onclick="downloadPDF()" class="btn" style="margin: 10px;">📥 Download</button>
                    <a href="file/<?= htmlspecialchars($row['file_buku']); ?>" target="_blank" class="btn btn-success" style="margin: 10px;">🔗 Buka di Tab Baru</a>
                </div>
            `;
        }

        function showMobileError() {
            document.getElementById('pdfContainer').style.display = 'none';
            document.getElementById('fallbackViewer').style.display = 'block';
        }

        // Keyboard shortcuts (hanya untuk desktop)
        if (!isMobileDevice) {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F11' || (e.key === 'f' && e.ctrlKey)) {
                    e.preventDefault();
                    toggleFullscreen();
                }
                if (e.key === 'Escape' && isFullscreen) {
                    toggleFullscreen();
                }
            });
        }

        // Auto-hide loading after 10 seconds
        setTimeout(function() {
            const loading = document.getElementById('loadingIndicator');
            if (loading && loading.style.display !== 'none') {
                if (isMobileDevice) {
                    showMobileError();
                } else {
                    showError();
                }
            }
        }, 10000);
    </script>
</body>
</html>