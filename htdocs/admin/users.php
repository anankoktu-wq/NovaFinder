<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_user'])) {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            if (empty($username) || empty($password)) {
                $error = 'Kullanıcı adı ve şifre gerekli';
            } else {
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->fetch()) {
                    $error = 'Bu kullanıcı adı zaten kullanılıyor';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword, $is_admin]);
                    $success = 'Kullanıcı başarıyla eklendi';
                }
            }
        }
        
        if (isset($_POST['delete_user'])) {
            $userId = (int)$_POST['user_id'];
            if ($userId !== $_SESSION['user_id']) {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $success = 'Kullanıcı başarıyla silindi';
            } else {
                $error = 'Kendi hesabınızı silemezsiniz';
            }
        }
        
        if (isset($_POST['toggle_admin'])) {
            $userId = (int)$_POST['user_id'];
            if ($userId !== $_SESSION['user_id']) {
                $stmt = $db->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
                $stmt->execute([$userId]);
                $success = 'Kullanıcı yetkisi güncellendi';
            } else {
                $error = 'Kendi yetkinizi değiştiremezsiniz';
            }
        }
        
        if (isset($_POST['change_password'])) {
            $userId = (int)$_POST['user_id'];
            $newPassword = $_POST['new_password'];
            
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                $success = 'Şifre başarıyla güncellendi';
            } else {
                $error = 'Yeni şifre boş olamaz';
            }
        }
        
    } catch (PDOException $e) {
        $error = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
    }
}

try {
    $stmt = $db->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $totalAdmins = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = "Veriler yüklenirken hata oluştu: " . $e->getMessage();
}

$current_page = 'users';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - LogFinder Pro</title>
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/theme-system.js"></script>
    <style>
        .user-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .user-actions .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        .btn-sm {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
        }
        .btn-danger {
            background: var(--error-color);
            color: white;
        }
        .btn-danger:hover {
            background: #c53030;
        }
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        .btn-warning:hover {
            background: #b7791f;
        }
        .btn-secondary {
            background: var(--gray-500);
            color: white;
        }
        .btn-secondary:hover {
            background: var(--gray-600);
        }
        .form-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray-500);
            cursor: pointer;
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
                    <i class="fas fa-users"></i>
                    Kullanıcı Yönetimi
                </h1>
                <div class="user-info">
                    <button onclick="showAddUserModal()" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Yeni Kullanıcı Ekle
                    </button>
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
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Toplam Kullanıcı</div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalUsers ?? 0); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Admin Kullanıcı</div>
                            <div class="stat-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($totalAdmins ?? 0); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-title">Normal Kullanıcı</div>
                            <div class="stat-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format(($totalUsers ?? 0) - ($totalAdmins ?? 0)); ?></div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-list"></i>
                            Tüm Kullanıcılar
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($users)): ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Kullanıcı Adı</th>
                                            <th>Email</th>
                                            <th>Yetki</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge badge-info">Siz</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email'] ?? 'Belirtilmemiş'); ?></td>
                                                <td>
                                                    <?php if ($user['is_admin']): ?>
                                                        <span class="badge badge-success">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-info">Kullanıcı</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $date = new DateTime($user['created_at']);
                                                    echo $date->format('d.m.Y H:i'); 
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="user-actions">
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yetki değişikliğinden emin misiniz?');">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="toggle_admin" class="btn btn-sm <?php echo $user['is_admin'] ? 'btn-warning' : 'btn-secondary'; ?>">
                                                                    <i class="fas <?php echo $user['is_admin'] ? 'fa-user-minus' : 'fa-user-plus'; ?>"></i>
                                                                    <?php echo $user['is_admin'] ? 'Admin İptal' : 'Admin Yap'; ?>
                                                                </button>
                                                            </form>
                                                            
                                                            <button onclick="showPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-sm btn-secondary">
                                                                <i class="fas fa-key"></i>
                                                                Şifre
                                                            </button>
                                                            
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                    Sil
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="badge badge-info">Kendi Hesabınız</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>Henüz kullanıcı bulunmuyor.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addUserModal" class="form-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yeni Kullanıcı Ekle</h3>
                <button type="button" class="modal-close" onclick="hideModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email (Opsiyonel)</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_admin" value="1">
                        Admin Yetkisi Ver
                    </label>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="hideModal('addUserModal')" class="btn btn-secondary">İptal</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Kullanıcı Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <div id="passwordModal" class="form-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Şifre Değiştir</h3>
                <button type="button" class="modal-close" onclick="hideModal('passwordModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="password_user_id" name="user_id">
                <div class="form-group">
                    <label>Kullanıcı:</label>
                    <p id="password_username" style="font-weight: bold;"></p>
                </div>
                <div class="form-group">
                    <label for="new_password">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="hideModal('passwordModal')" class="btn btn-secondary">İptal</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Şifreyi Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
        }
        
        function showPasswordModal(userId, username) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('password_username').textContent = username;
            document.getElementById('passwordModal').style.display = 'flex';
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('form-modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
