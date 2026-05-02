<?php
// index.php
session_start();
require 'db.php';

// Etkinlikleri veritabanından çek
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmt->fetchAll();

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'anonymous';
$user_name = $_SESSION['user_name'] ?? 'Misafir';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>AnkaraAI Events — Intelligence-Powered Event Portal</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <div class="logo-icon">&#129504;</div>
    <div>
      <div class="logo-text">AnkaraAI</div>
      <div class="logo-sub">Events Portal</div>
    </div>
  </div>
  
  <div style="padding: 15px; background: rgba(255,255,255,0.05); margin: 0 15px 15px 15px; border-radius: 8px;">
      <div style="font-size: 13px; color: #a1a1aa;">Hoş Geldiniz,</div>
      <div style="font-size: 14px; font-weight: bold; color: #fff; margin-bottom: 8px;"><?= htmlspecialchars($user_name) ?></div>
      <?php if($user_id): ?>
          <a href="logout.php" style="color: #ef4444; font-size: 12px; text-decoration: none;">Çıkış Yap</a>
      <?php else: ?>
          <a href="login.php" style="color: var(--accent); font-size: 12px; text-decoration: none; margin-right:10px;">Giriş Yap</a>
          <a href="register.php" style="color: var(--accent); font-size: 12px; text-decoration: none;">Kayıt Ol</a>
      <?php endif; ?>
  </div>

  <nav>
    <div class="nav-label">Main Menu</div>
    <a href="index.php" class="nav-item active"><span class="icon">&#9702;</span> Dashboard <span class="nav-badge"><?= count($events) ?></span></a>
    <a href="#" class="nav-item"><span class="icon">&#128269;</span> Ara & Filtrele</a>
    
    <?php if(in_array($user_role, ['admin', 'organizer'])): ?>
    <div class="nav-label" style="margin-top:12px; color: var(--accent);">Organizatör Menüsü</div>
    <a href="add_event.php" class="nav-item"><span class="icon">&#10133;</span> Yeni Etkinlik Ekle</a>
    <?php endif; ?>

    <div class="nav-label" style="margin-top:12px">Kategoriler</div>
    <a href="#" class="nav-item"><span class="icon">&#127891;</span> Akademik Paneller</a>
    <a href="#" class="nav-item"><span class="icon">&#127901;</span> Tiyatro & Sanat</a>
    <a href="#" class="nav-item"><span class="icon">&#128295;</span> Workshops</a>
    <a href="#" class="nav-item"><span class="icon">&#127908;</span> Konferanslar</a>
    <a href="#" class="nav-item"><span class="icon">&#9889;</span> Hackathons</a>
    <a href="#" class="nav-item"><span class="icon">&#127890;</span> Öğrenci Etkinlikleri</a>

  </nav>
</aside>

<main class="main-content">
    <header class="topbar">
        <h1>Dashboard</h1>
        <input type="text" id="main-search" class="search-bar" placeholder="Etkinlik, konu veya lokasyon ara...">
    </header>

    <div class="content-wrapper">
        <h2 style="margin-bottom: 20px;">Yaklaşan Etkinlikler</h2>
        
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="card-header">
                    <span class="tag <?= htmlspecialchars($event['tag_color']) ?>"><?= htmlspecialchars($event['category']) ?></span>
                </div>
                <h3 class="card-headline"><?= htmlspecialchars($event['title']) ?></h3>
                
                <div class="card-meta">
                    <span>📅 <?= htmlspecialchars($event['event_date']) ?></span>
                    <span>📍 <?= htmlspecialchars($event['location']) ?></span>
                </div>
                
                <div class="ai-badge">✦ AI ÖZET</div>
                <p class="ai-summary"><?= htmlspecialchars($event['ai_summary']) ?></p>
                
				<div class="card-footer">
					<a href="<?= htmlspecialchars($event['source_link'] ?? '#') ?>" target="_blank" class="btn-read" style="display:block; text-decoration:none;">
						Devamını Oku →
					</a>
				</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

</body>
</html>