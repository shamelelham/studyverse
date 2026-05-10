<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config/db.php';

if (isLoggedIn()) {
    $go = $_SESSION['user_role'] === 'Admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/dashboard.php';
    header('Location: ' . $go);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && $password === $u['password']) {
            if (!$u['is_active']) {
                $error = 'Your account has been banned. Contact admin.';
            } else {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['user_name'] = $u['name'];
                $_SESSION['user_email'] = $u['email'];
                $_SESSION['user_role'] = $u['role'];
                $_SESSION['user_level'] = $u['level'];

                header('Location: ' . ($u['role'] === 'Admin' ? BASE_URL . '/admin/dashboard.php ' : BASE_URL . '/dashboard.php'));
                exit;
            }
        } else {
            $error = 'Incorrect email or password.';
        }

    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .demo-box {
            background: var(--accent-dim);
            border: 1px solid rgba(124,106,247,.3);
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 14px;
        }
        .demo-box .demo-label {
            font-size: 11px;
            color: var(--muted);
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .demo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid rgba(255,255,255,.05);
            font-size: 12px;
        }
        .demo-row:last-child { border-bottom: none; }
        .demo-role  { color: var(--accent-l); font-weight: 500; min-width: 70px; }
        .demo-email { color: var(--muted); }
        .demo-fill  {
            background: none; border: 1px solid rgba(124,106,247,.4);
            border-radius: 5px; padding: 3px 8px; font-size: 11px;
            color: var(--accent-l); cursor: pointer; font-family: inherit;
        }
        .demo-fill:hover { background: var(--accent-dim); }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box">

        <div class="auth-hero">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" 
                alt="StudyVerse Logo"
                class="logo-img">
            <h1>StudyVerse</h1>
            <p>YOUR UNIVERSE OF LEARNING</p>
        </div>

        <div class="card">
            <div class="tab-switcher">
                <a href="<?= BASE_URL ?>/login.php" class="tab-btn active">LOGIN</a>
                <a href="<?= BASE_URL ?>/register.php" class="tab-btn">REGISTER</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($flash = getFlash()): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>EMAIL</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="loginEmail"
                        placeholder="email@example.com" 
                        required
                        value="<?= e($_POST['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>PASSWORD</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="loginPassword"
                        placeholder="••••••••" 
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    LOGIN
                </button>

                <div style="text-align:center;margin-top:14px;">
                    <a href="<?= BASE_URL ?>/forgot.php" style="font-size:12px;color:var(--muted);">
                        FORGET?
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>

//settle

</html>