<?php
session_start();
require 'db.php';

// Kullanıcı giriş yaptı mı?
if (!isset($_SESSION['user_id'])) {
    die("Yetkisiz erişim.");
}

// Kullanıcı firma admin mi?
if ($_SESSION['role'] !== 'firma_admin') {
    die("Bu işlem için yetkiniz yok.");
}

// Sadece POST istekler
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek metodu.");
}


// Giriş yapmış yetkilinin firma ID'sini al
$firma_id = $_SESSION['firm_id'];

// Hangi işlemin yapılacağını URL'den al
$islem = $_GET['islem'] ?? '';



switch ($islem) {

    case 'sefer_ekle':
        // Form verilerini al
        $kalkis = trim($_POST['kalkis']);
        $varis = trim($_POST['varis']);
        $tarih = $_POST['tarih'];
        $saat = $_POST['saat'];
        $fiyat = $_POST['fiyat'];
        $koltuk_sayisi = $_POST['koltuk_sayisi'];

        
        if (empty($kalkis) || empty($varis) || empty($tarih) || empty($saat) || !is_numeric($fiyat) || !is_numeric($koltuk_sayisi)) {
            $_SESSION['error_message'] = "Tüm alanlar doğru bir şekilde doldurulmalıdır.";
            header('Location: firma_panel.php?sayfa=seferler');
            exit();
        }

        try {
            
            $sql = "INSERT INTO trips (firm_id, kalkis, varis, tarih, saat, fiyat, koltuk_sayisi) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$firma_id, $kalkis, $varis, $tarih, $saat, $fiyat, $koltuk_sayisi]);
            $_SESSION['success_message'] = "Yeni sefer başarıyla eklendi.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Hata: " . $e->getMessage();
        }
        header('Location: firma_panel.php?sayfa=seferler');
        exit();
        break;

    case 'sefer_sil':
        $sefer_id = $_POST['sefer_id'] ?? null;
        if (empty($sefer_id)) {
            $_SESSION['error_message'] = "Geçersiz sefer ID'si.";
            header('Location: firma_panel.php?sayfa=seferler');
            exit();
        }
        try {
            // Sadece sefer_id'ye göre değil, hem sefer_id hem de o anki yetkilinin firma_id'sine göre sil.
            $sql = "DELETE FROM trips WHERE id = ? AND firm_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$sefer_id, $firma_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Sefer başarıyla silindi.";
            } else {
                $_SESSION['error_message'] = "İşlem başarısız oldu veya bu seferi silme yetkiniz yok.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Hata: " . $e->getMessage();
        }
        header('Location: firma_panel.php?sayfa=seferler');
        exit();
        break;

    case 'sefer_guncelle':
        
        $trip_id = $_POST['trip_id'] ?? null;
        $kalkis = trim($_POST['kalkis']);
        $varis = trim($_POST['varis']);
        $tarih = $_POST['tarih'];
        $saat = $_POST['saat'];
        $fiyat = $_POST['fiyat'];
        $koltuk_sayisi = $_POST['koltuk_sayisi'];

        
        if (empty($trip_id) || empty($kalkis) || empty($varis) || empty($tarih) || empty($saat) || !is_numeric($fiyat) || !is_numeric($koltuk_sayisi)) {
            $_SESSION['error_message'] = "Tüm alanlar doldurulmalıdır.";
            header('Location: edit_trip.php?id=' . $trip_id); 
            exit();
        }

        try {
            
            $sql = "UPDATE trips SET 
                        kalkis = ?, 
                        varis = ?, 
                        tarih = ?, 
                        saat = ?, 
                        fiyat = ?, 
                        koltuk_sayisi = ? 
                    WHERE id = ? AND firm_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $kalkis, 
                $varis, 
                $tarih, 
                $saat, 
                $fiyat, 
                $koltuk_sayisi,
                $trip_id,
                $firma_id 
            ]);

            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Sefer başarıyla güncellendi.";
            } else {
                $_SESSION['error_message'] = "Güncelleme yapılamadı (veya değişiklik yapmadınız).";
            }

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Hata: " . $e->getMessage();
            header('Location: edit_trip.php?id=' . $trip_id); 
            exit();
        }
        
        
        header('Location: firma_panel.php?sayfa=seferler');
        exit();
        break;

    case 'kupon_ekle':
        $kod = trim(strtoupper($_POST['kod']));
        $oran = $_POST['oran'] ?? null;
        $limit = $_POST['kullanim_limit'] ?? null;
        $son_tarih = $_POST['son_tarih'];

        if (empty($kod) || !is_numeric($oran) || !is_numeric($limit) || empty($son_tarih)) {
            $_SESSION['error_message'] = "Tüm alanlar doğru doldurulmalıdır.";
            header('Location: firma_panel.php?sayfa=kuponlar');
            exit();
        }

        try {
            // Yeni kuponu, o an giriş yapmış yetkilinin firm_id'si ile birlikte ekle
            $sql = "INSERT INTO coupons (kod, oran, kullanim_limit, son_tarih, firm_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$kod, $oran, $limit, $son_tarih, $firma_id]);
            $_SESSION['success_message'] = "Kupon başarıyla oluşturuldu.";
        } catch (PDOException $e) {
            
            if ($e->getCode() == 23000) {
                 $_SESSION['error_message'] = "Bu kupon kodu zaten mevcut. Lütfen başka bir kod girin.";
            } else {
                 $_SESSION['error_message'] = "Hata: " . $e->getMessage();
            }
        }
        header('Location: firma_panel.php?sayfa=kuponlar');
        exit();
        break;

    case 'kupon_sil':
        $kupon_id = $_POST['kupon_id'] ?? null;
        if (empty($kupon_id)) {
            $_SESSION['error_message'] = "Geçersiz kupon ID'si.";
            header('Location: firma_panel.php?sayfa=kuponlar');
            exit();
        }
        try {
            // Kuponu silerken, hem kupon ID'sinin hem de firma ID'sinin eşleştiğinden emin ol.
            $sql = "DELETE FROM coupons WHERE id = ? AND firm_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$kupon_id, $firma_id]);
            
            if ($stmt->rowCount() > 0) {
                 $_SESSION['success_message'] = "Kupon başarıyla silindi.";
            } else {
                 $_SESSION['error_message'] = "İşlem başarısız veya bu kuponu silme yetkiniz yok.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Hata: " . $e->getMessage();
        }
        header('Location: firma_panel.php?sayfa=kuponlar');
        exit();
        break;

    default:
        die("Geçersiz işlem.");
        break;
}
?>