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
    <title>Giriş Yap - ErayBilet Platformu</title>
    
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-layout">

    <a href="index.php" class="home-button">&larr; Anasayfa</a>

    <div class="auth-container">
        <h2 style="text-align: center;">Giriş Yap</h2>

        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']); 
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']); 
        }
        ?>

        <form action="handle_login.php" method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
        </form>
        
        <p class="auth-switch-link">
            Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
        </p>
    </div>

</body>
</html>