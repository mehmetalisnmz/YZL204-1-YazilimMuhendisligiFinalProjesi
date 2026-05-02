<?php
// mail_service.php
require 'db.php';
require 'vendor/PHPMailer-6.9.1/src/Exception.php';
require 'vendor/PHPMailer-6.9.1/src/PHPMailer.php';
require 'vendor/PHPMailer-6.9.1/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// .env yükleme fonksiyonunu cron.php'den veya ortak bir helpers dosyasından alabiliriz.
// Şimdilik burada da basitçe yüklüyoruz.
function loadEnvMail($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

loadEnvMail(__DIR__ . '/.env');

function sendNotificationToUsers($pdo, $category, $event_title, $event_date) {
    $smtp_user = getenv('SMTP_USER');
    $smtp_pass = getenv('SMTP_PASS');
    
    if (!$smtp_user || !$smtp_pass || $smtp_pass == '16hanelisifre123') {
        // Eğer geçersiz veya varsayılan şifre ise e-posta atma
        return false;
    }

    // Bu kategoriye abone olan kullanıcıları bul
    $stmt = $pdo->prepare("SELECT u.email, u.name FROM users u JOIN user_preferences up ON u.id = up.user_id WHERE up.category = ?");
    $stmt->execute([$category]);
    $subscribers = $stmt->fetchAll();

    if (count($subscribers) == 0) return true;

    $mail = new PHPMailer(true);

    try {
        // Sunucu ayarları
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Gönderen
        $mail->setFrom($smtp_user, 'AnkaraAI Events');

        // Alıcılar
        foreach ($subscribers as $sub) {
            $mail->addBCC($sub['email'], $sub['name']);
        }

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = "Yeni Etkinlik: " . $event_title;
        $mail->Body    = "Merhaba, <br><br>İlgilendiğiniz <b>$category</b> kategorisinde yeni bir etkinlik eklendi: <b>$event_title</b>.<br>Tarih: $event_date<br><br>Detaylar için portala giriş yapabilirsiniz.<br>AnkaraAI Events Team";
        $mail->AltBody = "Yeni etkinlik eklendi: $event_title ($event_date)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("E-Posta gönderilemedi. Hata: {$mail->ErrorInfo}");
        return false;
    }
}
?>
