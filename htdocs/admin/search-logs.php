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

$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$searchFilter = $_GET['search_filter'] ?? '';
$userFilter = $_GET['user_filter'] ?? '';
$dateFilter = $_GET['date_filter'] ?? '';

try {
    $whereConditions = [];
    $params = [];
    
    if (!empty($searchFilter)) {
        $whereConditions[] = "search_term LIKE ?";
        $params[] = "%$searchFilter%";
    }
    
    if (!empty($userFilter)) {
        $whereConditions[] = "username LIKE ?";
        $params[] = "%$userFilter%";
    }
    
    if (!empty($dateFilter)) {
        $whereConditions[] = "search_date = ?";
        $params[] = $dateFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $countQuery = "SELECT COUNT(*) FROM search_logs $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    
    $totalPages = ceil($totalRecords / $limit);
    
    $query = "SELECT * FROM search_logs $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $searchLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT DISTINCT username FROM search_logs ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = 'Veriler yüklenirken hata oluştu: ' . $e->getMessage();
    $searchLogs = [];
    $users = [];
    $totalPages = 0;
}

$current_page = 'search-logs';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Geçmişi - LogFinder Pro</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-system.js"></script>
    <style>
        /* Modern Filtre Tasarımı */
        .filters {
            background: var(--white);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .filter-group label {
            font-size: 0.875rem;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group .form-control {
            transition: all 0.3s ease;
            border: 2px solid var(--gray-200);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            background: var(--white);
        }
        
        .filter-group .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
            transform: translateY(-1px);
        }
        
        .filter-group .btn {
            height: 48px;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .filter-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Modern Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            transition: all 0.3s ease;
            background: var(--white);
        }
        
        .pagination a:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .pagination .current {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        /* Geliştirilmiş Tablo */
        .table-container {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: none;
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .table th {
            background: var(--gray-100);
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem;
            color: var(--gray-800);
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .table tr:hover {
            background: var(--gray-25);
            transform: scale(1.005);
            transition: all 0.2s ease;
        }
        
        /* Log Detayları */
        .log-details {
            font-size: 0.8rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Arama Terimi Vurgulama */
        .search-term-highlight {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(168, 85, 247, 0.2));
            padding: 0.3rem 0.6rem;
            border-radius: 0.4rem;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            border: 1px solid rgba(168, 85, 247, 0.3);
            box-shadow: 0 2px 4px rgba(168, 85, 247, 0.1);
            display: inline-block;
        }
        
        /* Sonuç Badge'i */
        .results-badge {
            background: linear-gradient(135deg, var(--success-color), #22c55e);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
            background: linear-gradient(135deg, var(--accent-color), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }
        
        .empty-state p {
            font-size: 1rem;
            margin-bottom: 2rem;
            color: var(--gray-500);
        }
        
        /* Tema Uyumluluğu */
        [data-theme="neon"] .filters {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="neon"] .filter-group .form-control {
            background: rgba(0, 0, 0, 0.6);
            border-color: rgba(168, 85, 247, 0.5);
            color: #ffffff;
        }
        
        [data-theme="neon"] .filter-group .form-control:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.3);
        }
        
        [data-theme="neon"] .table-container {
            box-shadow: 0 0 30px rgba(168, 85, 247, 0.3);
        }
        
        [data-theme="corporate"] .filters {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border: 2px solid rgba(168, 85, 247, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="corporate"] .filter-group .form-control {
            border: 2px solid rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="corporate"] .filter-group .form-control:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.1);
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
                    <a href="log-settings.php" class="nav-link">
                        <i class="fas fa-search-plus"></i>
                        <span>Log Finder Ayarları</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search-logs.php" class="nav-link active">
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
                    <i class="fas fa-history"></i>
                    Arama Geçmişi
                </h1>
                <div class="user-info">
                    <span>Toplam <?php echo number_format($totalRecords ?? 0); ?> arama kaydı</span>
                </div>
            </div>
            
            <div class="content-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="filters">
                    <form method="GET" class="filter-row">
                        <div class="filter-group">
                            <label>Arama Terimi</label>
                            <input type="text" name="search_filter" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Arama terimi girin..." class="form-control">
                        </div>
                        
                        <div class="filter-group">
                            <label>Kullanıcı</label>
                            <select name="user_filter" class="form-control">
                                <option value="">Tüm Kullanıcılar</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $userFilter === $user ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Tarih</label>
                            <input type="date" name="date_filter" value="<?php echo htmlspecialchars($dateFilter); ?>" class="form-control">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Filtrele
                            </button>
                        </div>
                        
                        <div class="filter-group">
                            <a href="search-logs.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Temizle
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-list"></i>
                            Arama Kayıtları
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($searchLogs)): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Arama Terimi</th>
                                            <th>Sonuç Sayısı</th>
                                            <th>Tarih & Saat</th>
                                            <th>IP Adresi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($searchLogs as $log): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                                                    <div class="log-details">ID: <?php echo $log['user_id']; ?></div>
                                                </td>
                                                <td>
                                                    <span class="search-term-highlight"><?php echo htmlspecialchars($log['search_term']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="results-badge"><?php echo number_format($log['results_count']); ?> sonuç</span>
                                                </td>
                                                <td>
                                                    <div><?php echo date('d.m.Y', strtotime($log['search_date'])); ?></div>
                                                    <div class="log-details"><?php echo date('H:i:s', strtotime($log['search_time'])); ?></div>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page-1; ?>&search_filter=<?php echo urlencode($searchFilter); ?>&user_filter=<?php echo urlencode($userFilter); ?>&date_filter=<?php echo urlencode($dateFilter); ?>">
                                            <i class="fas fa-chevron-left"></i> Önceki
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?>&search_filter=<?php echo urlencode($searchFilter); ?>&user_filter=<?php echo urlencode($userFilter); ?>&date_filter=<?php echo urlencode($dateFilter); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page+1; ?>&search_filter=<?php echo urlencode($searchFilter); ?>&user_filter=<?php echo urlencode($userFilter); ?>&date_filter=<?php echo urlencode($dateFilter); ?>">
                                            Sonraki <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h3>Arama kaydı bulunamadı</h3>
                                <p>Henüz kullanıcılar tarafından arama yapılmamış veya arama loglaması kapalı.</p>
                                <a href="log-settings.php" class="btn btn-primary">
                                    <i class="fas fa-cog"></i>
                                    Ayarlara Git
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
