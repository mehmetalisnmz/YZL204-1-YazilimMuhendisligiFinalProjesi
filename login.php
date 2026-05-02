<?php
// login.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Hatalı e-posta veya şifre.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - AnkaraAI Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh;">

<div class="event-card" style="width: 400px; padding: 30px;">
    <h2>Giriş Yap</h2>
    <?php if(!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <div style="margin-bottom:15px;">
            <label>E-Posta:</label><br>
            <input type="email" name="email" class="search-bar" required style="width:100%; margin-top:5px;">
        </div>
        <div style="margin-bottom:15px;">
            <label>Şifre:</label><br>
            <input type="password" name="password" class="search-bar" required style="width:100%; margin-top:5px;">
        </div>
        <button type="submit" class="btn-read" style="width:100%; cursor:pointer; border:none; padding:10px;">Giriş Yap</button>
    </form>
    <p style="margin-top:20px; font-size:14px; text-align:center;">Hesabınız yok mu? <a href="register.php" style="color:var(--accent);">Kayıt Ol</a></p>
</div>

</body>
</html>
