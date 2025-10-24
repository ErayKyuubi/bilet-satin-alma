<?php
session_start();
require 'db.php'; 


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    
    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Tüm alanlar zorunludur.";
        header("Location: register.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Şifreler eşleşmiyor.";
        header("Location: register.php");
        exit();
    }
    
    // Kullanıcı adı mevcut mu kontrol
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = "Bu kullanıcı adı zaten alınmış.";
            header("Location: register.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Veritabanı hatası: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }

    // Şifre hashleme işlemi
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    
    try {
       
        $sql = "INSERT INTO users (username, password, role, kredi) VALUES (?, ?, 'user', 100.0)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $hashed_password]);

        $_SESSION['success_message'] = "Kayıt başarıyla tamamlandı. Lütfen giriş yapın.";
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }

} else {
    
    header("Location: index.php");
    exit();
}
?>