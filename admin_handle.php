<?php

session_start();
require 'db.php';

// Rol Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    
    die("Bu işlem için yetkiniz yok.");
}

// İşlem türünü al
$islem = $_GET['islem'] ?? '';

// Yalnızca POST istekleri
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek metodu.");
}



switch ($islem) {
    case 'firma_ekle':

        $firma_adi = trim($_POST['firma_adi']);


        if (empty($firma_adi)) {

            $_SESSION['error_message'] = "Firma adı boş bırakılamaz.";
            header('Location: admin_panel.php?sayfa=firmalar');
            exit();
        }

        try {

            $sql = "INSERT INTO firms (name) VALUES (?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$firma_adi]);


            $_SESSION['success_message'] = "Firma başarıyla eklendi.";
            header('Location: admin_panel.php?sayfa=firmalar');
            exit();

        } catch (PDOException $e) {

            $_SESSION['error_message'] = "Veritabanı hatası: " . $e->getMessage();
            header('Location: admin_panel.php?sayfa=firmalar');
            exit();
        }
        break; 

    case 'firma_sil':

        $firma_id = $_POST['firma_id'] ?? null;

        if (empty($firma_id)) {
            $_SESSION['error_message'] = "Geçersiz firma ID'si.";
            header('Location: admin_panel.php?sayfa=firmalar');
            exit();
        }

        try {
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE firm_id = ?");
            $stmt->execute([$firma_id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Bu firmayı silemezsiniz! Önce firmaya ait tüm seferleri silmelisiniz.";
                header('Location: admin_panel.php?sayfa=firmalar');
                exit();
            }

            
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE firm_id = ?");
            $stmt->execute([$firma_id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Bu firmayı silemezsiniz! Önce firmaya atanmış tüm yetkilileri silmeli/değiştirmelisiniz.";
                header('Location: admin_panel.php?sayfa=firmalar');
                exit();
            }

            
            $sql = "DELETE FROM firms WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$firma_id]);
            
            $_SESSION['success_message'] = "Firma başarıyla silindi.";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Silme işlemi sırasında bir hata oluştu: " . $e->getMessage();
        }
        
        header('Location: admin_panel.php?sayfa=firmalar');
        exit();

        break;
        
    case 'sefer_ekle':
        
        $firma_id = $_POST['firma_id'] ?? null;
        $kalkis = trim($_POST['kalkis']);
        $varis = trim($_POST['varis']);
        $tarih = $_POST['tarih'];
        $saat = $_POST['saat'];
        $fiyat = $_POST['fiyat'];
        $koltuk_sayisi = $_POST['koltuk_sayisi'];

        
        if (empty($firma_id) || empty($kalkis) || empty($varis) || empty($tarih) || empty($saat) || !is_numeric($fiyat) || !is_numeric($koltuk_sayisi)) {
            $_SESSION['error_message'] = "Tüm alanlar doğru bir şekilde doldurulmalıdır.";
            header('Location: admin_panel.php?sayfa=seferler');
            exit();
        }

        
        try {
            $sql = "INSERT INTO trips (firm_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$firma_id, $kalkis, $varis, $tarih, $saat, $fiyat, $koltuk_sayisi]);

            $_SESSION['success_message'] = "Yeni sefer başarıyla sisteme eklendi.";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Sefer eklenirken bir veritabanı hatası oluştu: " . $e->getMessage();
        }

        
        header('Location: admin_panel.php?sayfa=seferler');
        exit();

        break;

    case 'sefer_sil':
        
        $sefer_id = $_POST['sefer_id'] ?? null;
        if (empty($sefer_id)) {
            $_SESSION['error_message'] = "Geçersiz sefer ID'si.";
            header('Location: admin_panel.php?sayfa=seferler');
            exit();
        }

        try {
            
            $sql = "DELETE FROM trips WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$sefer_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Sefer başarıyla silindi.";
            } else {
                $_SESSION['error_message'] = "Sefer bulunamadı veya silinemedi.";
            }

        } catch (PDOException $e) {
            
            
            if ($e->getCode() == 23000) { 
                 $_SESSION['error_message'] = "Bu sefere daha önce bilet satıldığı için silinemez. Önce ilişkili biletlerin silinmesi gerekir.";
            } else {
                 $_SESSION['error_message'] = "Hata: " . $e->getMessage();
            }
        }
        
        
        header('Location: admin_panel.php?sayfa=seferler');
        exit();
        break;


    case 'firma_admin_ekle':
        
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $firma_id = $_POST['firma_id'] ?? null;

        
        if (empty($username) || empty($password) || empty($firma_id)) {
            $_SESSION['error_message'] = "Tüm alanların doldurulması zorunludur.";
            header('Location: admin_panel.php?sayfa=kullanicilar');
            exit();
        }

        try {
            
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $_SESSION['error_message'] = "Bu kullanıcı adı zaten alınmış. Lütfen başka bir tane seçin.";
                header('Location: admin_panel.php?sayfa=kullanicilar');
                exit();
            }

            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            
            $sql = "INSERT INTO users (username, password, role, firm_id, kredi) VALUES (?, ?, 'firma_admin', ?, 0)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $hashed_password, $firma_id]);

            $_SESSION['success_message'] = "'". htmlspecialchars($username) . "' adlı firma yetkilisi başarıyla oluşturuldu.";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Kullanıcı oluşturulurken bir veritabanı hatası oluştu: " . $e->getMessage();
        }

        
        header('Location: admin_panel.php?sayfa=kullanicilar');
        exit();
        break;
        
        case 'kupon_ekle':
        
        $kod = trim(strtoupper($_POST['kod']));
        $oran = $_POST['oran'] ?? null;
        $limit = $_POST['kullanim_limit'] ?? null;
        $son_tarih = $_POST['son_tarih'];

        
        if (empty($kod) || !is_numeric($oran) || !is_numeric($limit) || empty($son_tarih)) {
            $_SESSION['error_message'] = "Tüm alanlar doğru bir şekilde doldurulmalıdır.";
            header('Location: admin_panel.php?sayfa=kuponlar');
            exit();
        }

        try {
            
            $stmt = $db->prepare("SELECT id FROM coupons WHERE kod = ?");
            $stmt->execute([$kod]);
            if ($stmt->fetch()) {
                $_SESSION['error_message'] = "Bu kupon kodu zaten mevcut. Lütfen başka bir kod girin.";
                header('Location: admin_panel.php?sayfa=kuponlar');
                exit();
            }

            
            $sql = "INSERT INTO coupons (kod, oran, kullanim_limit, son_tarih) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$kod, $oran, $limit, $son_tarih]);

            $_SESSION['success_message'] = "'". htmlspecialchars($kod) . "' kodlu kupon başarıyla oluşturuldu.";

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Kupon oluşturulurken bir veritabanı hatası oluştu: " . $e->getMessage();
        }

        
        header('Location: admin_panel.php?sayfa=kuponlar');
        exit();
        break;

 
    case 'kupon_sil':
        $kupon_id = $_POST['kupon_id'] ?? null;
        if (empty($kupon_id)) {
            $_SESSION['error_message'] = "Geçersiz kupon ID'si.";
            header('Location: admin_panel.php?sayfa=kuponlar');
            exit();
        }
        try {
            $sql = "DELETE FROM coupons WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$kupon_id]);
            $_SESSION['success_message'] = "Kupon başarıyla silindi.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Kupon silinirken bir hata oluştu: " . $e->getMessage();
        }
        header('Location: admin_panel.php?sayfa=kuponlar');
        exit();
        break;

    case 'kullanici_sil':
        $silinen_id = $_POST['user_id'] ?? null;
        if (empty($silinen_id)) {
            $_SESSION['error_message'] = "Geçersiz kullanıcı ID'si.";
            header('Location: admin_panel.php?sayfa=kullanicilar');
            exit();
        }

        
        if ($silinen_id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "Kendi hesabınızı silemezsiniz.";
            header('Location: admin_panel.php?sayfa=kullanicilar');
            exit();
        }

        try {
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
            $stmt->execute([$silinen_id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Bu kullanıcıya ait bilet bulunduğu için silinemez.";
                header('Location: admin_panel.php?sayfa=kullanicilar');
                exit();
            }

            
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$silinen_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $_SESSION['error_message'] = "Silmek istediğiniz kullanıcı bulunamadı.";
                header('Location: admin_panel.php?sayfa=kullanicilar');
                exit();
            }

            if ($row['role'] === 'admin') {
                $countStmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                if ($countStmt->fetchColumn() <= 1) {
                    $_SESSION['error_message'] = "Sistemde en az bir admin olmalıdır. Son admin silinemez.";
                    header('Location: admin_panel.php?sayfa=kullanicilar');
                    exit();
                }
            }

            
            $del = $db->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$silinen_id]);

            if ($del->rowCount() > 0) {
                $_SESSION['success_message'] = "Kullanıcı başarıyla silindi.";
            } else {
                $_SESSION['error_message'] = "Kullanıcı silinemedi veya zaten silinmiş.";
            }

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Kullanıcı silinirken hata: " . $e->getMessage();
        }

        header('Location: admin_panel.php?sayfa=kullanicilar');
        exit();
        break;

    default:
        // tanımsız işlem
        die("Geçersiz işlem.");
        break;
}

?>