<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

try {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $totalAdmins = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $newUsers = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $weeklyUsers = $stmt->fetchColumn();

    $stmt = $db->query("SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LogFinder Pro</title>
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
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </h1>
                <div class="user-info">
                    <span>Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <span>|</span>
                    <span><?php echo date('d.m.Y H:i'); ?></span>
                </div>
            </div>
            
            <div class="content-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Toplam Kullanıcı</div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalUsers ?? 0); ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span><?php echo $newUsers ?? 0; ?> yeni (24s)</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Admin Kullanıcı</div>
                            <div class="stat-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalAdmins ?? 0); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-check"></i>
                            <span>Aktif</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Haftalık Kayıt</div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($weeklyUsers ?? 0); ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-trending-up"></i>
                            <span>Son 7 gün</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Sistem Durumu</div>
                            <div class="stat-icon">
                                <i class="fas fa-server"></i>
                            </div>
                        </div>
                        <div class="stat-value">
                            <span class="badge badge-success">Çevrimiçi</span>
                        </div>
                        <div class="stat-change positive">
                            <i class="fas fa-check-circle"></i>
                            <span>Tüm servisler aktif</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-history"></i>
                            Son Kullanıcı Aktiviteleri
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentActivities)): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı Adı</th>
                                            <th>Email</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['email'] ?? 'Belirtilmemiş'); ?></td>
                                                <td>
                                                    <?php 
                                                    $date = new DateTime($activity['created_at']);
                                                    echo $date->format('d.m.Y H:i'); 
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success">Yeni Kayıt</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Henüz aktivite bulunmuyor.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-bolt"></i>
                                Hızlı İşlemler
                            </h2>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <a href="users.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i>
                                    Yeni Kullanıcı Ekle
                                </a>
                                <a href="logs.php" class="btn btn-primary">
                                    <i class="fas fa-file-alt"></i>
                                    Log Dosyalarını Yönet
                                </a>
                                <a href="../index.php" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-search"></i>
                                    Log Araması Yap
                                </a>
                                <a href="settings.php" class="btn btn-primary">
                                    <i class="fas fa-cogs"></i>
                                    Sistem Ayarları
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
