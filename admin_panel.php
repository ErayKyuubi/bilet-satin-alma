<?php
session_start();
require 'db.php';

// Rol Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$sayfa = $_GET['sayfa'] ?? 'firmalar'; 
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="panel-layout">

    <aside class="sidebar">
        <div class="logo">Admin Paneli</div>
        
        <ul class="nav-menu">
            <li><a href="admin_panel.php?sayfa=firmalar" class="<?php echo ($sayfa === 'firmalar') ? 'active' : ''; ?>">Firma Yönetimi</a></li>
            <li><a href="admin_panel.php?sayfa=seferler" class="<?php echo ($sayfa === 'seferler') ? 'active' : ''; ?>">Sefer Yönetimi</a></li>
            <li><a href="admin_panel.php?sayfa=kullanicilar" class="<?php echo ($sayfa === 'kullanicilar') ? 'active' : ''; ?>">Kullanıcı Yönetimi</a></li>
            <li><a href="admin_panel.php?sayfa=kuponlar" class="<?php echo ($sayfa === 'kuponlar') ? 'active' : ''; ?>">Kupon Yönetimi</a></li>
            </ul>
        
        <a href="logout.php" class="logout-btn">Çıkış Yap</a>
        
    </aside>

    <main class="main-content">

        <?php
        
        if (isset($_SESSION['success_message'])) { echo '<div class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
        if (isset($_SESSION['error_message'])) { echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }

        if ($sayfa === 'firmalar'):
            $firmalar = [];
            try {
                $stmt = $db->query("SELECT * FROM firms ORDER BY name ASC");
                $firmalar = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Veritabanı hatası: " . $e->getMessage();
                
                if (isset($_SESSION['error_message'])) { echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
            }
        ?>
            <h1>Firma Yönetimi</h1>
            <section class="card">
                <h2>Yeni Firma Ekle</h2>
                <form action="admin_handle.php?islem=firma_ekle" method="POST">
                    <div class="form-group">
                        <label for="firma-adi">Firma Adı:</label>
                        <input type="text" id="firma-adi" name="firma_adi" required placeholder="Firma adını girin...">
                    </div>
                    <button type="submit" class="btn btn-success">Ekle</button>
                </form>
            </section>

            <section class="card">
                <h2>Mevcut Firmalar</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Firma Adı</th>
                            <th style="text-align: right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($firmalar) > 0): foreach ($firmalar as $firma): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($firma['id']); ?></td>
                                <td><?php echo htmlspecialchars($firma['name']); ?></td>
                                <td style="text-align: right;">
                                    <form action="admin_handle.php?islem=firma_sil" method="POST" onsubmit="return confirm('Bu firmayı silmek istediğinizden emin misiniz?');" style="display:inline;">
                                        <input type="hidden" name="firma_id" value="<?php echo $firma['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" style="text-align: center;">Sistemde kayıtlı firma bulunmamaktadır.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        <?php
        
        elseif ($sayfa === 'seferler'):
            require 'iller.php'; 
            $firmalar_select = $db->query("SELECT id, name FROM firms ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $seferler = $db->query("
                SELECT trips.*, firms.name as firma_adi
                FROM trips
                JOIN firms ON trips.firm_id = firms.id
                ORDER BY tarih DESC, saat DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h1>Sefer Yönetimi</h1>
            <section class="card">
                <h2>Yeni Sefer Ekle</h2>
                <form action="admin_handle.php?islem=sefer_ekle" method="POST">
                    <div class="form-group">
                        <label for="firma_id">Firma Seçin:</label>
                        <select id="firma_id" name="firma_id" required>
                            <option value="">-- Lütfen bir firma seçin --</option>
                            <?php foreach ($firmalar_select as $firma): ?>
                                <option value="<?php echo $firma['id']; ?>"><?php echo htmlspecialchars($firma['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                         <div class="form-group" style="flex: 1;">
                            <label for="tarih">Tarih:</label>
                            <input type="date" id="tarih" name="tarih" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="saat">Saat:</label>
                            <input type="time" id="saat" name="saat" required>
                        </div>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="fiyat">Bilet Fiyatı (TL):</label>
                            <input type="number" id="fiyat" name="fiyat" step="0.01" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="koltuk_sayisi">Koltuk Sayısı:</label>
                            <input type="number" id="koltuk_sayisi" name="koltuk_sayisi" value="40" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Seferi Ekle</button>
                </form>
            </section>

            <section class="card">
                <h2>Mevcut Seferler</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Firma</th>
                            <th>Güzergah</th>
                            <th>Tarih & Saat</th>
                            <th>Fiyat</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($seferler) > 0): foreach ($seferler as $sefer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sefer['firma_adi']); ?></td>
                                <td><?php echo htmlspecialchars($sefer['kalkis']); ?> &rarr; <?php echo htmlspecialchars($sefer['varis']); ?></td>
                                <td><?php echo htmlspecialchars($sefer['tarih']); ?> - <?php echo htmlspecialchars($sefer['saat']); ?></td>
                                <td><?php echo htmlspecialchars($sefer['fiyat']); ?> TL</td>
                                <td>
                                    <form action="admin_handle.php?islem=sefer_sil" method="POST" onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?');" style="display:inline;">
                                        <input type="hidden" name="sefer_id" value="<?php echo $sefer['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align:center;">Sistemde kayıtlı sefer bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        <?php
        
        elseif ($sayfa === 'kullanicilar'):
            $firmalar_select = $db->query("SELECT id, name FROM firms ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $kullanicilar = $db->query("
                SELECT users.*, firms.name as firma_adi
                FROM users
                LEFT JOIN firms ON users.firm_id = firms.id
                ORDER BY role, username ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h1>Kullanıcı Yönetimi</h1>
            <section class="card">
                <h2>Yeni Firma Yetkilisi Ekle</h2>
                <p style="color: #6c757d;">Bu form ile sisteme yeni bir firma admini ekleyebilir ve onu bir firmaya atayabilirsiniz.</p>
                <form action="admin_handle.php?islem=firma_admin_ekle" method="POST">
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="username">Yetkili Kullanıcı Adı:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="password">Şifre:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="firma_id">Atanacak Firma:</label>
                        <select id="firma_id" name="firma_id" required>
                            <option value="">-- Lütfen bir firma seçin --</option>
                            <?php foreach ($firmalar_select as $firma): ?>
                                <option value="<?php echo $firma['id']; ?>"><?php echo htmlspecialchars($firma['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Firma Yetkilisi Oluştur</button>
                </form>
            </section>

            <section class="card">
                <h2>Tüm Kullanıcılar</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kullanıcı Adı</th>
                            <th>Rol</th>
                            <th>İlişkili Firma</th>
                            <th>Kredi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kullanicilar) > 0): foreach ($kullanicilar as $kullanici): ?>
                            <tr>
                                <td><?php echo $kullanici['id']; ?></td>
                                <td><?php echo htmlspecialchars($kullanici['username']); ?></td>
                                <td><strong><?php echo strtoupper(htmlspecialchars($kullanici['role'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($kullanici['firma_adi'] ?? '---'); ?></td>
                                <td><?php echo number_format($kullanici['kredi'], 2); ?> TL</td>
                                <td>
                                    <?php if ($kullanici['role'] !== 'admin'): ?>
                                        <form action="admin_handle.php?islem=kullanici_sil" method="POST" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Sil</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center;">Sistemde kayıtlı kullanıcı bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        <?php
        
        elseif ($sayfa === 'kuponlar'):
            // Admin bütün kuponları görmeli
             $kuponlar = $db->query("
                SELECT coupons.*, firms.name as firma_adi
                FROM coupons
                LEFT JOIN firms ON coupons.firm_id = firms.id
                ORDER BY coupons.firm_id, coupons.son_tarih DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <h1>Kupon Yönetimi</h1>
            <section class="card">
                <h2>Yeni Global Kupon Oluştur</h2>
                <p style="color: #6c757d;">Buradan oluşturulan kuponlar tüm firmaların seferlerinde geçerli olur.</p>
                <form action="admin_handle.php?islem=kupon_ekle" method="POST">
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 2;">
                            <label for="kod">Kupon Kodu:</label>
                            <input type="text" id="kod" name="kod" required placeholder="Örn: GENEL25">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="oran">İndirim Oranı (%):</label>
                            <input type="number" id="oran" name="oran" required placeholder="Örn: 15">
                        </div>
                    </div>
                     <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="kullanim_limit">Kullanım Limiti:</label>
                            <input type="number" id="kullanim_limit" name="kullanim_limit" required placeholder="Kaç kez kullanılabilir?">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="son_tarih">Son Geçerlilik Tarihi:</label>
                            <input type="date" id="son_tarih" name="son_tarih" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Global Kupon Oluştur</button>
                </form>
            </section>

            <section class="card">
                <h2>Mevcut Kuponlar (Global ve Firmaya Özel)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kod</th>
                            <th>Firma</th>
                            <th>İndirim</th>
                            <th>Limit</th>
                            <th>Son Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kuponlar) > 0): foreach ($kuponlar as $kupon): ?>
                            <tr>
                                <td><?php echo $kupon['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars(strtoupper($kupon['kod'])); ?></strong></td>
                                <td><?php echo htmlspecialchars($kupon['firma_adi'] ?? 'Global'); ?></td>
                                <td>%<?php echo htmlspecialchars($kupon['oran']); ?></td>
                                <td><?php echo htmlspecialchars($kupon['kullanim_limit']); ?></td>
                                <td><?php echo htmlspecialchars($kupon['son_tarih']); ?></td>
                                <td>
                                     <form action="admin_handle.php?islem=kupon_sil" method="POST" onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');" style="display:inline;">
                                        <input type="hidden" name="kupon_id" value="<?php echo $kupon['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" style="text-align:center;">Sistemde kayıtlı kupon bulunmuyor.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        <?php
        
        else:
        ?>
            <h1>Sayfa Bulunamadı</h1>
            <p>Aradığınız yönetim sayfası mevcut değil.</p>
        <?php
        endif;
        ?>
    </main>

</body>
</html>