<?php
session_start();
require 'db.php';
require('fpdf/fpdf.php'); 


if (!isset($_SESSION['user_id'])) {
    die("Bu sayfayı görüntülemek için giriş yapmalısınız.");
}

// Bilet ID kontrolü
$bilet_id = $_GET['ticket_id'] ?? null;
if (!$bilet_id) {
    die("Geçersiz bilet ID'si.");
}

$user_id = $_SESSION['user_id'];

try {
    // Bilet geçerli kullanıcıya mı ait kontrol.
    $sql = "
        SELECT 
            tickets.id as bilet_id, tickets.koltuk_no, tickets.fiyat as odenen_fiyat,
            trips.kalkis, trips.varis, trips.tarih, trips.saat,
            firms.name as firma_adi,
            users.username
        FROM tickets
        JOIN trips ON tickets.trip_id = trips.id
        JOIN firms ON trips.firm_id = firms.id
        JOIN users ON tickets.user_id = users.id
        WHERE tickets.id = ? AND tickets.user_id = ?
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$bilet_id, $user_id]);
    $bilet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bilet) {
        die("Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.");
    }

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}



$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16); 


$pdf->Cell(0, 10, 'ErayBilet - Yolculuk Biletiniz', 0, 1, 'C'); 
$pdf->Ln(10); 


$pdf->SetFont('Arial', '', 12);


$firma_adi = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $bilet['firma_adi']);
$guzergah = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $bilet['kalkis'] . ' -> ' . $bilet['varis']);
$yolcu_adi = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $bilet['username']);

$pdf->Cell(40, 10, 'Firma:', 0, 0);
$pdf->Cell(0, 10, $firma_adi, 0, 1);

$pdf->Cell(40, 10, 'Guzergah:', 0, 0);
$pdf->Cell(0, 10, $guzergah, 0, 1);

$pdf->Cell(40, 10, 'Tarih ve Saat:', 0, 0);
$pdf->Cell(0, 10, $bilet['tarih'] . ' - ' . $bilet['saat'], 0, 1);

$pdf->Ln(5); 

$pdf->Cell(40, 10, 'Yolcu Adi:', 0, 0);
$pdf->Cell(0, 10, $yolcu_adi, 0, 1);

$pdf->Cell(40, 10, 'Koltuk No:', 0, 0);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, $bilet['koltuk_no'], 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->Cell(40, 10, 'Odenen Tutar:', 0, 0);
$pdf->Cell(0, 10, number_format($bilet['odenen_fiyat'], 2) . ' TL', 0, 1);

$pdf->Ln(15);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Iyi yolculuklar dileriz!', 0, 1, 'C');


$pdf_dosya_adi = 'ErayBilet' . $bilet['bilet_id'] . '.pdf';
$pdf->Output('D', $pdf_dosya_adi);
?>