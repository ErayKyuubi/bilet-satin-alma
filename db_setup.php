<?php
require 'db.php';

$tables = [
    // Kullanıcılar tablosu
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('admin','firma_admin','user')),
        firm_id INTEGER DEFAULT NULL,
        kredi REAL DEFAULT 0,
        FOREIGN KEY(firm_id) REFERENCES firms(id)
    );",

    // Firmalar tablosu
    "CREATE TABLE IF NOT EXISTS firms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL
    );",

    // Seferler tablosu
    "CREATE TABLE IF NOT EXISTS trips (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firm_id INTEGER NOT NULL,
        kalkis TEXT NOT NULL,
        varis TEXT NOT NULL,
        tarih TEXT NOT NULL,
        saat TEXT NOT NULL,
        fiyat REAL NOT NULL,
        koltuk_sayisi INTEGER NOT NULL,
        FOREIGN KEY(firm_id) REFERENCES firms(id)
    );",

    // Biletler tablosu
    "CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        trip_id INTEGER NOT NULL,
        koltuk_no INTEGER NOT NULL,
        fiyat REAL NOT NULL,
        kupon_id INTEGER,
        durum TEXT DEFAULT 'aktif',
        tarih TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(trip_id) REFERENCES trips(id),
        FOREIGN KEY(kupon_id) REFERENCES coupons(id)
    );",

    // Kuponlar tablosu 
    "CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kod TEXT UNIQUE NOT NULL,
    oran INTEGER NOT NULL,
    kullanim_limit INTEGER DEFAULT 1,
    son_tarih TEXT NOT NULL,
    firm_id INTEGER DEFAULT NULL,  
    FOREIGN KEY(firm_id) REFERENCES firms(id)
    );",
    
];

foreach ($tables as $sql) {
    $db->exec($sql);
}

echo "Veritabanı başarıyla oluşturuldu!";
?>
