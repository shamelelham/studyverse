<?php
require_once 'config/db.php';
requireLogin();
$user = currentUser();
$tab  = $_GET['tab'] ?? 'profile';

// fetch full user dari DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$dbUser = $stmt->fetch();

// update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $level = $_POST['level'] ?? $dbUser['level'];
    $bio   = trim($_POST['bio']   ?? '');

    if (!$name || !$email) {
        setFlash('danger', 'Please fill in your name and email.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('danger', 'Invalid email format.');
    } else {
        // check email dah digunakan user lain
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $chk->execute([$email, $user['id']]);
        if ($chk->fetch()) {
            setFlash('danger', 'This email has already been used.');
        } else {
            $pdo->prepare("UPDATE users SET name=?, email=?, level=?, bio=? WHERE id=?")
                ->execute([$name, $email, $level, $bio, $user['id']]);

            // update session
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_level'] = $level;

            setFlash('success', 'Profile successfully updated!');
        }
    }
    redirect('/profile.php?tab=profile');
}

// ── changa password ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_pwd'] ?? '';
    $new     = $_POST['new_pwd']     ?? '';
    $confirm = $_POST['confirm_pwd'] ?? '';

    if ($current !== $dbUser['password']) {
        setFlash('danger', 'The current password is incorrect.');
    } elseif (strlen($new) < 6) {
        setFlash('danger', 'The new password must be at least 6 characters long.');
    } elseif ($new !== $confirm) {
        setFlash('danger', 'The new password does not match.');
    } else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")
            ->execute([$new, $user['id']]);
        setFlash('success', 'Password successfully changed!');
    }
    redirect('/profile.php?tab=password');
}

$levels    = ['Primary School', 'Secondary School', 'STPM', 'University'];
$roleColor = match($dbUser['role']) { 'Admin' => 'avatar-danger', 'Lecturer' => 'avatar-amber', default => 'avatar-accent' };

// refetch updated user
$stmt->execute([$user['id']]);
$dbUser = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header"><h1>ACCOUNT ◎</h1></div>

        <!-- tabs -->
        <div class="flex-center gap-8 mb-20">
            <a href="?tab=profile"  class="btn <?= $tab==='profile' ?'btn-outline':'btn-ghost' ?>">PROFILE</a>
            <a href="?tab=password" class="btn <?= $tab==='password'?'btn-outline':'btn-ghost' ?>">PASSWORD</a>
        </div>

        <?php showFlash(); ?>

        <?php if ($tab === 'profile'): ?>
        <!-- edit profile-->
        <div class="card" style="max-width:600px;">
            <!-- avatar & info -->
            <div class="flex-center gap-16 mb-24">
                <div class="avatar avatar-lg <?= $roleColor ?>">
                    <?= strtoupper(substr($dbUser['name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight:600;font-size:16px;color:var(--text);"><?= e($dbUser['name']) ?></div>
                    <div style="color:var(--muted);font-size:13px;"><?= e($dbUser['role']) ?> · <?= e($dbUser['level']) ?></div>
                    <div style="color:var(--muted);font-size:11px;margin-top:2px;">
                        Joined <?= date('d M Y', strtotime($dbUser['created_at'])) ?>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="update_profile" value="1">

                <div class="form-group">
                    <label>FULL NAME</label>
                    <input type="text" name="name" value="<?= e($dbUser['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>EMAIL</label>
                    <input type="email" name="email" value="<?= e($dbUser['email']) ?>" required>
                </div>

                <?php if ($dbUser['role'] === 'Student'): ?>
                <div class="form-group">
                    <label>LEVEL <span style="font-size:11px;color:var(--muted);">(changing this will change the papers displayed)</span></label>
                    <select name="level">
                        <?php foreach ($levels as $l): ?>
                        <option value="<?= $l ?>" <?= $dbUser['level']===$l?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>BIO (optional)</label>
                    <textarea name="bio" rows="3"
                              placeholder="Tell us a about yourself..."><?= e($dbUser['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">SAVE</button>
            </form>
        </div>

        <?php elseif ($tab === 'password'): ?>
        <!-- change password -->
        <div class="card" style="max-width:480px;">
            <form method="POST">
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label>CURRENT PASSWORD</label>
                    <input type="password" name="current_pwd" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>NEW PASSWORD</label>
                    <input type="password" name="new_pwd" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>CONFIRM NEW PASSWORD</label>
                    <input type="password" name="confirm_pwd" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary">UPDATE</button>
            </form>
        </div>
        <?php endif; ?>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>