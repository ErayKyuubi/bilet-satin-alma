<?php
session_start();
require 'db.php';

// Gelen istek POST mu?
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    
    $username = $_POST['username'];
    $password = $_POST['password'];

    
    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Kullanıcı adı ve şifre zorunludur.";
        header("Location: login.php");
        exit();
    }

    try {
        
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kullanıcı adı ve şifreyi doğrula
        if ($user && password_verify($password, $user['password'])) {
            
            
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['firm_id'] = $user['firm_id']; 

            // Giriş yapan kullancının rolüne göre yönlendirme
            if ($user['role'] === 'admin') {
                header("Location: admin_panel.php");
            } elseif ($user['role'] === 'firma_admin') {
                header("Location: firma_panel.php");
            } else {
                header("Location: index.php");
            }
            exit(); 

        } else {
            // Hatalı giriş
            $_SESSION['error_message'] = "Kullanıcı adı veya şifre hatalı.";
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Veritabanı hatası: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
} else {
    // Geçersiz isteklerde anasayfaya yönlendir
    header("Location: index.php");
    exit();
}
?>