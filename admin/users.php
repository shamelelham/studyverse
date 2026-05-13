<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

// ban / unban
if (isset($_GET['ban'])) {
    $uid = (int)$_GET['ban'];
    $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role != 'Admin'")->execute([$uid]);
    setFlash('success', 'User successfully banned.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}
if (isset($_GET['unban'])) {
    $uid = (int)$_GET['unban'];
    $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$uid]);
    setFlash('success', 'User berjaya di-unban.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = in_array($_POST['role'], ['Student','Lecturer']) ? $_POST['role'] : 'Student';
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role != 'Admin'")->execute([$role, $uid]);
    setFlash('success', 'Role successfully changed.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'All';

$sql    = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($filter !== 'All') { $sql .= " AND role = ?";  $params[] = $filter; }
if ($search)           { $sql .= " AND (name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql .= " ORDER BY created_at DESC";
$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'Admin'")->fetchColumn();
$banned     = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once '../includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>MANAGE USER 👥</h1>
            <p><?= $totalUsers ?> users · <?= $banned ?> BANNED</p>
        </div>

        <?php showFlash(); ?>

        <!-- search + filter -->
        <form method="GET" style="display:grid;grid-template-columns:1fr auto auto auto;gap:10px;margin-bottom:20px;">
            <input type="text" name="search" class="search-input"
                   placeholder="Search name or email..."
                   value="<?= e($search) ?>">
            <select name="filter">
                <?php foreach (['All','Student','Lecturer','Admin'] as $f): ?>
                <option value="<?= $f ?>" <?= $filter===$f?'selected':'' ?>><?= $f ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">SEARCH</button>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-ghost">RESET</a>
        </form>

        <!-- users table -->
        <div class="card" style="padding:0;overflow:hidden;">
            <table class="sv-table">
                <thead>
                    <tr>
                        <th>USER</th>
                        <th>ROLE</th>
                        <th>LEVEL</th>
                        <th>STATUS</th>
                        <th>JOINED</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">NO USER FOUND.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $avatarC = match($u['role']) { 'Admin'=>'avatar-danger','Lecturer'=>'avatar-amber',default=>'avatar-accent' };
                    ?>
                    <tr>
                        <!-- user info -->
                        <td>
                            <div class="flex-center gap-10">
                                <div class="avatar avatar-sm <?= $avatarC ?>">
                                    <?= strtoupper(substr($u['name'],0,1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:500;font-size:13px;"><?= e($u['name']) ?></div>
                                    <div style="color:var(--muted);font-size:11px;"><?= e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>

                        <!-- role (editable if not admin) -->
                        <td>
                            <?php if ($u['role'] === 'Admin'): ?>
                                <span class="badge badge-danger">ADMIN</span>
                            <?php else: ?>
                                <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="change_role" value="1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" style="padding:4px 8px;font-size:12px;width:auto;">
                                        <option value="Student"  <?= $u['role']==='Student' ?'selected':'' ?>>STUDENT</option>
                                        <option value="Lecturer" <?= $u['role']==='Lecturer'?'selected':'' ?>>LECTURER</option>
                                    </select>
                                    <button type="submit" class="btn btn-ghost btn-sm">SAVE</button>
                                </form>
                            <?php endif; ?>
                        </td>

                        <td style="color:var(--muted);font-size:12px;"><?= e($u['level']) ?></td>

                        <td>
                            <span class="badge <?= $u['is_active']?'badge-teal':'badge-danger' ?>">
                                <?= $u['is_active'] ? 'Active' : 'BANNED' ?>
                            </span>
                        </td>

                        <td style="color:var(--muted);font-size:12px;">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>

                        <!-- actions -->
                        <td>
                            <?php if ($u['role'] !== 'Admin'): ?>
                                <?php if ($u['is_active']): ?>
                                    <a href="?ban=<?= $u['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       data-confirm="Ban user <?= e($u['name']) ?>?">
                                        BAN
                                    </a>
                                <?php else: ?>
                                    <a href="?unban=<?= $u['id'] ?>" class="btn btn-teal btn-sm">UNBAND</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
