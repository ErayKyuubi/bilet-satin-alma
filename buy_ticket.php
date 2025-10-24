<?php
session_start();
require 'db.php';


// Sefer ID'sini al ve doğrula
$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id || !is_numeric($trip_id)) {
    header('Location: index.php');
    exit();
}

try {
    $sql_trip = "SELECT trips.*, firms.name as firma_adi FROM trips
                 JOIN firms ON trips.firm_id = firms.id WHERE trips.id = ?";
    $stmt_trip = $db->prepare($sql_trip);
    $stmt_trip->execute([$trip_id]);
    $sefer = $stmt_trip->fetch(PDO::FETCH_ASSOC);

    if (!$sefer) {
        header('Location: index.php');
        exit();
    }

    $sql_tickets = "SELECT koltuk_no FROM tickets WHERE trip_id = ?";
    $stmt_tickets = $db->prepare($sql_tickets);
    $stmt_tickets->execute([$trip_id]);
    $dolu_koltuklar = $stmt_tickets->fetchAll(PDO::FETCH_COLUMN, 0);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Al - <?php echo htmlspecialchars($sefer['kalkis'] . ' - ' . $sefer['varis']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">ErayBilet</a>
        <div class="nav-links">
             <span class="welcome-user">Hoş Geldin</span>
             <a href="profile.php">Profilim</a>
             <a href="logout.php">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container-normal">
        <h1>Bilet Satın Alma</h1>

        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <section class="card">
            <h2>Sefer Bilgileri</h2>
            <div class="trip-info" style="font-size: 16px; line-height: 1.7;">
                <strong>Firma:</strong> <?php echo htmlspecialchars($sefer['firma_adi']); ?><br>
                <strong>Güzergah:</strong> <?php echo htmlspecialchars($sefer['kalkis']); ?> &rarr; <?php echo htmlspecialchars($sefer['varis']); ?><br>
                <strong>Tarih & Saat:</strong> <?php echo htmlspecialchars($sefer['tarih']); ?> - <?php echo htmlspecialchars($sefer['saat']); ?><br>
                <strong>Fiyat:</strong> <span id="base-price" style="font-weight: bold; color: var(--primary-color);"><?php echo number_format($sefer['fiyat'], 2); ?></span> TL
            </div>
        </section>

        <section class="card">
            <form id="purchase-form" action="handle_actions.php?islem=bilet_al" method="POST">
                <input type="hidden" name="trip_id" value="<?php echo $sefer['id']; ?>">
                <input type="hidden" name="koltuk_no" id="selected-seat-input" required>

                <h2>Koltuk Seçimi</h2>
                <div class="bus-layout">
                    <?php for ($i = 1; $i <= $sefer['koltuk_sayisi']; $i++): ?>
                        <?php
                        $is_occupied = in_array($i, $dolu_koltuklar);
                        $seat_class = 'seat' . ($is_occupied ? ' occupied' : '');
                        
                        if ($i % 2 == 0 && $i % 4 != 0) {
                            echo '<div class="' . $seat_class . '" data-seat-number="' . $i . '">' . $i . '</div>';
                            echo '<div class="seat aisle"></div>'; 
                        } else {
                            echo '<div class="' . $seat_class . '" data-seat-number="' . $i . '">' . $i . '</div>';
                        }
                        ?>
                    <?php endfor; ?>
                </div>

                <div class="purchase-summary">
                    <h2>Ödeme Bilgileri</h2>
                    <div class="form-group">
                        <label for="kupon_kodu">Kupon Kodu (isteğe bağlı):</label>
                        <input type="text" id="kupon_kodu" name="kupon_kodu" placeholder="Varsa kupon kodunu girin">
                    </div>
                    <div class="price-details" style="margin-bottom: 20px;">
                        <strong>Seçilen Koltuk:</strong> <span id="selected-seat-display">Henüz seçilmedi</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Satın Almayı Tamamla</button>
                </div>
            </form>
        </section>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const seats = document.querySelectorAll('.seat:not(.aisle):not(.occupied)');
    const selectedSeatInput = document.getElementById('selected-seat-input');
    const selectedSeatDisplay = document.getElementById('selected-seat-display');
    const form = document.getElementById('purchase-form');

    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    seats.forEach(seat => {
        seat.addEventListener('click', function() {
            const currentSelected = document.querySelector('.seat.selected');
            if (currentSelected) {
                currentSelected.classList.remove('selected');
            }
            
            this.classList.add('selected');
            const seatNumber = this.getAttribute('data-seat-number');
            
            selectedSeatInput.value = seatNumber;
            selectedSeatDisplay.textContent = seatNumber;
        });
    });

    form.addEventListener('submit', function(event) {
    
        if (!selectedSeatInput.value) {
            alert('Lütfen bir koltuk seçin!');
            event.preventDefault();
            return;
        }

        
        if (!isLoggedIn) {
            alert('Lütfen Giriş Yapın!'); 
            event.preventDefault(); 
            window.location.href = 'login.php';
        }
    });
});
</script>

</body>
</html>