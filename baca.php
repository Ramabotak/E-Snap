<?php
include 'koneksi.php'; // pastikan ini file koneksi database kamu

// Mulai session jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Validasi dan ambil ID buku dari URL
if (!isset($_GET['id_buku']) || empty($_GET['id_buku'])) {
    die("ID buku tidak valid");
}

$id_buku = mysqli_real_escape_string($conn, $_GET['id_buku']);

// Query untuk mengambil data buku
$query = "SELECT * FROM buku WHERE id_buku = '$id_buku'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Buku tidak ditemukan");
}

$row = mysqli_fetch_assoc($result);

// Cek apakah user sudah login
if (isset($_SESSION['id_user'])) {
    $id_user = mysqli_real_escape_string($conn, $_SESSION['id_user']);

    // Simpan ke history jika belum ada entri sebelumnya
    $cek = mysqli_query($conn, "SELECT * FROM history WHERE id_user='$id_user' AND id_buku='$id_buku'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO history (id_user, id_buku, waktu) VALUES ('$id_user', '$id_buku', NOW())");
    } else {
        // Update waktu jika sudah pernah dilihat
        mysqli_query($conn, "UPDATE history SET waktu=NOW() WHERE id_user='$id_user' AND id_buku='$id_buku'");
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
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s ease;
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

        .fullscreen-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 10;
        }

        .fullscreen-btn:hover {
            background: rgba(0,0,0,0.9);
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

        /* Responsive */
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
            
            .pdf-viewer {
                height: 70vh;
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
                <button onclick="toggleFullscreen()" class="btn" id="fullscreenBtn">📖 Mode Fullscreen</button>
                <button onclick="downloadPDF()" class="btn">📥 Download</button>
            </div>
        </div>

        <?php if (!empty($row['file_buku'])): ?>
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

        function hideLoading() {
            document.getElementById('loadingIndicator').style.display = 'none';
        }

        function showError() {
            const loading = document.getElementById('loadingIndicator');
            loading.innerHTML = `
                <div style="color: #dc3545;">
                    <h3>❌ Gagal memuat PDF</h3>
                    <p>File PDF tidak dapat ditampilkan. Silakan coba download file atau hubungi administrator.</p>
                </div>
            `;
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F11' || (e.key === 'f' && e.ctrlKey)) {
                e.preventDefault();
                toggleFullscreen();
            }
            if (e.key === 'Escape' && isFullscreen) {
                toggleFullscreen();
            }
        });

        // Auto-hide loading after 10 seconds
        setTimeout(function() {
            const loading = document.getElementById('loadingIndicator');
            if (loading.style.display !== 'none') {
                loading.style.display = 'none';
            }
        }, 10000);
    </script>
</body>
</html>