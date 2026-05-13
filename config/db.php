<?php
// database setting
define('DB_HOST', 'localhost');
define('DB_NAME', 'studyverse_db');
define('DB_USER', 'root');
define('DB_PASS', '');          //xampp default = kosong

// AI API KEY BELOM ADE

// base url
define('BASE_URL', 'http://localhost/STUDYVERSE');

// database & web conection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",         // ;charset=utf8mb4 = support emoji
        DB_USER,
        DB_PASS,
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:20px;background:#1a0000;color:#ff6b6b;border:1px solid #ff6b6b;border-radius:8px;margin:20px;'>
        <strong>Database Connection Failed</strong><br><br>
        " . $e->getMessage() . "<br><br>
        <small>Make sure XAMPP MySQL has started and the 'studyverse_db' database has been created.</small>
    </div>");
}

// session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// help functions
// check user dah login?
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// user kene login -> loginpage kalau belom
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// admin kene login -> rediret kalau bukan admin, pakai kat semua admin
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'Admin') {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// user data login from session
// return array - id, name, email, role, level
function currentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
        'level' => $_SESSION['user_level'] ?? '',
    ];
}

// sanitize output xss attack
// guna bila output data dari database ke HTML
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// rediret helper
function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

// flash message
// setFlash ('success', paper berjaya upload)
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

//clear flash message
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
    return $flash;
    }
    return null;
}

// display flash message / letak kat page lepas sidebar
function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$type}'>" .e($flash['message']) . "</div>";
    }
}
?>