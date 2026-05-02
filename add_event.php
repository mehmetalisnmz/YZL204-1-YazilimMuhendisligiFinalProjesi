<?php
// add_event.php
session_start();
require 'db.php';
require 'mail_service.php'; // Bildirim atmak için

// Sadece organizer ve admin girebilir
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'organizer'])) {
    die("Bu sayfaya erişim yetkiniz yok. Organizatör hesabı gereklidir.");
}

// .env yükleme
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}
loadEnv(__DIR__ . '/.env');
$api_key = getenv('GEMINI_API_KEY');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $source_link = $_POST['source_link'] ?? '#';
    $description = $_POST['description'] ?? '';

    // Tag rengi belirleme
    $tag_color = 'blue';
    $cat = mb_strtolower($category);
    if (strpos($cat, 'tiyatro') !== false) $tag_color = 'amber';
    elseif (strpos($cat, 'hackathon') !== false) $tag_color = 'purple';
    elseif (strpos($cat, 'öğrenci') !== false) $tag_color = 'green';

    // Gemini API ile açıklamayı özetle
    $ai_summary = $description; // Fallback
    if (!empty($description) && $api_key) {
        $prompt = "Aşağıdaki etkinlik açıklamasını çok kısa, en fazla 2 cümlelik profesyonel bir özete çevir:\n\n" . $description;
        $model = "gemini-2.5-flash-lite";
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        
        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => ["temperature" => 0.2]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_summary = trim($result['candidates'][0]['content']['parts'][0]['text']);
        }
    }

    // Veritabanına kaydet
    $insert = $pdo->prepare("INSERT INTO events (title, category, location, event_date, ai_summary, tag_color, source_link, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($insert->execute([$title, $category, $location, $event_date, $ai_summary, $tag_color, $source_link, $_SESSION['user_id']])) {
        // Yeni eklendiğinde mail at
        sendNotificationToUsers($pdo, $category, $title, $event_date);
        $success = "Etkinlik başarıyla eklendi ve üyelere bildirim atıldı.";
    } else {
        $error = "Etkinlik eklenirken hata oluştu.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Etkinlik Ekle - AnkaraAI Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div style="padding: 40px; max-width: 600px; margin: auto;">
    <a href="index.php" style="color:var(--accent); text-decoration:none;">← Dashboard'a Dön</a>
    <h2 style="margin-top:20px;">Yeni Etkinlik Ekle (Organizatör)</h2>
    
    <?php if(!empty($success)): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="event-card" style="padding: 20px;">
        <form method="POST">
            <div style="margin-bottom:15px;">
                <label>Etkinlik Başlığı:</label><br>
                <input type="text" name="title" class="search-bar" required style="width:100%; margin-top:5px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Kategori (Örn: Hackathon, Konferans):</label><br>
                <input type="text" name="category" class="search-bar" required style="width:100%; margin-top:5px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Lokasyon (Örn: ODTÜ KKM, Ankara):</label><br>
                <input type="text" name="location" class="search-bar" required style="width:100%; margin-top:5px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Tarih (Örn: 23 Nisan 2026):</label><br>
                <input type="text" name="event_date" class="search-bar" required style="width:100%; margin-top:5px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Kayıt / Bilet Linki:</label><br>
                <input type="url" name="source_link" class="search-bar" required style="width:100%; margin-top:5px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Etkinlik Açıklaması (AI Tarafından Özetlenecek):</label><br>
                <textarea name="description" class="search-bar" required style="width:100%; height:100px; margin-top:5px; resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn-read" style="width:100%; cursor:pointer; border:none; padding:10px;">Etkinliği Yayınla</button>
        </form>
    </div>
</div>
</body>
</html>
