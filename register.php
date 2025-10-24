<?php
session_start();

if (isset($_SESSION['user_id'])) {
    
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: admin_panel.php');
            exit();
        } elseif ($_SESSION['role'] === 'firma_admin') {
            header('Location: firma_panel.php');
            exit();
        }
    }
    
    header('Location: index.php'); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - ErayBilet Platformu</title>
    
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-layout">

    <a href="index.php" class="home-button">&larr; Anasayfa</a>

    <div class="auth-container">
        <h2 style="text-align: center;">Yeni Hesap Oluştur</h2>

        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <form action="handle_register.php" method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
             <div class="form-group">
                <label for="confirm_password">Şifre Tekrar:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
             </div>
            <button type="submit" class="btn btn-primary btn-block">Kayıt Ol</button>
        </form>
        
        <p class="auth-switch-link">
            Zaten bir hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
    </div>

</body>
</html>