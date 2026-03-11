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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $dailyLimit = (int)($_POST['daily_search_limit'] ?? 10);
        $enableLogging = isset($_POST['enable_search_logging']) ? 1 : 0;
        $maxResults = (int)($_POST['max_results_per_search'] ?? 50);
        
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $stmt->execute(['daily_search_limit', $dailyLimit]);
        $stmt->execute(['enable_search_logging', $enableLogging]);
        $stmt->execute(['max_results_per_search', $maxResults]);
        
        $success = 'Log Finder ayarları başarıyla kaydedildi!';
    } catch (PDOException $e) {
        $error = 'Ayarlar kaydedilirken hata oluştu: ' . $e->getMessage();
    }
}

try {
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('daily_search_limit', 'enable_search_logging', 'max_results_per_search')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $dailyLimit = (int)($settings['daily_search_limit'] ?? 10);
    $enableLogging = (int)($settings['enable_search_logging'] ?? 1);
    $maxResults = (int)($settings['max_results_per_search'] ?? 50);
    
} catch (PDOException $e) {
    $dailyLimit = 10;
    $enableLogging = 1;
    $maxResults = 50;
    $error = 'Ayarlar yüklenirken hata oluştu: ' . $e->getMessage();
}

$current_page = 'log-settings';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Finder Ayarları - LogFinder Pro</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-system.js"></script>
    <style>
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 0.5rem;
            text-align: center;
            border: 1px solid var(--gray-200);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .top-searches {
            background: var(--gray-50);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .search-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .search-item:last-child {
            border-bottom: none;
        }
        
        .search-term {
            font-weight: 500;
            color: var(--gray-800);
        }
        
        .search-count {
            background: var(--accent-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .form-text {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
    </style>
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
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Kullanıcı Yönetimi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logs.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Log Yönetimi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="log-settings.php" class="nav-link active">
                        <i class="fas fa-search-plus"></i>
                        <span>Log Finder Ayarları</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search-logs.php" class="nav-link">
                        <i class="fas fa-history"></i>
                        <span>Arama Geçmişi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
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
                    <i class="fas fa-search-plus"></i>
                    Log Finder Ayarları
                </h1>
                <div class="user-info">
                    <span>Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                </div>
            </div>
            
            <div class="content-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-search"></i>
                            Log Finder Ayarları
                        </h2>
                        <p style="margin-top: 0.5rem; color: var(--gray-600); font-size: 0.9rem;">
                            Kullanıcıların arama limitleri ve sistem ayarları
                        </p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="daily_search_limit">
                                    <i class="fas fa-clock"></i>
                                    Günlük Arama Limiti
                                </label>
                                <input 
                                    type="number" 
                                    id="daily_search_limit" 
                                    name="daily_search_limit" 
                                    value="<?php echo $dailyLimit; ?>"
                                    min="1" 
                                    max="1000"
                                    class="form-control"
                                    required>
                                <small class="form-text">Her kullanıcının günde yapabileceği maksimum arama sayısı</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_results_per_search">
                                    <i class="fas fa-list"></i>
                                    Maksimum Sonuç Sayısı
                                </label>
                                <input 
                                    type="number" 
                                    id="max_results_per_search" 
                                    name="max_results_per_search" 
                                    value="<?php echo $maxResults; ?>"
                                    min="10" 
                                    max="500"
                                    class="form-control"
                                    required>
                                <small class="form-text">Her aramada gösterilecek maksimum sonuç sayısı</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        name="enable_search_logging" 
                                        <?php echo $enableLogging ? 'checked' : ''; ?>>
                                    <i class="fas fa-history"></i>
                                    Arama Loglamasını Aktif Et
                                </label>
                                <small class="form-text">Kullanıcıların arama geçmişi kaydedilir</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="save_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Ayarları Kaydet
                                </button>
                                <a href="search-logs.php" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i>
                                    Arama Loglarını Görüntüle
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Arama İstatistikleri
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $db->query("SELECT COUNT(*) FROM search_logs WHERE search_date = CURDATE()");
                            $todaySearches = $stmt->fetchColumn();
                            
                            $stmt = $db->query("SELECT COUNT(*) FROM search_logs WHERE search_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                            $weekSearches = $stmt->fetchColumn();
                            
                            $stmt = $db->query("SELECT search_term, COUNT(*) as count FROM search_logs WHERE search_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY search_term ORDER BY count DESC LIMIT 5");
                            $topSearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                        } catch (PDOException $e) {
                            $todaySearches = 0;
                            $weekSearches = 0;
                            $topSearches = [];
                        }
                        ?>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($todaySearches ?? 0); ?></div>
                                <div class="stat-label">Bugünkü Aramalar</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($weekSearches ?? 0); ?></div>
                                <div class="stat-label">Bu Haftaki Aramalar</div>
                            </div>
                        </div>
                        
                        <?php if (!empty($topSearches)): ?>
                            <h4 style="margin-top: 2rem; margin-bottom: 1rem;">En Çok Aranan Terimler (Son 7 Gün)</h4>
                            <div class="top-searches">
                                <?php foreach ($topSearches as $search): ?>
                                    <div class="search-item">
                                        <span class="search-term"><?php echo htmlspecialchars($search['search_term']); ?></span>
                                        <span class="search-count"><?php echo $search['count']; ?> kez</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--gray-600); padding: 2rem;">
                                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <p>Henüz arama verisi bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
