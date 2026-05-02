<?php
// cron.php
require 'db.php';
require_once 'mail_service.php';

// .env dosyasını yükle ve API anahtarını al (loadEnvMail fonksiyonu mail_service.php'de var)
$api_key = getenv('GEMINI_API_KEY');

if (!$api_key) {
    die("API Anahtarı bulunamadı.");
}

$model = "gemini-2.5-flash-lite"; 
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

$stmt = $pdo->query("SELECT title FROM events");
$existing_events = $stmt->fetchAll(PDO::FETCH_COLUMN);

function isEventExists($new_title, $existing_events, $threshold = 80) {
    $new_title = mb_strtolower(trim($new_title));
    foreach ($existing_events as $existing_title) {
        $existing_title = mb_strtolower(trim($existing_title));
        similar_text($new_title, $existing_title, $percent);
        if ($percent >= $threshold) {
            return true;
        }
    }
    return false;
}

$prompt = "Ankara'da önümüzdeki 30 gün içinde yapılacak olan panel, tiyatro, hackathon, öğrenci etkinlikleri, workshop ve konferansları web'de ara. Bana sadece aşağıdaki JSON formatında, geçerli bir JSON dizisi (array) dön. Markdown kullanma, sadece saf JSON ver:
[
  {
    \"title\": \"Etkinlik Adı\",
    \"category\": \"Kategori (Panel/Tiyatro vb.)\",
    \"location\": \"Mekan Adı, Ankara\",
    \"event_date\": \"DD Ay YYYY\",
    \"ai_summary\": \"2 cümlelik akıllı özet\",
    \"source_link\": \"Etkinliğin resmi kayıt veya duyuru web adresi\"
  }
]";

$data = [
    "contents" => [["parts" => [["text" => $prompt]]]],
    "tools" => [["google_search" => new stdClass()]],
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
    $raw_json_text = $result['candidates'][0]['content']['parts'][0]['text'];
    $raw_json_text = preg_replace('/^```json\s*/i', '', $raw_json_text);
    $raw_json_text = preg_replace('/\s*```$/i', '', $raw_json_text);
    
    $new_events = json_decode($raw_json_text, true);

    if (is_array($new_events)) {
        $added_count = 0;
        foreach ($new_events as $event) {
            if (!isEventExists($event['title'], $existing_events)) {
                
                $tag_color = 'blue';
                $cat = mb_strtolower($event['category']);
                if (strpos($cat, 'tiyatro') !== false) $tag_color = 'amber';
                elseif (strpos($cat, 'hackathon') !== false) $tag_color = 'purple';
                elseif (strpos($cat, 'öğrenci') !== false) $tag_color = 'green';

                $source_link = $event['source_link'] ?? '#';

                $insert = $pdo->prepare("INSERT INTO events (title, category, location, event_date, ai_summary, tag_color, source_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($insert->execute([$event['title'], $event['category'], $event['location'], $event['event_date'], $event['ai_summary'], $tag_color, $source_link])) {
                    $added_count++;
                    $existing_events[] = $event['title']; 
                    
                    // Yeni eklenen etkinlik için abonelere mail at
                    sendNotificationToUsers($pdo, $event['category'], $event['title'], $event['event_date']);
                }
            }
        }
        echo "Cron başarıyla çalıştı. $added_count yeni etkinlik eklendi ve bildirimler gönderildi.";
    } else {
         echo "JSON parse hatası: Gelen veri geçerli bir JSON değil.";
    }
} else {
    echo "Gemini API yanıt vermedi veya format hatalı.";
}
?>