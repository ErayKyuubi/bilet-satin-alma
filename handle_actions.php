<?php
session_start();
require 'db.php';


if (!isset($_SESSION['user_id'])) {

    die("<h1>Bu işlem için giriş yapmalısınız.</h1>");
}

$user_id = $_SESSION['user_id'];
$islem = $_GET['islem'] ?? '';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek metodu.");
}

switch ($islem) {

    case 'bilet_al':
        
        $db->beginTransaction();

       
        try {
            
            $trip_id = $_POST['trip_id'] ?? null;
            $koltuk_no = $_POST['koltuk_no'] ?? null;
            $kupon_kodu = trim(strtoupper($_POST['kupon_kodu']));

            if (empty($trip_id) || empty($koltuk_no)) {
                throw new Exception("Sefer veya koltuk bilgisi eksik.");
            }

            
            $stmt_sefer = $db->prepare("SELECT fiyat FROM trips WHERE id = ?");
            $stmt_sefer->execute([$trip_id]);
            $sefer = $stmt_sefer->fetch(PDO::FETCH_ASSOC);

            $stmt_kullanici = $db->prepare("SELECT kredi FROM users WHERE id = ?");
            $stmt_kullanici->execute([$user_id]);
            $kullanici = $stmt_kullanici->fetch(PDO::FETCH_ASSOC);

            //Koltuk hala boş mu kontrol
            $stmt_is_occupied = $db->prepare("SELECT id FROM tickets WHERE trip_id = ? AND koltuk_no = ?");
            $stmt_is_occupied->execute([$trip_id, $koltuk_no]);
            if ($stmt_is_occupied->fetch()) {
                throw new Exception("Üzgünüz, seçtiğiniz koltuk siz işlemi tamamlarken başkası tarafından satın alındı.");
            }

            $son_fiyat = $sefer['fiyat'];
            $kupon_id = null; 

            
            if (!empty($kupon_kodu)) {
                $stmt_kupon = $db->prepare("SELECT * FROM coupons WHERE kod = ?");
                $stmt_kupon->execute([$kupon_kodu]);
                $kupon = $stmt_kupon->fetch(PDO::FETCH_ASSOC);

                if ($kupon && $kupon['kullanim_limit'] > 0 && strtotime($kupon['son_tarih']) >= time()) {
                    $son_fiyat = $son_fiyat - ($son_fiyat * ($kupon['oran'] / 100));
                    $kupon_id = $kupon['id'];
                    
                    $db->prepare("UPDATE coupons SET kullanim_limit = kullanim_limit - 1 WHERE id = ?")->execute([$kupon_id]);
                } else {
                    throw new Exception("Geçersiz veya süresi dolmuş kupon kodu.");
                }
            }

            
            if ($kullanici['kredi'] < $son_fiyat) {
                throw new Exception("Yetersiz bakiye.");
            }
            
            
            $sql_insert_ticket = "INSERT INTO tickets (user_id, trip_id, koltuk_no, fiyat, kupon_id) VALUES (?, ?, ?, ?, ?)";
            $db->prepare($sql_insert_ticket)->execute([$user_id, $trip_id, $koltuk_no, $son_fiyat, $kupon_id]);

            $sql_update_kredi = "UPDATE users SET kredi = kredi - ? WHERE id = ?";
            $db->prepare($sql_update_kredi)->execute([$son_fiyat, $user_id]);

            
            $db->commit();
            $_SESSION['success_message'] = "Biletiniz başarıyla satın alındı! Koltuk No: " . $koltuk_no;
            header('Location: profile.php');
            exit();

        
        
        } catch (Exception $e) {
            // Hata durumunda tüm veritabanı işlemlerini geri al
            $db->rollBack();
            $_SESSION['error_message'] = "Bilet alınırken bir hata oluştu: " . $e->getMessage();
            // Kullanıcıyı, hangi seferdeyse oraya geri yönlendir
            header('Location: buy_ticket.php?trip_id=' . ($_POST['trip_id'] ?? $trip_id));
            exit();
        }
        break;

    case 'bilet_iptal':
        $db->beginTransaction();
        try {
            $bilet_id = $_POST['bilet_id'] ?? null;
            if (empty($bilet_id)) {
                throw new Exception("Geçersiz bilet ID'si.");
            }

            $sql = "SELECT tickets.fiyat, trips.tarih, trips.saat 
                    FROM tickets JOIN trips ON tickets.trip_id = trips.id
                    WHERE tickets.id = ? AND tickets.user_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$bilet_id, $user_id]);
            $bilet = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bilet) {
                throw new Exception("İptal edilecek bilet bulunamadı veya bu bileti iptal etme yetkiniz yok.");
            }

            $sefer_timestamp = strtotime($bilet['tarih'] . ' ' . $bilet['saat']);
            if (($sefer_timestamp - time()) <= 3600) {
                throw new Exception("Sefer saatine 1 saatten az kaldığı için bilet iptal edilemez.");
            }

            $iade_edilecek_tutar = $bilet['fiyat'];

            $db->prepare("UPDATE users SET kredi = kredi + ? WHERE id = ?")->execute([$iade_edilecek_tutar, $user_id]);
            $db->prepare("DELETE FROM tickets WHERE id = ?")->execute([$bilet_id]);

            $db->commit();
            $_SESSION['success_message'] = "Biletiniz başarıyla iptal edildi. " . $iade_edilecek_tutar . " TL hesabınıza iade edildi.";

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "İptal işlemi sırasında bir hata oluştu: " . $e->getMessage();
        }
        
        header('Location: profile.php');
        exit();
        break;

    case 'bakiye_yukle':
        $miktar = $_POST['miktar'] ?? 0;
        if ($miktar <= 0) {
            $_SESSION['error_message'] = "Geçersiz tutar.";
        } else {
            $db->prepare("UPDATE users SET kredi = kredi + ? WHERE id = ?")->execute([$miktar, $user_id]);
            $_SESSION['success_message'] = "Bakiye başarıyla yüklendi.";
        }
        header('Location: profile.php');
        exit();

    default:
        die("Geçersiz işlem.");
        break;
}
?>