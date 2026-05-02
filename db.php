<?php
// db.php
$db_file = __DIR__ . '/events.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabloları oluştur
    $query = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'member',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS user_preferences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        category TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        category TEXT NOT NULL,
        location TEXT NOT NULL,
        event_date TEXT NOT NULL,
        ai_summary TEXT NOT NULL,
        tag_color TEXT DEFAULT 'blue',
        source_link TEXT,
        organizer_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organizer_id) REFERENCES users(id)
    );
    ";
    $pdo->exec($query);
    
    // Geçici Admin kullanıcısı ekleme
    $stmt_admin = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@ankaraai.com'");
    if ($stmt_admin->fetchColumn() == 0) {
        $admin_pass = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Sistem Yöneticisi', 'admin@ankaraai.com', '$admin_pass', 'admin')");
    }


    // Test için örnek veri ekleme (sadece tablo boşsa çalışır)
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    if ($stmt->fetchColumn() == 0) {
        $sample_data = "INSERT INTO events (title, category, location, event_date, ai_summary, tag_color, source_link) VALUES 
        ('Yapay Zeka ve Gelecek', 'Konferans', 'ODTÜ KKM, Ankara', '25 Nisan 2026', 'Yapay zekanın savunma ve otonom sistemlerdeki rolü üzerine kapsamlı bir akademik değerlendirme.', 'blue', '#'),
        ('Flutter ile Mobil Geliştirme', 'Workshop', 'Ostim Teknik Üni.', '10 Mayıs 2026', 'Sıfırdan ileri seviyeye Flutter ile cross-platform uygulama geliştirme pratikleri.', 'purple', '#')";
        $pdo->exec($sample_data);
    }

} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>