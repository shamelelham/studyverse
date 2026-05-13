<?php
require_once 'config/db.php';

// redirect kalau dah login
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';
$step = $_GET['step'] ?? '1'; // step 1 : email, step 2 : reset password

//  check email 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_email'])) {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $error = 'PLEASE ENTER YOUR EMAIL.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'INVALID EMAIL FORMAT';
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // generate token-> save to DB -> send email via PHPMailer
            // reset password
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_uid'] = $user['id'];
            $success = "EMAIL FOUND! ENTER YOUR NEW PASSWORD.";
            $step = '2';
        } else {
            $error = 'EMAIL NOT FOUND OR ACCOUNT HAS BEEN BANNED.';
        }
    }
}

// step 2 : reset password 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $uid = $_SESSION['reset_uid'] ?? null;

    if (!$uid) {
        $error = 'SESSION EXPIRED. PLEASE START AGAIN.';
        $step = '1';
    } elseif (strlen($newPass) < 6) {
        $error = ' PASSWORD MUST BE AT LEAST 6 CHARACTERS.';
        $step = '2';
    } elseif ($newPass !== $confirm) {
        $error = 'PASSWORD DOES NOT MATCH.';
        $step = '2';
    } else {
        $hashed = $newPass;
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([$hashed, $uid]);

        // clear reset session
        unset($_SESSION['reset_email'], $_SESSION['reset_uid']);

        setFlash('success', 'PASSWORD SUCCESFULLY CHANGED! GO LOGIN.');
        redirect('/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box">

        <div class="auth-hero">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" 
                    alt="StudyVerse Logo"
                    class="logo-img">
            <h1>STUDYVERSE</h1>
            <p><?= $step === '2' ? 'SET YOUR NEW PASSWORD.' : 'RESET YOUR PASSWORD' ?></p>
        </div>

        <div class="card">

            <!-- step indicator -->
            <div style="display:flex;gap:8px;margin-bottom:20px;">
                <div style="flex:1;height:3px;border-radius:2px;background:<?= $step>='1'?'var(--accent)':'var(--border)' ?>;"></div>
                <div style="flex:1;height:3px;border-radius:2px;background:<?= $step>='2'?'var(--accent)':'var(--border)' ?>;"></div>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?=  e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

            <?php if ($step === '1'): ?>
            <!-- step 1 : masuk email -->
            <div style="margin-bottom:16px;">
                <div style="font-weight:500;color:var(--text);margin-bottom:4px;">PLEASE ENTER YOUR EMAIL.</div>
                <div style="font-size:13px;color:var(--muted);">
                    WE WILL CHECK YOUR EMAIL IN THE SYSTEM.
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="check_email" value="1">
                <div class="form-group">
                    <label>EMAIL</label>
                    <input type="email" name="email"
                           placeholder="email@example.com"
                           value="<?= e($_POST['email'] ?? '') ?>"
                           required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-full">CHECK EMAIL</button>
            </form>

            <?php elseif ($step === '2'): ?>
            <!-- step 2 : new password -->
            <div style="margin-bottom:16px;">
                <div style="font-weight:500;color:var(--text);margin-bottom:4px;">SET NEW PASSWORD</div>
                <div style="font-size:13px;color:var(--muted);">
                    EMAIL :<span style="color:var(--accent-l);"><?= e($_SESSION['reset_email'] ?? '') ?></span>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="reset_password" value="1">
                <div class="form-group">
                    <label>NEW PASSWORD</label>
                    <input type="password" name="new_password"
                           placeholder="MIN 6 CHARACTERS." required autofocus>
                </div>
                <div class="form-group">
                    <label>CONFIRM NEW PASSWORD</label>
                    <input type="password" name="confirm_password"
                           placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">RESET PASSWORD</button>
            </form>
            <?php endif; ?>

            <!--back login page -->
            <div style="text-align:center;margin-top:16px;">
                <a href="<?= BASE_URL ?>/login.php"
                   style="font-size:12px;color:var(--muted);">
                    ← RETURN LOGIN PAGE
                </a>
            </div>
        </div>

    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>

// settle

</html>