<?php

require_once '../config/db.php';
requireAdmin();
$user = currentUser();

// fetch stats
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'Admin'")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Student'")->fetchColumn();
$totalLecturers= $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Lecturer'")->fetchColumn();
$bannedUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn();
$totalPapers   = $pdo->query("SELECT COUNT(*) FROM papers")->fetchColumn();
$pendingPapers = $pdo->query("SELECT COUNT(*) FROM papers WHERE status = 'pending'")->fetchColumn();
$approvedPapers= $pdo->query("SELECT COUNT(*) FROM papers WHERE status = 'approved'")->fetchColumn();
$activeRooms   = $pdo->query("SELECT COUNT(*) FROM study_rooms WHERE is_active = 1")->fetchColumn();
$totalRooms    = $pdo->query("SELECT COUNT(*) FROM study_rooms")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// recent registrations
$recentUsers = $pdo->query("
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT 6
")->fetchAll();

// recent paper submissions
$recentPapers = $pdo->query("
    SELECT p.*, u.name AS uploader
    FROM papers p
    JOIN users u ON p.uploaded_by = u.id
    ORDER BY p.created_at DESC
    LIMIT 6
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once '../includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>ADMIN DASHBOARD 🛡️</h1>
            <p>System Analytics & Overview · Welcome, <?= e($user['name']) ?></p>
        </div>

        <?php showFlash(); ?>

        <!-- stats row 1 -->
        <div class="grid-4 mb-16">
            <div class="card stat-card">
                <div class="value" style="color:var(--accent-l)"><?= $totalUsers ?></div>
                <div class="label">TOTAL USERS</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--teal)"><?= $totalStudents ?></div>
                <div class="label">STUDENTS</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--amber)"><?= $totalLecturers ?></div>
                <div class="label">LECTURERS</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--danger)"><?= $bannedUsers ?></div>
                <div class="label">BANNED USERS</div>
            </div>
        </div>

        <!-- stats row 2 -->
        <div class="grid-4 mb-24">
            <div class="card stat-card">
                <div class="value" style="color:var(--accent-l)"><?= $totalPapers ?></div>
                <div class="label">TOTAL PAPERS</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--amber)"><?= $pendingPapers ?></div>
                <div class="label">PENDING APPROVAL</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--teal)"><?= $activeRooms ?></div>
                <div class="label">ACTIVE ROOMS</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--success)"><?= $totalMessages ?></div>
                <div class="label">TOTAL MESSAGES</div>
            </div>
        </div>

        <!-- quick cctions -->
        <div class="card mb-24">
            <div class="section-label">QUICK ACTIONS</div>
            <div class="flex-center gap-10 flex-wrap">
                <a href="<?= BASE_URL ?>/admin/papers.php?filter=pending" class="btn btn-primary">
                    ⚡ REVIEW PENDINGg (<?= $pendingPapers ?>)
                </a>
                <a href="<?= BASE_URL ?>/admin/users.php"  class="btn btn-outline">👥 MANAGE USERS</a>
                <a href="<?= BASE_URL ?>/admin/rooms.php"  class="btn btn-ghost">⬡ MONITOR ROOMS</a>
                <a href="<?= BASE_URL ?>/admin/papers.php" class="btn btn-ghost">📄 ALL PAPERS</a>
            </div>
        </div>

        <!-- two column -->
        <div class="grid-2">

            <!-- recent users -->
            <div class="card">
                <div class="flex-between mb-12">
                    <div class="section-label" style="margin:0;">RECENT REGISTRATIONS</div>
                    <a href="<?= BASE_URL ?>/admin/users.php" style="font-size:12px;color:var(--accent);">VIEW ALL →</a>
                </div>
                <?php foreach ($recentUsers as $u): ?>
                <div class="paper-item">
                    <div class="flex-center gap-10">
                        <div class="avatar avatar-sm <?= $u['role']==='Lecturer'?'avatar-amber':'avatar-accent' ?>">
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="paper-title"><?= e($u['name']) ?></div>
                            <div class="paper-meta"><?= e($u['role']) ?> · <?= e($u['level']) ?></div>
                        </div>
                    </div>
                    <span class="badge <?= $u['is_active']?'badge-teal':'badge-danger' ?>">
                        <?= $u['is_active'] ? 'Active' : 'Banned' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Papers -->
            <div class="card">
                <div class="flex-between mb-12">
                    <div class="section-label" style="margin:0;">RECENT SUBMISSIONS</div>
                    <a href="<?= BASE_URL ?>/admin/papers.php" style="font-size:12px;color:var(--accent);">VIEW ALL →</a>
                </div>
                <?php foreach ($recentPapers as $p):
                    $sc = match($p['status']) { 'approved'=>'badge-teal','rejected'=>'badge-danger',default=>'badge-amber' };
                ?>
                <div class="paper-item">
                    <div>
                        <div class="paper-title"><?= e($p['subject']) ?></div>
                        <div class="paper-meta"><?= e($p['level']) ?> · by <?= e($p['uploader']) ?></div>
                    </div>
                    <span class="badge <?= $sc ?>"><?= ucfirst($p['status']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>