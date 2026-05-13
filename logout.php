<?php
require_once 'config/db.php';
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <!-- auto redirect ke login lepas 2 saat -->
    <meta http-equiv="refresh" content="2;url=<?= BASE_URL ?>/login.php">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);">
    <div style="text-align:center;">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" 
                alt="StudyVerse Logo"
                class="logo-img">
        <h2 style="color:var(--text);font-size:20px;margin-bottom:8px;">LOGGED OUT SUCCESFULLY!</h2>
        <p style="color:var(--muted);font-size:14px;margin-bottom:24px;">
            Thank You For Using STUDYVERSE<br>
            You Will Be Send To The Login Page Shortly...
        </p>
        <!-- loading bar -->
        <div style="width:200px;height:3px;background:var(--border);border-radius:2px;margin:0 auto 20px;">
            <div style="height:100%;background:var(--accent);border-radius:2px;animation:loadbar 2s linear forwards;"></div>
        </div>
        <!--<a href="<?= BASE_URL ?>/login.php" style="font-size:13px;color:var(--accent);">
            Click here if you are not automatically redirected →
        </a>-->
    </div>
</div>
<style>
    @keyframes loadbar {
        from { width: 0%; }
        to   { width: 100%; }
    }
</style>
</body>
</html>

//settle