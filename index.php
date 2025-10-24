<?php
session_start();
require 'db.php';
require 'iller.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_panel.php');
        exit();
    }
    if ($_SESSION['role'] === 'firma_admin') {
        header('Location: firma_panel.php');
        exit();
    }
}

$kalkis = $_GET['kalkis'] ?? null;
$varis = $_GET['varis'] ?? null;
$tarih = $_GET['tarih'] ?? null;

$sql = "SELECT trips.*, firms.name as firma_adi 
        FROM trips 
        JOIN firms ON trips.firm_id = firms.id";
$where_clauses = [];
$params = [];

if (!empty($kalkis)) {
    $where_clauses[] = "kalkis = ?";
    $params[] = $kalkis;
}
if (!empty($varis)) {
    $where_clauses[] = "varis = ?";
    $params[] = $varis;
}
if (!empty($tarih)) {
    $where_clauses[] = "tarih = ?";
    $params[] = $tarih;
} else {
    $where_clauses[] = "tarih >= date('now')";
}

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY tarih, saat ASC";

try {
    $firmalar = $db->query("SELECT * FROM firms ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $seferler = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anasayfa - ErayBilet Platformu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">ErayBilet</a>
        <div class="nav-links">
             <?php if (isset($_SESSION['user_id'])): ?>
                <span class="welcome-user">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
                <a href="profile.php">Profilim</a>
                <a href="logout.php">Çıkış Yap</a>
            <?php else: ?>
                <a href="login.php">Giriş Yap</a>
                <a href="register.php">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container-grid">
        
        <aside class="sidebar">
            <div class="card">
                <h2 style="padding: 20px; margin-bottom:0; border-bottom: 1px solid #000000ff;">Tüm Firmalar</h2>
                <ul class="company-list">
                    <?php if (count($firmalar) > 0): ?>
                        <?php foreach ($firmalar as $firma): ?>
                            <li><?php echo htmlspecialchars($firma['name']); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="color: #6c757d;">Sistemde firma bulunmuyor.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            
            <section class="card search-form-card">
                <form action="index.php" method="GET" class="search-form">
                    <div class="form-group">
                        <label for="kalkis">Kalkış Yeri</label>
                        <select id="kalkis" name="kalkis">
                            <option value="">Tümü</option>
                            <?php foreach ($iller as $il): ?>
                                <option value="<?php echo $il; ?>" <?php echo ($kalkis === $il) ? 'selected' : ''; ?>>
                                    <?php echo $il; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="varis">Varış Yeri</label>
                        <select id="varis" name="varis">
                            <option value="">Tümü</option>
                            <?php foreach ($iller as $il): ?>
                                <option value="<?php echo $il; ?>" <?php echo ($varis === $il) ? 'selected' : ''; ?>>
                                    <?php echo $il; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tarih">Tarih</label>
                        <input type="date" id="tarih" name="tarih" value="<?php echo htmlspecialchars($tarih ?? ''); ?>">
                    </div>
                    <button type-="submit" class="btn btn-primary" style="height: 42px;">Sefer Bul</button>
                </form>
            </section>

            <h2><?php echo !empty($kalkis) || !empty($varis) || !empty($tarih) ? 'Arama Sonuçları' : 'Yaklaşan Seferler'; ?></h2>
            
            <div class="trips-container">
                <?php if (count($seferler) > 0): ?>
                    <?php foreach ($seferler as $sefer): ?>
                        <div class="trip-card">
                            <div class="trip-info">
                                <div>
                                    <div class="route"><?php echo htmlspecialchars($sefer['kalkis']); ?> &rarr; <?php echo htmlspecialchars($sefer['varis']); ?></div>
                                    <div style="font-size: 14px; color: #6c757d; margin-top: 5px;"><?php echo htmlspecialchars($sefer['firma_adi']); ?></div>
                                </div>
                                <div class="date-time">
                                    <div><?php echo date("d M Y", strtotime($sefer['tarih'])); ?></div>
                                    <div style="font-weight:bold;"><?php echo htmlspecialchars($sefer['saat']); ?></div>
                                </div>
                                <div class="trip-price"><?php echo htmlspecialchars($sefer['fiyat']); ?> TL</div>
                                
                                <a href="buy_ticket.php?trip_id=<?php echo $sefer['id']; ?>" class="btn btn-success">
                                    <?php echo isset($_SESSION['user_id']) ? 'Satın Al' : 'Detayları Gör'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; font-size: 1.1em; padding: 25px;">Aradığınız kriterlere uygun sefer bulunamadı.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>