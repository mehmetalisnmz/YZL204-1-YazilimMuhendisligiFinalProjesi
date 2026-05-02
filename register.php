<?php
// register.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $categories = $_POST['categories'] ?? [];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Bu e-posta adresi zaten kullanımda.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'member')");
        $insert->execute([$name, $email, $hashed_password]);
        $user_id = $pdo->lastInsertId();

        // İlgi alanlarını kaydet
        if (!empty($categories)) {
            $pref_insert = $pdo->prepare("INSERT INTO user_preferences (user_id, category) VALUES (?, ?)");
            foreach ($categories as $cat) {
                $pref_insert->execute([$user_id, $cat]);
            }
        }

        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol - AnkaraAI Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh;">

<div class="event-card" style="width: 400px; padding: 30px;">
    <h2>Kayıt Ol</h2>
    <?php if(!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <div style="margin-bottom:15px;">
            <label>Ad Soyad:</label><br>
            <input type="text" name="name" class="search-bar" required style="width:100%; margin-top:5px;">
        </div>
        <div style="margin-bottom:15px;">
            <label>E-Posta:</label><br>
            <input type="email" name="email" class="search-bar" required style="width:100%; margin-top:5px;">
        </div>
        <div style="margin-bottom:15px;">
            <label>Şifre:</label><br>
            <input type="password" name="password" class="search-bar" required style="width:100%; margin-top:5px;">
        </div>
        <div style="margin-bottom:15px;">
            <label>İlgi Alanları (Bildirim Almak İçin):</label><br>
            <label><input type="checkbox" name="categories[]" value="Konferans"> Konferans</label><br>
            <label><input type="checkbox" name="categories[]" value="Panel"> Panel</label><br>
            <label><input type="checkbox" name="categories[]" value="Tiyatro"> Tiyatro</label><br>
            <label><input type="checkbox" name="categories[]" value="Hackathon"> Hackathon</label><br>
            <label><input type="checkbox" name="categories[]" value="Workshop"> Workshop</label>
        </div>
        <button type="submit" class="btn-read" style="width:100%; cursor:pointer; border:none; padding:10px;">Kayıt Ol</button>
    </form>
    <p style="margin-top:20px; font-size:14px; text-align:center;">Hesabınız var mı? <a href="login.php" style="color:var(--accent);">Giriş Yap</a></p>
</div>

</body>
</html>
