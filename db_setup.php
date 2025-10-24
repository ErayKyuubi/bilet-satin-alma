<?php

$db_file = __DIR__ . '/db/database.sqlite';
$db_dir = __DIR__ . '/db';

if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabloları oluşturmak için SQL komutları
    $commands = [
        // Kullanıcılar Tablosu
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user',
            firm_id INTEGER DEFAULT NULL,
            kredi REAL DEFAULT 1000.00,
            FOREIGN KEY(firm_id) REFERENCES firms(id)
        );",
        
        // Firmalar Tablosu
        "CREATE TABLE IF NOT EXISTS firms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        );",
        
        // Seferler Tablosu
        "CREATE TABLE IF NOT EXISTS trips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            firm_id INTEGER NOT NULL,
            kalkis TEXT NOT NULL,
            varis TEXT NOT NULL,
            tarih TEXT NOT NULL,
            saat TEXT NOT NULL,
            fiyat REAL NOT NULL,
            koltuk_sayisi INTEGER DEFAULT 40,
            FOREIGN KEY(firm_id) REFERENCES firms(id)
        );",
        
        // Biletler Tablosu
        "CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            trip_id INTEGER NOT NULL,
            koltuk_no INTEGER NOT NULL,
            fiyat REAL NOT NULL,
            satin_alma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id),
            FOREIGN KEY(trip_id) REFERENCES trips(id)
        );",
        
        // Kuponlar Tablosu
        "CREATE TABLE IF NOT EXISTS coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kod TEXT UNIQUE NOT NULL,
            oran INTEGER NOT NULL,
            kullanim_limit INTEGER DEFAULT 1,
            son_tarih TEXT NOT NULL,
            firm_id INTEGER DEFAULT NULL,
            FOREIGN KEY(firm_id) REFERENCES firms(id)
        );"
    ];

    foreach ($commands as $command) {
        $db->exec($command);
    }
    
    echo "Tüm tablolar başarıyla oluşturuldu.<br>";
    

    try {
        $db->exec("INSERT INTO firms (name) VALUES ('Varsayılan Firma')");
        $default_firm_id = $db->lastInsertId();
        echo "Varsayılan Firma eklendi (ID: $default_firm_id).<br>";
    } catch (PDOException $e) {
        $stmt = $db->query("SELECT id FROM firms WHERE name = 'Varsayılan Firma'");
        $default_firm_id = $stmt->fetchColumn();
        echo "Varsayılan Firma zaten mevcut (ID: $default_firm_id).<br>";
    }

    $default_users = [
        ['admin', password_hash('admin', PASSWORD_DEFAULT), 'admin', null],
        ['firma', password_hash('firma', PASSWORD_DEFAULT), 'firma_admin', $default_firm_id],
        ['user', password_hash('user', PASSWORD_DEFAULT), 'user', null]
    ];
    
    $stmt = $db->prepare("INSERT INTO users (username, password, role, firm_id) VALUES (?, ?, ?, ?)");
    
    foreach ($default_users as $user) {
        try {
            $stmt->execute($user);
            echo "'$user[0]' kullanıcısı başarıyla eklendi.<br>";
        } catch (PDOException $e) {
            echo "'$user[0]' kullanıcısı zaten mevcut.<br>";
        }
    }
    
    echo "<hr><strong>Kurulum tamamlandı! Ana sayfaya gidebilirsiniz.</strong>";
    
} catch (PDOException $e) {
    die("Veritabanı bağlantısı veya kurulum başarısız: " . $e->getMessage());
}
?>
