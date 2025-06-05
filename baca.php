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

// Fungsi untuk membuat URL absolut
function getAbsoluteURL($relativePath) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $currentDir = dirname($_SERVER['REQUEST_URI']);
    
    // Pastikan tidak ada double slash
    $currentDir = rtrim($currentDir, '/');
    $relativePath = ltrim($relativePath, '/');
    
    return $protocol . $host . $currentDir . '/' . $relativePath;
}

// Cek keberadaan file
$file_exists = false;
$file_path = '';
$absolute_url = '';
$file_size = 0;

if (!empty($row['file_buku'])) {
    $file_path = 'file/' . $row['file_buku'];
    $absolute_url = getAbsoluteURL($file_path);
    
    if (file_exists($file_path)) {
        $file_exists = true;
        $file_size = filesize($file_path);
    }
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

        .debug-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
            display: none;
        }

        .debug-info.show {
            display: block;
        }

        .debug-info h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-family: 'Segoe UI', sans-serif;
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

        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }

        .file-status {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .status-ok { border-left: 4px solid #28a745; }
        .status-error { border-left: 4px solid #dc3545; }
        .status-warning { border-left: 4px solid #ffc107; }

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

        .url-test {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
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
        <!-- Debug Info -->
        <div class="debug-info" id="debugInfo">
            <h4>🔍 Debug Information</h4>
            <p><strong>File Name:</strong> <?= htmlspecialchars($row['file_buku'] ?? 'NULL'); ?></p>
            <p><strong>Relative Path:</strong> <?= htmlspecialchars($file_path); ?></p>
            <p><strong>Absolute URL:</strong> <?= htmlspecialchars($absolute_url); ?></p>
            <p><strong>File Exists:</strong> <?= $file_exists ? 'YES' : 'NO'; ?></p>
            <p><strong>File Size:</strong> <?= $file_exists ? number_format($file_size) . ' bytes' : 'N/A'; ?></p>
            <p><strong>Server Path:</strong> <?= realpath($file_path) ?: 'Path not found'; ?></p>
            <p><strong>Current Directory:</strong> <?= __DIR__; ?></p>
            <p><strong>User Agent:</strong> <?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'); ?></p>
            <p><strong>Is Mobile:</strong> <?= isMobile() ? 'YES' : 'NO'; ?></p>
        </div>

        <!-- File Status -->
        <div class="file-status <?= $file_exists ? 'status-ok' : 'status-error'; ?>">
            <?php if ($file_exists): ?>
                <h4 style="color: #28a745;">✅ File PDF Ditemukan</h4>
                <p>File: <strong><?= htmlspecialchars($row['file_buku']); ?></strong></p>
                <p>Ukuran: <strong><?= number_format($file_size / 1024, 2); ?> KB</strong></p>
            <?php else: ?>
                <h4 style="color: #dc3545;">❌ File PDF Tidak Ditemukan</h4>
                <p>File yang dicari: <strong><?= htmlspecialchars($row['file_buku'] ?? 'NULL'); ?></strong></p>
                <p>Path lengkap: <strong><?= htmlspecialchars($file_path); ?></strong></p>
            <?php endif; ?>
            <button onclick="toggleDebug()" class="btn btn-warning" style="margin-top: 10px;">
                🔍 Toggle Debug Info
            </button>
        </div>

        <div class="controls">
            <div>
                <a href="homepage.php" class="btn btn-secondary">← Kembali ke Beranda</a>
            </div>
            <div>
                <?php if (!isMobile()): ?>
                    <button onclick="toggleFullscreen()" class="btn" id="fullscreenBtn">📖 Mode Fullscreen</button>
                <?php endif; ?>
                
                <?php if ($file_exists): ?>
                    <button onclick="downloadPDF()" class="btn">📥 Download</button>
                    <button onclick="testURL()" class="btn btn-warning">🔗 Test URL</button>
                    <a href="<?= htmlspecialchars($absolute_url); ?>" target="_blank" class="btn btn-success" id="openTabBtn">
                        🔗 Buka di Tab Baru
                    </a>
                <?php else: ?>
                    <button class="btn btn-danger" disabled>❌ File Tidak Tersedia</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($file_exists): ?>
            <?php if (isMobile()): ?>
                <!-- Mobile Warning dan Options -->
                <div class="mobile-warning">
                    <h3>📱 Tampilan Mobile Terdeteksi</h3>
                    <p>Pilih cara terbaik untuk membuka PDF:</p>
                    <div class="mobile-options">
                        <a href="<?= htmlspecialchars($absolute_url); ?>" target="_blank" class="btn btn-success">
                            🔗 Buka PDF di Tab Baru
                        </a>
                        <button onclick="downloadPDF()" class="btn">
                            📥 Download PDF
                        </button>
                        <button onclick="openInBrowser()" class="btn btn-warning">
                            🌐 Buka dengan Browser Default
                        </button>
                        <button onclick="showMobileViewer()" class="btn" id="showViewerBtn">
                            👁️ Coba Tampilkan di Sini
                        </button>
                    </div>
                    
                    <!-- URL Test Area -->
                    <div class="url-test">
                        <strong>URL yang akan diakses:</strong><br>
                        <span id="urlDisplay"><?= htmlspecialchars($absolute_url); ?></span>
                        <br><button onclick="copyURL()" class="btn" style="margin-top: 5px; padding: 5px 10px; min-width: auto;">📋 Copy URL</button>
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
            <?php else: ?>
                <!-- Desktop View -->
                <div class="pdf-container" id="pdfContainer">
                    <div class="loading" id="loadingIndicator">
                        <div class="spinner"></div>
                        <p>Memuat PDF...</p>
                    </div>
                    <iframe 
                        src="<?= htmlspecialchars($absolute_url); ?>#toolbar=1&navpanes=1&scrollbar=1&page=1&view=FitH" 
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
                <p>File PDF untuk buku "<strong><?= htmlspecialchars($row['judul']); ?></strong>" tidak ditemukan di server.</p>
                <p>Path yang dicari: <code><?= htmlspecialchars($file_path); ?></code></p>
                <a href="homepage.php" class="btn" style="margin-top: 15px;">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let isFullscreen = false;
        let isMobileDevice = <?= isMobile() ? 'true' : 'false'; ?>;
        const absoluteURL = '<?= htmlspecialchars($absolute_url); ?>';
        const fileName = '<?= htmlspecialchars($row['file_buku'] ?? ''); ?>';

        function toggleDebug() {
            const debugInfo = document.getElementById('debugInfo');
            debugInfo.classList.toggle('show');
        }

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
            if (!fileName) {
                alert('File tidak tersedia untuk didownload');
                return;
            }
            
            const link = document.createElement('a');
            link.href = absoluteURL;
            link.download = '<?= htmlspecialchars($row['judul']); ?>.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function testURL() {
            fetch(absoluteURL, { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        alert('✅ URL dapat diakses!\nStatus: ' + response.status + '\nSize: ' + (response.headers.get('content-length') || 'Unknown'));
                    } else {
                        alert('❌ URL tidak dapat diakses!\nStatus: ' + response.status + ' - ' + response.statusText);
                    }
                })
                .catch(error => {
                    alert('❌ Error mengakses URL:\n' + error.message);
                });
        }

        function copyURL() {
            navigator.clipboard.writeText(absoluteURL).then(() => {
                alert('URL berhasil di-copy ke clipboard!');
            }).catch(() => {
                // Fallback untuk browser yang tidak support clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = absoluteURL;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('URL berhasil di-copy ke clipboard!');
            });
        }

        function openInBrowser() {
            // Untuk mobile, coba buka dengan intent atau scheme
            if (isMobileDevice) {
                // Coba beberapa cara untuk membuka di browser default
                const schemes = [
                    'googlechrome://' + absoluteURL,
                    'firefox://' + absoluteURL,
                    absoluteURL
                ];
                
                let tried = 0;
                function tryNext() {
                    if (tried < schemes.length) {
                        window.open(schemes[tried], '_blank');
                        tried++;
                        setTimeout(tryNext, 1000);
                    }
                }
                tryNext();
            } else {
                window.open(absoluteURL, '_blank');
            }
        }

        function showMobileViewer() {
            const container = document.getElementById('pdfContainer');
            const viewer = document.getElementById('pdfViewer');
            const btn = document.getElementById('showViewerBtn');
            
            container.style.display = 'block';
            viewer.src = absoluteURL;
            
            btn.innerHTML = '🔄 Refresh Viewer';
            btn.onclick = function() {
                viewer.src = absoluteURL + '?t=' + new Date().getTime();
            };
        }

        function hideLoading() {
            const loading = document.getElementById('loadingIndicator');
            if (loading) loading.style.display = 'none';
        }

        function showError() {
            const loading = document.getElementById('loadingIndicator');
            if (loading) {
                loading.innerHTML = `
                    <div style="color: #dc3545;">
                        <h3>❌ Gagal memuat PDF</h3>
                        <p>File PDF tidak dapat ditampilkan di browser ini.</p>
                        <button onclick="downloadPDF()" class="btn" style="margin: 10px;">📥 Download</button>
                        <button onclick="testURL()" class="btn btn-warning" style="margin: 10px;">🔗 Test URL</button>
                    </div>
                `;
            }
        }

        function showMobileError() {
            const container = document.getElementById('pdfContainer');
            if (container) {
                container.innerHTML = `
                    <div style="padding: 40px; text-align: center;">
                        <h3 style="color: #dc3545;">❌ PDF Tidak Dapat Ditampilkan</h3>
                        <p>Browser mobile Anda tidak mendukung tampilan PDF.</p>
                        <button onclick="downloadPDF()" class="btn" style="margin: 10px;">📥 Download PDF</button>
                        <a href="${absoluteURL}" target="_blank" class="btn btn-success" style="margin: 10px;">🔗 Coba Buka Lagi</a>
                    </div>
                `;
            }
        }

        // Auto-hide loading
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

        // Test URL on page load for mobile
        if (isMobileDevice && fileName) {
            setTimeout(testURL, 2000);
        }
    </script>
</body>
</html>