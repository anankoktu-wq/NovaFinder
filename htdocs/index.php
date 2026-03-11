<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$searchResults = [];
$searchTerm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli';
    } else {
        try {
            $stmt = $db->prepare("SELECT id, username, password, email, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Geçersiz kullanıcı adı veya şifre';
            }
        } catch (PDOException $e) {
            $error = 'Giriş sırasında bir hata oluştu';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $password_confirm = $_POST['reg_password_confirm'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor';
    } else {
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Bu kullanıcı adı zaten kullanılıyor';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");
                $stmt->execute([$username, $email, $hashedPassword]);
                
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['is_admin'] = false;
                
                $success = 'Kayıt başarılı! Hoş geldiniz.';
            }
        } catch (PDOException $e) {
            $error = 'Kayıt sırasında bir hata oluştu';
        }
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && isset($_SESSION['user_id'])) {
    $searchTerm = trim($_POST['search_term'] ?? '');
    
    if (!empty($searchTerm)) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'daily_search_limit'");
            $stmt->execute();
            $dailyLimit = (int)($stmt->fetchColumn() ?: 10);
            
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'enable_search_logging'");
            $stmt->execute();
            $loggingEnabled = (int)($stmt->fetchColumn() ?: 1);
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM search_logs WHERE user_id = ? AND search_date = CURDATE()");
            $stmt->execute([$_SESSION['user_id']]);
            $todaySearchCount = (int)$stmt->fetchColumn();
            
            if ($todaySearchCount >= $dailyLimit) {
                $error = "Günlük arama limitiniz ($dailyLimit) dolmuştur. Yarın tekrar deneyiniz.";
            } else {
                $logDir = 'loglar';
                
                if (is_dir($logDir)) {
                    $logFiles = glob($logDir . '/*.txt');
                    
                    foreach ($logFiles as $logFile) {
                        $fileName = basename($logFile);
                        $fileHandle = fopen($logFile, 'r');
                        
                        if ($fileHandle) {
                            $lineNumber = 0;
                            
                            while (($line = fgets($fileHandle)) !== false) {
                                $lineNumber++;
                                
                                if (stripos($line, $searchTerm) !== false) {
                                    $highlightedLine = preg_replace(
                                        '/(' . preg_quote($searchTerm, '/') . ')/i', 
                                        '<mark>$1</mark>', 
                                        htmlspecialchars($line)
                                    );
                                    
                                    $searchResults[] = [
                                        'file' => $fileName,
                                        'lineNumber' => $lineNumber,
                                        'line' => $highlightedLine,
                                        'originalLine' => trim($line)
                                    ];
                                }
                            }
                            
                            fclose($fileHandle);
                        }
                    }
                }
                
                if ($loggingEnabled) {
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                    $resultsCount = count($searchResults);
                    
                    $stmt = $db->prepare("INSERT INTO search_logs (user_id, username, search_term, results_count, ip_address, user_agent, search_date, search_time) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $_SESSION['username'],
                        $searchTerm,
                        $resultsCount,
                        $ipAddress,
                        $userAgent
                    ]);
                }
                
                $remainingSearches = $dailyLimit - $todaySearchCount - 1;
                if ($remainingSearches > 0) {
                    $success = "Arama tamamlandı. Bugün $remainingSearches arama hakkınız kaldı.";
                } else {
                    $success = "Arama tamamlandı. Bugünkü arama limitiniz dolmuştur.";
                }
            }
        } catch (PDOException $e) {
            $error = 'Arama sırasında bir hata oluştu';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogFinder Pro - Profesyonel Log Arama Sistemi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/theme-system.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
        
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d3748;
            --accent-color: #3182ce;
            --success-color: #38a169;
            --error-color: #e53e3e;
            --warning-color: #d69e2e;
            --info-color: #3182ce;
            --white: #ffffff;
            --gray-50: #f7fafc;
            --gray-100: #edf2f7;
            --gray-200: #e2e8f0;
            --gray-600: #4a5568;
            --gray-700: #2d3748;
            --gray-800: #1a202c;
            --gray-900: #171923;
        }
        
        
        /* Neon Purple Theme */
        [data-theme="neon"] {
            --primary-color: #1a0d2e;
            --secondary-color: #2d1b3d;
            --accent-color: #a855f7;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #c084fc;
            --white: #0f0817;
            --gray-50: #0f0817;
            --gray-100: #1a0d2e;
            --gray-200: #2d1b3d;
            --gray-600: #c084fc;
            --gray-700: #e879f9;
            --gray-800: #f0abfc;
            --gray-900: #fdf4ff;
        }
        
        /* Neon Effects */
        [data-theme="neon"] .header {
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.3);
            border: 1px solid rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="neon"] .main-content {
            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.3);
            border: 1px solid rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="neon"] .auth-form {
            background: rgba(26, 13, 46, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.5);
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.2),
                        inset 0 1px 0 rgba(192, 132, 252, 0.1);
        }
        
        [data-theme="neon"] .auth-form:hover {
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.4),
                        inset 0 1px 0 rgba(192, 132, 252, 0.2);
            border-color: rgba(168, 85, 247, 0.8);
        }
        
        [data-theme="neon"] .btn-primary {
            background: linear-gradient(135deg, #a855f7, #c084fc);
            border: 1px solid rgba(168, 85, 247, 0.5);
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
            text-shadow: 0 0 10px rgba(168, 85, 247, 0.8);
        }
        
        [data-theme="neon"] .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.6);
            transform: translate3d(0, -2px, 0);
        }
        
        [data-theme="neon"] .form-group input {
            background: rgba(26, 13, 46, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            color: var(--gray-800);
        }
        
        [data-theme="neon"] .form-group input:focus {
            border-color: rgba(168, 85, 247, 0.8);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.3);
        }
        
        [data-theme="neon"] .logo h1 {
            background: linear-gradient(135deg, #a855f7, #c084fc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(168, 85, 247, 0.5);
        }
        
        [data-theme="neon"] .logo i {
            color: #a855f7;
            filter: drop-shadow(0 0 15px rgba(168, 85, 247, 0.8));
        }
        
        [data-theme="neon"] .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
        }
        
        [data-theme="neon"] .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
        }
        
        [data-theme="neon"] .search-section {
            background: rgba(26, 13, 46, 0.8);
            border-top: 1px solid rgba(168, 85, 247, 0.3);
        }
        
        [data-theme="neon"] .search-input {
            background: rgba(26, 13, 46, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            color: var(--gray-800);
        }
        
        [data-theme="neon"] .search-input:focus {
            border-color: rgba(168, 85, 247, 0.8);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.3);
        }
        
        [data-theme="neon"] .result-item {
            background: rgba(26, 13, 46, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="neon"] .result-content {
            background: rgba(45, 27, 61, 0.6);
            border-left: 3px solid #a855f7;
            box-shadow: inset 0 0 10px rgba(168, 85, 247, 0.1);
        }
        
        [data-theme="neon"] mark {
            background: rgba(168, 85, 247, 0.3);
            color: #fdf4ff;
            box-shadow: 0 0 10px rgba(168, 85, 247, 0.5);
            border-radius: 0.25rem;
        }
        
        [data-theme="neon"] body {
            background: linear-gradient(135deg, #0f0817 0%, #1a0d2e 50%, #2d1b3d 100%);
        }
        
        /* Corporate White + Neon Purple Theme */
        [data-theme="corporate"] {
            --primary-color: #ffffff;
            --secondary-color: #f8fafc;
            --accent-color: #a855f7;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #c084fc;
            --white: #ffffff;
            --gray-50: #ffffff;
            --gray-100: #f8fafc;
            --gray-200: rgba(168, 85, 247, 0.1);
            --gray-600: #64748b;
            --gray-700: #475569;
            --gray-800: #334155;
            --gray-900: #1e293b;
        }
        
        /* Corporate White Background with Neon Purple Accents */
        [data-theme="corporate"] body {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
        }
        
        [data-theme="corporate"] .header {
            background: #ffffff;
            border: 2px solid #a855f7;
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.15);
            animation: glow 3s ease-in-out infinite;
        }
        
        [data-theme="corporate"] .main-content {
            background: #ffffff;
            border: 2px solid #a855f7;
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.15);
        }
        
        [data-theme="corporate"] .auth-form {
            background: #ffffff;
            border: 2px solid #a855f7;
            box-shadow: 0 8px 32px rgba(168, 85, 247, 0.2),
                        inset 0 1px 0 rgba(168, 85, 247, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        [data-theme="corporate"] .auth-form::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #a855f7, #c084fc, #e879f9, #a855f7);
            background-size: 300% 300%;
            border-radius: 0.75rem;
            z-index: -1;
            animation: gradientShift 4s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        [data-theme="corporate"] .auth-form:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(168, 85, 247, 0.25);
        }
        
        [data-theme="corporate"] .logo i {
            color: #a855f7;
            filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.6));
            animation: pulse 2s ease-in-out infinite;
        }
        
        [data-theme="corporate"] .logo h1 {
            background: linear-gradient(135deg, #a855f7, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        [data-theme="corporate"] .btn-primary {
            background: linear-gradient(135deg, #a855f7, #c084fc);
            border: 2px solid #a855f7;
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
            text-shadow: none;
            position: relative;
        }
        
        [data-theme="corporate"] .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(168, 85, 247, 0.6);
            transform: translateY(-3px);
        }
        
        [data-theme="corporate"] .btn-secondary {
            background: #ffffff;
            border: 2px solid #a855f7;
            color: #a855f7;
        }
        
        [data-theme="corporate"] .btn-secondary:hover {
            background: #a855f7;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
        }
        
        [data-theme="corporate"] .form-group input {
            background: #ffffff;
            border: 2px solid rgba(168, 85, 247, 0.2);
            color: var(--gray-800);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        [data-theme="corporate"] .form-group input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1),
                        0 0 20px rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="corporate"] .form-group label {
            color: var(--gray-700);
            font-weight: 600;
        }
        
        [data-theme="corporate"] .alert-success {
            background: #ffffff;
            border: 2px solid #10b981;
            color: #065f46;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
        }
        
        [data-theme="corporate"] .alert-error {
            background: #ffffff;
            border: 2px solid #ef4444;
            color: #991b1b;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.2);
        }
        
        [data-theme="corporate"] .search-section {
            background: #ffffff;
            border-top: 2px solid rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="corporate"] .search-input {
            background: #ffffff;
            border: 2px solid rgba(168, 85, 247, 0.2);
            color: var(--gray-800);
        }
        
        [data-theme="corporate"] .search-input:focus {
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1),
                        0 0 20px rgba(168, 85, 247, 0.2);
        }
        
        [data-theme="corporate"] .result-item {
            background: #ffffff;
            border: 2px solid rgba(168, 85, 247, 0.15);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.1);
        }
        
        [data-theme="corporate"] .result-content {
            background: rgba(168, 85, 247, 0.02);
            border-left: 4px solid #a855f7;
        }
        
        [data-theme="corporate"] mark {
            background: rgba(168, 85, 247, 0.2);
            color: #581c87;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            box-shadow: 0 0 8px rgba(168, 85, 247, 0.3);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            animation: fadeIn 0.8s ease-out;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Smooth Animations with Hardware Acceleration */
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translate3d(0,0,0);
            }
            to { 
                opacity: 1; 
                transform: translate3d(0,0,0);
            }
        }
        
        @keyframes slideInDown {
            from {
                transform: translate3d(0, -30px, 0);
                opacity: 0;
            }
            to {
                transform: translate3d(0, 0, 0);
                opacity: 1;
            }
        }
        
        @keyframes slideInUp {
            from {
                transform: translate3d(0, 20px, 0);
                opacity: 0;
            }
            to {
                transform: translate3d(0, 0, 0);
                opacity: 1;
            }
        }
        
        @keyframes slideInLeft {
            from {
                transform: translate3d(-20px, 0, 0);
                opacity: 0;
            }
            to {
                transform: translate3d(0, 0, 0);
                opacity: 1;
            }
        }
        
        @keyframes slideInRight {
            from {
                transform: translate3d(20px, 0, 0);
                opacity: 0;
            }
            to {
                transform: translate3d(0, 0, 0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0% { transform: translate3d(0,0,0) scale(1); }
            50% { transform: translate3d(0,0,0) scale(1.02); }
            100% { transform: translate3d(0,0,0) scale(1); }
        }
        
        @keyframes glow {
            0% { box-shadow: 0 0 5px rgba(168, 85, 247, 0.3); }
            50% { box-shadow: 0 0 15px rgba(168, 85, 247, 0.5); }
            100% { box-shadow: 0 0 5px rgba(168, 85, 247, 0.3); }
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
            overflow-x: hidden;
            position: relative;
        }
        
        .header {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid var(--gray-200);
            animation: slideInDown 0.6s ease-out;
            will-change: transform, opacity;
            backface-visibility: hidden;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInLeft 0.6s ease-out 0.2s both;
            will-change: transform, opacity;
            backface-visibility: hidden;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: var(--accent-color);
        }
        
        .logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInRight 0.6s ease-out 0.3s both;
            will-change: transform, opacity;
            backface-visibility: hidden;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
            backface-visibility: hidden;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.5s ease;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #2c5aa0;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--gray-600);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            background: var(--gray-700);
        }
        
        .btn-danger {
            background: var(--error-color);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .main-content {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            animation: slideInUp 0.6s ease-out 0.2s both;
            will-change: transform, opacity;
            backface-visibility: hidden;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
        }
        
        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .auth-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            animation: slideInUp 0.5s ease-out 0.3s both;
            will-change: transform, opacity;
            backface-visibility: hidden;
        }
        
        .auth-form:hover {
            transform: translate3d(0, -3px, 0);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            border-color: var(--accent-color);
        }
        
        .auth-form:nth-child(1) {
            animation-delay: 0.4s;
        }
        
        .auth-form:nth-child(2) {
            animation-delay: 0.6s;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .search-section {
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .search-input-group {
            flex: 1;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .results-container {
            border-top: 1px solid var(--gray-200);
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .results-count {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .result-item {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .result-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        
        .result-content {
            font-family: 'Consolas', 'Monaco', monospace;
            background: var(--gray-50);
            padding: 0.75rem;
            border-radius: 0.25rem;
            border-left: 3px solid var(--accent-color);
            white-space: pre-wrap;
        }
        
        mark {
            background: #fef08a;
            color: #78350f;
            padding: 0.1rem 0.2rem;
            border-radius: 0.25rem;
        }
        
        .welcome-info {
            text-align: center;
            color: var(--gray-600);
            font-size: 1.1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gray-600);
        }
        
        .user-welcome {
            font-weight: 600;
            color: var(--gray-800);
        }
        
        /* Theme Modal Styles */
        .theme-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .theme-modal-content {
            background: var(--white);
            border-radius: 1rem;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            transform: translate3d(0,0,0) scale(0.95);
            transition: transform 0.3s ease;
        }
        
        .theme-modal.show .theme-modal-content {
            transform: translate3d(0,0,0) scale(1);
        }
        
        .theme-modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .theme-modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        
        .theme-modal-subtitle {
            color: var(--gray-600);
        }
        
        .theme-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .theme-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
        }
        
        .theme-option:hover {
            border-color: var(--accent-color);
            transform: translate3d(0, -2px, 0);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .theme-option.active {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, var(--accent-color), var(--success-color));
            color: white;
        }
        
        .theme-option i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        
        .theme-option-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .theme-option-desc {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .theme-modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .theme-toggle-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--accent-color);
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
        }
        
        .theme-toggle-btn:hover {
            transform: translate3d(0,0,0) scale(1.05);
            box-shadow: 0 6px 30px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                max-width: 100%;
                overflow-x: hidden;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
                overflow-x: hidden;
            }
            
            .auth-container {
                grid-template-columns: 1fr;
                gap: 1rem;
                width: 100%;
                max-width: 100%;
            }
            
            .search-form {
                flex-direction: column;
                width: 100%;
            }
            
            .theme-options {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .theme-modal-content {
                width: 95%;
                margin: 0 auto;
                max-width: 350px;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
            
            .logo i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-shield-halved"></i>
                <h1>LogFinder Pro</h1>
            </div>
            
            <div class="user-section">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <span class="user-welcome">
                            <i class="fas fa-user"></i>
                            Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <?php if ($_SESSION['is_admin']): ?>
                            <a href="admin/" class="btn btn-primary">
                                <i class="fas fa-cogs"></i>
                                Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="?logout=1" class="btn btn-secondary" onclick="return confirm('Çıkış yapmak istediğiniz emin misiniz?')">
                            <i class="fas fa-sign-out-alt"></i>
                            Çıkış Yap
                        </a>
                    </div>
                <?php else: ?>
                    <div class="user-info">
                        <span>Profesyonel Log Arama ve Analiz Sistemi</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="main-content">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="content-section">
                    <h2 class="section-title">
                        <i class="fas fa-sign-in-alt"></i>
                        Giriş Yap veya Kayıt Ol
                    </h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="auth-container">
                        <div class="auth-form">
                            <h3 style="margin-bottom: 1.5rem; color: var(--gray-900);">
                                <i class="fas fa-sign-in-alt"></i>
                                Giriş Yap
                            </h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="username">Kullanıcı Adı</label>
                                    <input type="text" id="username" name="username" required 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="password">Şifre</label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Giriş Yap
                                </button>
                            </form>
                            
                        </div>
                        
                        <div class="auth-form">
                            <h3 style="margin-bottom: 1.5rem; color: var(--gray-900);">
                                <i class="fas fa-user-plus"></i>
                                Kayıt Ol
                            </h3>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="reg_username">Kullanıcı Adı</label>
                                    <input type="text" id="reg_username" name="reg_username" required
                                           value="<?php echo isset($_POST['reg_username']) ? htmlspecialchars($_POST['reg_username']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="reg_email">Email (Opsiyonel)</label>
                                    <input type="email" id="reg_email" name="reg_email"
                                           value="<?php echo isset($_POST['reg_email']) ? htmlspecialchars($_POST['reg_email']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="reg_password">Şifre</label>
                                    <input type="password" id="reg_password" name="reg_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="reg_password_confirm">Şifre Tekrar</label>
                                    <input type="password" id="reg_password_confirm" name="reg_password_confirm" required>
                                </div>
                                <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-user-plus"></i>
                                    Kayıt Ol
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="content-section search-section">
                    <h2 class="section-title">
                        <i class="fas fa-search"></i>
                        Log Arama
                    </h2>
                    
                    <form method="POST" class="search-form">
                        <div class="search-input-group">
                            <label for="search_term" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--gray-700);">
                                Arama terimi girin (Sadece alana dını girmeniz yeterlidir.)
                            </label>
                            <input type="text" 
                                   id="search_term" 
                                   name="search_term" 
                                   class="search-input" 
                                   placeholder="Örnek: logfinders.xyz, logsearch.xyz, lightning.com" 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                                   required>
                        </div>
                        <button type="submit" name="search" class="btn btn-primary" style="height: fit-content; padding: 1rem 2rem;">
                            <i class="fas fa-search"></i>
                            Ara
                        </button>
                    </form>
                </div>
                
                <?php if ($searchTerm || !empty($searchResults)): ?>
                    <div class="content-section results-container">
                        <div class="results-header">
                            <h2 class="section-title">
                                <i class="fas fa-list-ul"></i>
                                Arama Sonuçları
                            </h2>
                            <div class="results-count">
                                <?php echo count($searchResults); ?> sonuç bulundu
                                <?php if ($searchTerm): ?>
                                    - "<?php echo htmlspecialchars($searchTerm); ?>" için
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($searchResults)): ?>
                            <?php foreach ($searchResults as $result): ?>
                                <div class="result-item">
                                    <div class="result-header">
                                        <strong><?php echo htmlspecialchars($result['file']); ?></strong> 
                                        - Satır <?php echo $result['lineNumber']; ?>
                                    </div>
                                    <div class="result-content"><?php echo $result['line']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="welcome-info">
                                <i class="fas fa-search" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                                <p>Arama terimi için sonuç bulunamadı.</p>
                                <p>Farklı kelimeler deneyin veya log dosyalarının mevcut olduğundan emin olun.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="content-section">
                        <div class="welcome-info">
                            <i class="fas fa-shield-halved" style="font-size: 4rem; color: var(--accent-color); margin-bottom: 1rem;"></i>
                            <h2 style="color: var(--gray-900); margin-bottom: 1rem;">LogFinder Pro'ya Hoş Geldiniz!</h2>
                            <p>Güçlü ve profesyonel log arama sistemi ile loglarınızda istediğiniz bilgileri kolayca bulun.</p>
                            <p>Yukarıdaki arama kutusuna terim girerek başlayabilirsiniz.</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
</body>
</html>