<?php
session_start();
require 'db.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['role'] !== 'firma_admin') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

$firma_id = $_SESSION['firm_id'];
$sayfa = $_GET['sayfa'] ?? 'seferler';

try {
    $stmt_firma = $db->prepare("SELECT name FROM firms WHERE id = ?");
    $stmt_firma->execute([$firma_id]);
    $firma_adi = $stmt_firma->fetchColumn();
    if (!$firma_adi) {
        $firma_adi = "Firma Paneli";
    }
} catch (PDOException $e) {
    die("Firma bilgileri alınamadı: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($firma_adi); ?> - Firma Paneli</title>
    
    <link rel="stylesheet" href="style.css">
</head>

<body class="panel-layout">

   <aside class="sidebar">
        <div class="logo"><?php echo htmlspecialchars($firma_adi); ?></div>
        
        <ul class="nav-menu">
            <li><a href="firma_panel.php?sayfa=seferler" class="<?php echo ($sayfa === 'seferler') ? 'active' : ''; ?>">Sefer Yönetimi</a></li>
            <li><a href="firma_panel.php?sayfa=kuponlar" class="<?php echo ($sayfa === 'kuponlar') ? 'active' : ''; ?>">Kupon Yönetimi</a></li>
        </ul>
        
        <a href="logout.php" class="logout-btn">Çıkış Yap</a>
        
    </aside>

    <main class="main-content">
        <?php
        if (isset($_SESSION['success_message'])) { echo '<div class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
        if (isset($_SESSION['error_message'])) { echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
        

        if ($sayfa === 'seferler'):
            require 'iller.php';
            $seferler_stmt = $db->prepare("SELECT * FROM trips WHERE firm_id = ? ORDER BY tarih DESC, saat DESC");
            $seferler_stmt->execute([$firma_id]);
            $seferler = $seferler_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h1>Sefer Yönetimi</h1>
            <section class="card">
                <h2>Yeni Sefer Ekle</h2>
                <form action="handle_firma.php?islem=sefer_ekle" method="POST">
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="kalkis">Kalkış Yeri:</label>
                            <select id="kalkis" name="kalkis" required>
                                <option value="">-- İl Seçin --</option>
                                <?php foreach ($iller as $il): ?>
                                    <option value="<?php echo htmlspecialchars($il); ?>"><?php echo htmlspecialchars($il); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="varis">Varış Yeri:</label>
                            <select id="varis" name="varis" required>
                                <option value="">-- İl Seçin --</option>
                                <?php foreach ($iller as $il): ?>
                                    <option value="<?php echo htmlspecialchars($il); ?>"><?php echo htmlspecialchars($il); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;"><label for="tarih">Tarih:</label><input type="date" id="tarih" name="tarih" required></div>
                        <div class="form-group" style="flex: 1;"><label for="saat">Saat:</label><input type="time" id="saat" name="saat" required></div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;"><label for="fiyat">Bilet Fiyatı (TL):</label><input type="number" id="fiyat" name="fiyat" step="0.01" required></div>
                        <div class="form-group" style="flex: 1;"><label for="koltuk_sayisi">Koltuk Sayısı:</label><input type="number" id="koltuk_sayisi" name="koltuk_sayisi" value="40" required></div>
                    </div>
                    <button type="submit" class="btn btn-success">Seferi Ekle</button>
                </form>
            </section>
            
            <section class="card">
                <h2>Mevcut Seferleriniz</h2>
                <table>
                    <thead><tr><th>Güzergah</th><th>Tarih & Saat</th><th>Fiyat</th><th>İşlemler</th></tr></thead>
                <tbody>
                     <?php if (count($seferler) > 0): foreach ($seferler as $sefer): ?>
                 <tr>
                    <td><?php echo htmlspecialchars($sefer['kalkis']); ?> &rarr; <?php echo htmlspecialchars($sefer['varis']); ?></td>
                    <td><?php echo htmlspecialchars($sefer['tarih']); ?> - <?php echo htmlspecialchars($sefer['saat']); ?></td>
                    <td><?php echo htmlspecialchars($sefer['fiyat']); ?> TL</td>
            
                    <td>
                    <a href="edit_trip.php?id=<?php echo $sefer['id']; ?>" class="btn btn-primary" style="margin-right: 5px;">Düzenle</a>
                
                    <form action="handle_firma.php?islem=sefer_sil" method="POST" onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?');" style="display:inline;">
                        <input type="hidden" name="sefer_id" value="<?php echo $sefer['id']; ?>">
                        <button type="submit" class="btn btn-danger">Sil</button>
                    </form>
                    </td>
                        </tr>
                            <?php endforeach; else: ?>
                         <tr><td colspan="4" style="text-align:center;">Firmanıza ait kayıtlı sefer bulunmuyor.</td></tr>
                    <?php endif; ?>
                </tbody>
                </table>
            </section>

        <?php

        elseif ($sayfa === 'kuponlar'):
            $kuponlar_stmt = $db->prepare("SELECT * FROM coupons WHERE firm_id = ? ORDER BY son_tarih DESC");
            $kuponlar_stmt->execute([$firma_id]);
            $kuponlar = $kuponlar_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h1>Kupon Yönetimi</h1>
            <section class="card">
                <h2>Yeni Kupon Oluştur</h2>
                <form action="handle_firma.php?islem=kupon_ekle" method="POST">
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 2;"><label for="kod">Kupon Kodu:</label><input type="text" id="kod" name="kod" required placeholder="Örn: YAZ2025"></div>
                        <div class="form-group" style="flex: 1;"><label for="oran">İndirim Oranı (%):</label><input type="number" id="oran" name="oran" required placeholder="Örn: 10"></div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;"><label for="kullanim_limit">Kullanım Limiti:</label><input type="number" id="kullanim_limit" name="kullanim_limit" required placeholder="Kaç kez kullanılabilir?"></div>
                        <div class="form-group" style="flex: 1;"><label for="son_tarih">Son Geçerlilik Tarihi:</label><input type="date" id="son_tarih" name="son_tarih" required></div>
                    </div>
                    <button type="submit" class="btn btn-success">Kupon Oluştur</button>
                </form>
            </section>

            <section class="card">
                <h2>Firmanıza Ait Kuponlar</h2>
                <table>
                    <thead><tr><th>Kod</th><th>İndirim Oranı</th><th>Kalan Limit</th><th>Son Tarih</th><th>İşlemler</th></tr></thead>
                    <tbody>
                        <?php if (count($kuponlar) > 0): foreach ($kuponlar as $kupon): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars(strtoupper($kupon['kod'])); ?></strong></td>
                                <td>%<?php echo htmlspecialchars($kupon['oran']); ?></td>
                                <td><?php echo htmlspecialchars($kupon['kullanim_limit']); ?> adet</td>
                                <td><?php echo htmlspecialchars($kupon['son_tarih']); ?></td>
                                <td>
                                    <form action="handle_firma.php?islem=kupon_sil" method="POST" onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');" style="display:inline;">
                                        <input type="hidden" name="kupon_id" value="<?php echo $kupon['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align:center;">Firmanıza ait kayıtlı kupon bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        <?php else: ?>
            <h1>Sayfa Bulunamadı</h1>
        <?php endif; ?>
    </main>

</body>
</html>