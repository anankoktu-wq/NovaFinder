<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

$systemInfo = [
    'php_version' => phpversion(),
    'mysql_version' => $db->query('SELECT VERSION()')->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

$current_page = 'settings';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları - LogFinder Pro</title>
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
                    <i class="fas fa-cog"></i>
                    Sistem Ayarları
                </h1>
                <div class="user-info">
                    <span>LogFinder Pro v1.0</span>
                </div>
            </div>
            
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-server"></i>
                            Sistem Bilgileri
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <td><strong>PHP Sürümü</strong></td>
                                        <td>
                                            <?php echo $systemInfo['php_version']; ?>
                                            <span class="badge <?php echo version_compare($systemInfo['php_version'], '7.4.0', '>=') ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo version_compare($systemInfo['php_version'], '7.4.0', '>=') ? 'Uyumlu' : 'Güncelle'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>MySQL Sürümü</strong></td>
                                        <td><?php echo $systemInfo['mysql_version']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Web Sunucusu</strong></td>
                                        <td><?php echo $systemInfo['server_software']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Bellek Limiti</strong></td>
                                        <td><?php echo $systemInfo['memory_limit']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Maksimum Çalışma Süresi</strong></td>
                                        <td><?php echo $systemInfo['max_execution_time']; ?> saniye</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Maksimum Upload Boyutu</strong></td>
                                        <td><?php echo $systemInfo['upload_max_filesize']; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-database"></i>
                            Veritabanı Bilgileri
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-title">Veritabanı Adı</div>
                                    <div class="stat-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                </div>
                                <div class="stat-value"><?php echo DB_NAME; ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-title">Sunucu</div>
                                    <div class="stat-icon">
                                        <i class="fas fa-server"></i>
                                    </div>
                                </div>
                                <div class="stat-value"><?php echo DB_HOST; ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-title">Bağlantı Durumu</div>
                                    <div class="stat-icon">
                                        <i class="fas fa-plug"></i>
                                    </div>
                                </div>
                                <div class="stat-value">
                                    <span class="badge badge-success">Bağlı</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-shield-alt"></i>
                            Güvenlik Kontrolleri
                        </h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; gap: 1rem;">
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Uyarı:</strong> Demo hesabı varsayılan şifresi ile aktif. Güvenlik için şifreyi değiştirin.
                            </div>
                            <div class="alert <?php echo file_exists('../logfindersss.sql') ? 'alert-error' : 'alert-success'; ?>">
                                <i class="fas <?php echo file_exists('../logfindersss.sql') ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                                <?php if (file_exists('../logfindersss.sql')): ?>
                                    <strong>Güvenlik Riski:</strong> SQL dosyası hala sunucuda. Kurulum sonrası silin.
                                <?php else: ?>
                                    <strong>Güvenli:</strong> SQL kurulum dosyası bulunamadı.
                                <?php endif; ?>
                            </div>
                            <div class="alert <?php echo is_writable('../config/database.php') ? 'alert-error' : 'alert-success'; ?>">
                                <i class="fas <?php echo is_writable('../config/database.php') ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                                <?php if (is_writable('../config/database.php')): ?>
                                    <strong>Güvenlik Riski:</strong> Veritabanı yapılandırma dosyası yazılabilir durumda.
                                <?php else: ?>
                                    <strong>Güvenli:</strong> Veritabanı yapılandırması korunuyor.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-tools"></i>
                            Hızlı İşlemler
                        </h2>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="users.php" class="btn btn-primary">
                                <i class="fas fa-users-cog"></i>
                                Kullanıcı Yönetimi
                            </a>
                            <a href="logs.php" class="btn btn-primary">
                                <i class="fas fa-file-alt"></i>
                                Log Dosyaları
                            </a>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                            <a href="../index.php" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                Ana Sayfayı Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
