<?php
session_start();
require 'db.php';
require 'iller.php';


// Yetki Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'firma_admin') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) {
    header('Location: firma_panel.php'); exit();
}

$firma_id = $_SESSION['firm_id'];

try {
    $sql = "SELECT * FROM trips WHERE id = ? AND firm_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$trip_id, $firma_id]);
    $sefer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sefer) {
        $_SESSION['error_message'] = "Düzenlenecek sefer bulunamadı veya yetkiniz yok.";
        header('Location: firma_panel.php?sayfa=seferler');
        exit();
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Düzenle</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <nav class="navbar">
        <a href="firma_panel.php" class="logo">Firma Paneli</a>
        <div class="nav-links">
             <a href="firma_panel.php?sayfa=seferler">Seferlere Geri Dön</a>
             <a href="logout.php">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container-normal">
        <h1>Seferi Düzenle</h1>
        <p><b>Sefer ID:</b> <?php echo htmlspecialchars($sefer['id']); ?></p>

        <section class="card">
            <form action="handle_firma.php?islem=sefer_guncelle" method="POST">
                <input type="hidden" name="trip_id" value="<?php echo $sefer['id']; ?>">

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="kalkis">Kalkış Yeri:</label>
                        <select id="kalkis" name="kalkis" required>
                            <?php foreach ($iller as $il): ?>
                                <option value="<?php echo $il; ?>" <?php echo ($sefer['kalkis'] === $il) ? 'selected' : ''; ?>>
                                    <?php echo $il; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="varis">Varış Yeri:</label>
                        <select id="varis" name="varis" required>
                            <?php foreach ($iller as $il): ?>
                                <option value="<?php echo $il; ?>" <?php echo ($sefer['varis'] === $il) ? 'selected' : ''; ?>>
                                    <?php echo $il; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="tarih">Tarih:</label>
                        <input type="date" id="tarih" name="tarih" value="<?php echo htmlspecialchars($sefer['tarih']); ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="saat">Saat:</label>
                        <input type="time" id="saat" name="saat" value="<?php echo htmlspecialchars($sefer['saat']); ?>" required>
                    </div>
                </div>
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="fiyat">Bilet Fiyatı (TL):</label>
                        <input type="number" id="fiyat" name="fiyat" step="0.01" value="<?php echo htmlspecialchars($sefer['fiyat']); ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="koltuk_sayisi">Koltuk Sayısı:</label>
                        <input type="number" id="koltuk_sayisi" name="koltuk_sayisi" value="<?php echo htmlspecialchars($sefer['koltuk_sayisi']); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Güncelle</button>
            </form>
        </section>
    </div>

</body>
</html>