<?php

echo "<h1>LogFinder Pro - Database Test</h1>";

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'logfinderss');

try {
    echo "<h2>1. MySQL Bağlantısı</h2>";
    $db = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL bağlantısı başarılı<br>";
    
    echo "<h2>2. Database Kontrolü</h2>";
    $databases = $db->query("SHOW DATABASES LIKE 'logfinderss'")->fetchAll();
    if (count($databases) > 0) {
        echo "✅ Database 'logfinderss' mevcut<br>";
        
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<h2>3. Tablo Kontrolü</h2>";
        $tables = $db->query("SHOW TABLES LIKE 'users'")->fetchAll();
        if (count($tables) > 0) {
            echo "✅ 'users' tablosu mevcut<br>";
            
            echo "<h2>4. Kullanıcı Kontrolü</h2>";
            $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "📊 Toplam kullanıcı: " . $userCount . "<br>";
            
            if ($userCount > 0) {
                echo "<h3>Mevcut Kullanıcılar:</h3>";
                $users = $db->query("SELECT username, email, is_admin, created_at FROM users")->fetchAll(PDO::FETCH_ASSOC);
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>Kullanıcı Adı</th><th>Email</th><th>Admin</th><th>Oluşturma Tarihi</th></tr>";
                foreach ($users as $user) {
                    $adminStatus = $user['is_admin'] ? '✅ Admin' : '👤 Kullanıcı';
                    echo "<tr>";
                    echo "<td>{$user['username']}</td>";
                    echo "<td>{$user['email']}</td>";
                    echo "<td>{$adminStatus}</td>";
                    echo "<td>{$user['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h2>5. Şifre Hash Testi</h2>";
                $demoUser = $db->query("SELECT password FROM users WHERE username = 'demo'")->fetch();
                if ($demoUser) {
                    $testPassword = 'demo123';
                    if (password_verify($testPassword, $demoUser['password'])) {
                        echo "✅ Demo kullanıcı şifresi doğru (demo123)<br>";
                    } else {
                        echo "❌ Demo kullanıcı şifresi hatalı<br>";
                    }
                }
                
                echo "<h2>✅ KURULUM BAŞARILI!</h2>";
                echo "<p><strong>Giriş yapmak için:</strong></p>";
                echo "<ul>";
                echo "<li>Ana sayfa: <a href='index.php'>http://localhost/index.php</a></li>";
                echo "<li>Admin panel: <a href='admin/'>http://localhost/admin/</a></li>";
                echo "<li>Kullanıcı: <code>demo</code> | Şifre: <code>demo123</code></li>";
                echo "</ul>";
                
            } else {
                echo "⚠️ Kullanıcı bulunamadı. SQL dosyasını tekrar çalıştırın.<br>";
            }
            
        } else {
            echo "❌ 'users' tablosu bulunamadı<br>";
        }
        
    } else {
        echo "❌ Database 'logfinderss' bulunamadı<br>";
        echo "<p>👉 <strong>install.sql</strong> dosyasını phpMyAdmin'de çalıştırın.</p>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database hatası: " . $e->getMessage() . "<br>";
    echo "<p>XAMPP'nin çalıştığından ve MySQL'in aktif olduğundan emin olun.</p>";
}

echo "<hr>";
echo "<p><small>Test dosyası: " . __FILE__ . "</small></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
code { background-color: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
