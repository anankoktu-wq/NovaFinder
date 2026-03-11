<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

$logDir = '../loglar';
$logFiles = [];
$totalSize = 0;

if (is_dir($logDir)) {
    $files = glob($logDir . '/*.txt');
    foreach ($files as $file) {
        $size = filesize($file);
        $totalSize += $size;
        $logFiles[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => $size,
            'modified' => filemtime($file),
            'readable' => is_readable($file)
        ];
    }
}

$current_page = 'logs';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Yönetimi - LogFinder Pro</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-system.js"></script>
</head>
<body>
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-shield-halved"></i>
                    <h1>LogFinder Pro</h1>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Kullanıcı Yönetimi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php" class="nav-link <?php echo $current_page === 'logs' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Log Yönetimi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="log-settings.php" class="nav-link <?php echo $current_page === 'log-settings' ? 'active' : ''; ?>">
                        <i class="fas fa-search-plus"></i>
                        <span>Log Finder Ayarları</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search-logs.php" class="nav-link <?php echo $current_page === 'search-logs' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Arama Geçmişi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Sistem Ayarları</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../index.php" class="nav-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?logout=1" class="nav-link" onclick="return confirm('Çıkış yapmak istediğinizden emin misiniz?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Çıkış Yap</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="content-header">
                <h1 class="page-title">
                    <i class="fas fa-file-alt"></i>
                    Log Yönetimi
                </h1>
                <div class="user-info">
                    <span>Toplam <?php echo count($logFiles); ?> dosya</span>
                </div>
            </div>
            
            <div class="content-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Toplam Log Dosyası</div>
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo count($logFiles); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Toplam Boyut</div>
                            <div class="stat-icon">
                                <i class="fas fa-hdd"></i>
                            </div>
                        </div>
                        <div class="stat-value">
                            <?php 
                            if ($totalSize < 1024) {
                                echo $totalSize . ' B';
                            } elseif ($totalSize < 1048576) {
                                echo round($totalSize / 1024, 1) . ' KB';
                            } else {
                                echo round($totalSize / 1048576, 1) . ' MB';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Log Klasörü</div>
                            <div class="stat-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                        </div>
                        <div class="stat-value">
                            <span class="badge <?php echo is_dir($logDir) ? 'badge-success' : 'badge-error'; ?>">
                                <?php echo is_dir($logDir) ? 'Mevcut' : 'Bulunamadı'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-list"></i>
                            Log Dosyaları
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($logFiles)): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Dosya Adı</th>
                                            <th>Boyut</th>
                                            <th>Son Değişiklik</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logFiles as $file): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-file-alt" style="margin-right: 0.5rem; color: var(--accent-color);"></i>
                                                    <strong><?php echo htmlspecialchars($file['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($file['size'] < 1024) {
                                                        echo $file['size'] . ' B';
                                                    } elseif ($file['size'] < 1048576) {
                                                        echo round($file['size'] / 1024, 1) . ' KB';
                                                    } else {
                                                        echo round($file['size'] / 1048576, 1) . ' MB';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo date('d.m.Y H:i', $file['modified']); ?>
                                                </td>
                                                <td>
                                                    <?php if ($file['readable']): ?>
                                                        <span class="badge badge-success">Okunabilir</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-error">Erişim Hatası</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem;">
                                                        <a href="../index.php" class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-search"></i>
                                                            Ara
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; color: var(--gray-600);">
                                <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-400);"></i>
                                <h3>Log dosyası bulunamadı</h3>
                                <p>Log dosyaları <code>/loglar</code> klasöründe .txt formatında olmalıdır.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            Log Dosyaları Hakkında
                        </h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                            <div>
                                <h4 style="color: var(--gray-900); margin-bottom: 1rem;">
                                    <i class="fas fa-folder" style="color: var(--accent-color);"></i>
                                    Log Klasörü
                                </h4>
                                <p>Log dosyaları <code>/loglar</code> klasöründe bulunmalıdır. Desteklenen format: <code>.txt</code></p>
                            </div>
                            <div>
                                <h4 style="color: var(--gray-900); margin-bottom: 1rem;">
                                    <i class="fas fa-search" style="color: var(--accent-color);"></i>
                                    Arama Yapma
                                </h4>
                                <p>Log dosyalarında arama yapmak için ana sayfadaki arama özelliğini kullanabilirsiniz.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
