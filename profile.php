<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $user_stmt = $db->prepare("SELECT username, kredi FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $kullanici = $user_stmt->fetch(PDO::FETCH_ASSOC);

    $tickets_sql = "
        SELECT 
            tickets.id as bilet_id,
            tickets.koltuk_no,
            tickets.fiyat as odenen_fiyat,
            trips.kalkis,
            trips.varis,
            trips.tarih,
            trips.saat,
            firms.name as firma_adi
        FROM tickets
        JOIN trips ON tickets.trip_id = trips.id
        JOIN firms ON trips.firm_id = firms.id
        WHERE tickets.user_id = ?
        ORDER BY trips.tarih DESC, trips.saat DESC
    ";
    $tickets_stmt = $db->prepare($tickets_sql);
    $tickets_stmt->execute([$user_id]);
    $biletler = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - <?php echo htmlspecialchars($kullanici['username']); ?></title>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <nav class="navbar">
        <a href="index.php" class="logo">ErayBilet</a>
        <div class="nav-links">
             <span class="welcome-user">Hoş Geldin, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
             <a href="profile.php">Profilim</a>
             <a href="logout.php">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container-normal">
        <h1>Profilim ve Biletlerim</h1>

        <?php
        if (isset($_SESSION['success_message'])) { echo '<div class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</div>'; unset($_SESSION['success_message']); }
        if (isset($_SESSION['error_message'])) { echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>'; unset($_SESSION['error_message']); }
        ?>

        <section class="card">
            <h2>Kullanıcı Bilgileri</h2>
            <div class="profile-info">
                <span>Kullanıcı Adı: <strong><?php echo htmlspecialchars($kullanici['username']); ?></strong></span>
                <span class="credit">Kredi Bakiyeniz: <?php echo number_format($kullanici['kredi'], 2); ?> TL</span>
            </div>
        </section>

        <section class="card">
            <h2>Bakiye Yükle</h2>
            <form action="handle_actions.php?islem=bakiye_yukle" method="POST" style="display: flex; gap: 10px; align-items: flex-end;">
                <div class="form-group" style="flex-grow: 1; margin-bottom: 0;">
                    <label for="miktar">Yüklenecek Tutar (TL):</label>
                    <input type="number" id="miktar" name="miktar" min="1" step="0.01" required>
                </div>
                <button type="submit" class="btn btn-success" style="height: 42px;">Yükle</button>
            </form>
        </section>

        <section class="card">
            <h2>Biletlerim</h2>
            <?php if (count($biletler) > 0): ?>
                <?php foreach ($biletler as $bilet): ?>
                    <?php
                    $sefer_zamani_str = $bilet['tarih'] . ' ' . $bilet['saat'];
                    $sefer_timestamp = strtotime($sefer_zamani_str);
                    $su_anki_timestamp = time();
                    $iptal_edilebilir_mi = ($sefer_timestamp - $su_anki_timestamp) > 3600;
                    ?>
                    <div class="ticket-card">
                        <div class="ticket-header"><?php echo htmlspecialchars($bilet['firma_adi']); ?></div>
                        <div class="ticket-body">
                            <div class="ticket-details">
                                <div class="route"><?php echo htmlspecialchars($bilet['kalkis']); ?> &rarr; <?php echo htmlspecialchars($bilet['varis']); ?></div>
                                <div class="date-time"><?php echo date("d M Y, D", strtotime($bilet['tarih'])); ?> - Saat: <?php echo htmlspecialchars($bilet['saat']); ?></div>
                                <div class="seat-price">
                                    <strong>Koltuk No:</strong> <?php echo $bilet['koltuk_no']; ?> | 
                                    <strong>Ödenen Tutar:</strong> <?php echo number_format($bilet['odenen_fiyat'], 2); ?> TL
                                </div>
                            </div>
                            <div class="ticket-actions">
                                <a href="generate_pdf.php?ticket_id=<?php echo $bilet['bilet_id']; ?>" class="btn btn-primary">PDF İndir</a>
                                
                                <?php if ($iptal_edilebilir_mi): ?>
                                    <form action="handle_actions.php?islem=bilet_iptal" method="POST" onsubmit="return confirm('Bileti iptal etmek istediğinizden emin misiniz? Ücret hesabınıza iade edilecektir.');">
                                        <input type="hidden" name="bilet_id" value="<?php echo $bilet['bilet_id']; ?>">
                                        <button type="submit" class="btn btn-danger">İptal Et</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>İptal Edilemez</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center;">Henüz satın alınmış bir biletiniz bulunmamaktadır.</p>
            <?php endif; ?>
        </section>
    </div>

</body>
</html>