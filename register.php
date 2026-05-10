<?php
require_once 'config/db.php';

//redirect if dah login
if (isLoggedIn()) {
  header('Location: ' . BASE_URL . '/dashboard.php');
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $confirm = trim($_POST['confirm'] ?? '');
  $role = $_POST['role'] ?? 'Student';
  $level = $_POST['level'] ?? 'Secondary School';

  // validate
  if (!$name || !$email || !$password || !$confirm) {
    $error = 'Please fill in all fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email format.';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
  } elseif ($password !== $confirm) {
    $error = 'Password does not match';
  } elseif (!in_array($role, ['Student', 'Lecturer'])) {
    $error = 'Invalid role.';
  } else {
    //check email dah ade belom
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $chk ->execute([$email]);
    if ($chk->fetch()) {
      $error = 'This email is already registered. Please log in.';
    } else {
      // insert user
      $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, level) VALUES (?,?,?,?,?)");
      $stmt->execute([$name, $email, $password, $role, $level]);
      $success = 'Account created successfully! Please log in.';
    }
  }
}

$level = ['PRIMARY SCHOOL', 'SECONDARY SCHOOL', 'STPM', 'UNIVERSITY'];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box">

        <!-- header -->
        <div class="auth-hero">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" 
                  alt="StudyVerse Logo"
                  class="logo-img">
            <h1>StudyVerse</h1>
            <p>YOUR UNIVERSE OF LEARNING</p>
        </div>

        <div class="card">
            <div class="tab-switcher">
                <a href="<?= BASE_URL ?>/login.php" class="tab-btn">LOGIN</a>
                <a href="<?= BASE_URL ?>/register.php" class="tab-btn active">REGISTER</a>
            </div>

            <?php if ($error):   ?><div class="alert alert-danger"><?=  e($error)   ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

            <?php if ($success): ?>
                <!-- success state -->
                <div style="text-align:center;padding:10px 0;">
                    <!-- <div style="font-size:36px;margin-bottom:12px;">✅</div> -->
                    <p style="color:var(--muted);font-size:13px;margin-bottom:16px;">
                        YOUR ACCOUNT HAS BEEN SUCESSFULLY CREATED. GO LOGIN NOW!
                    </p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary w-full">GO TO LOGIN.</a>
                </div>

            <?php else: ?>
                <!-- register form -->
                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <label>FULL NAME *</label>
                        <input type="text" name="name"
                               placeholder="YOUR FULL NAME"
                               value="<?= e($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>EMAIL *</label>
                        <input type="email" name="email"
                               placeholder="email@example.com"
                               value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>PASSWORD * <span style="font-size:11px;color:var(--muted);">(min 6 characters)</span></label>
                        <input type="password" name="password"
                               placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label>CONFRIM PASSWORD *</label>
                        <input type="password" name="confirm"
                               placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label>REGISTER AS *</label>
                        <select name="role" id="roleSelect" onchange="toggleLevel()">
                            <option value="Student"
                                <?= ($_POST['role'] ?? '') === 'Student'  ? 'selected' : '' ?>>
                                STUDENT
                            </option>
                            <option value="Lecturer"
                                <?= ($_POST['role'] ?? '') === 'Lecturer' ? 'selected' : '' ?>>
                                LECTURER
                            </option>
                        </select>
                    </div>

                    <!-- level — student je -->
                    <div class="form-group" id="levelGroup">
                        <label>STUDY LEVEL *</label>
                        <select name="level">
                            <?php foreach ($level as $l): ?>
                            <option value="<?= $l ?>"
                                <?= ($_POST['level'] ?? 'Secondary School') === $l ? 'selected' : '' ?>>
                                <?= $l ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">CREATE ACCOUNT</button>

                    <p style="text-align:center;font-size:12px;color:var(--muted);margin-top:12px;">
                        ALREADY HAVE ACCOUNT?
                        <a href="<?= BASE_URL ?>/login.php">LOGIN</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
<!-- hide level dropdown kalau lecturer dipilih -->
function toggleLevel() {
    const role  = document.getElementById('roleSelect').value;
    const group = document.getElementById('levelGroup');
    group.style.display = role === 'Student' ? '' : 'none';
}

// run on load in case of POST error
toggleLevel();
</script>
</body>

//setlle

</html>

