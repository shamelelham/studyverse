<?php
//main dashboard - student & lecture
require_once 'config/db.php';
requireLogin();

//admin kene redirect ke admin punye dashboard
if (currentUser()['role'] === 'Admin') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$user = currentUser();

// part data
// papers ikut level user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE level = ? AND status = 'approved'");
$stmt->execute([$user['level']]);
$paperCount = $stmt->fetchColumn();

// total room active
$roomCount = $pdo->query("SELECT COUNT(*) FROM study_rooms WHERE is_active = 1")->fetchColumn();

//study hours & avg score (student)
$hoursStmt = $pdo->prepare("SELECT COALESCE(SUM(study_hours), 0) FROM progress WHERE user_id = ?");
$hoursStmt->execute([$user['id']]);
$totalHours = number_format($hoursStmt->fetchColumn(), 1);

$scoreStmt = $pdo->prepare("SELECT COALESCE(AVG(score), 0) FROM progress WHERE user_id = ?");
$scoreStmt->execute([$user['id']]);
$avgScore = round($scoreStmt->fetchColumn());

// rooms yg user join
$joinedStmt = $pdo->prepare("SELECT COUNT(*) FROM room_members WHERE user_id = ?");
$joinedStmt->execute([$user['id']]);
$joinedRooms = $joinedStmt->fetchColumn();

// recent paper based on level
$recentStmt = $pdo->prepare("
    SELECT P.*, u.name AS uploader_name
    FROM papers p
    JOIN users u ON p.uploaded_by = u.id
    WHERE p.level = ? AND p.status = 'approved'
    ORDER BY p.created_at DESC
    LIMIT 4
");

$recentStmt->execute([$user['level']]);
$recentPapers = $recentStmt->fetchAll();

// active rooms study
$rooms = $pdo->query(" 
    SELECT r.*, u.name AS owner_name,
    (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) AS member_count
    FROM study_rooms r 
    JOIN users u ON r.owner_id = u.id
    WHERE r.is_active = 1
    ORDER BY r.created_at DESC
    LIMIT 4
")->fetchAll();

// unread messages count
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unreadStmt->execute([$user['id']]);
$unreadMsgs = $unreadStmt->fetchColumn();

// helper : paper type badge class
function type($type) {
    return match($type) {
        'Past Year' => 'badge-accent',
        'Trial' => 'badge-amber',
        default => 'badge-teal',
    };
}
?>

<!DOCTYPE html>
<html lang="ms">
    <head>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    </head>
    <body>
        <div class="layout">

    <!-- sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- main content -->
    <main class="main-content">

        <!-- page header -->
        <div class="page-header">
            <h1>WELCOME! <?= e($user['name']) ?></h1>
            <p><?= e($user['role']) ?> · <?= e($user['level']) ?></p>
        </div>

        <!-- stats row -->
        <div class="grid-4 mb-24">
            <div class="card stat-card">
                <div class="value" style="color:var(--accent-l)"><?= $paperCount ?></div>
                <div class="label">PAPERS AVAILABLE</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--teal)"><?= $joinedRooms ?></div>
                <div class="label">ROOMS JOINED</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--amber)"><?= $avgScore ?>%</div>
                <div class="label">AVG SCORE</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--success)"><?= $totalHours ?>h</div>
                <div class="label">STUDY HOURS</div>
            </div>
        </div>

        <!-- unread messages alert -->
        <?php if ($unreadMsgs > 0): ?>
        <div class="alert alert-info mb-16">
            📬 YOU HAVE <strong><?= $unreadMsgs ?></strong>UNREAD MESSAGES.
            <a href="<?= BASE_URL ?>/messages.php" style="margin-left:8px;">READ NOW →</a>
        </div>
        <?php endif; ?>

        <!-- two column -->
        <div class="grid-2">

            <!-- recent papers -->
            <div class="card">
                <div class="section-label">RECENT PAPERS</div>
                <?php if (empty($recentPapers)): ?>
                    <p style="color:var(--muted);font-size:13px;">THERE NO PAPERS FOR YOUR LEVEL YET.</p>
                <?php else: ?>
                    <?php foreach ($recentPapers as $p): ?>
                    <div class="paper-item">
                        <div class="flex-center gap-10">
                            <div class="paper-icon">📄</div>
                            <div>
                                <div class="paper-title"><?= e($p['subject']) ?></div>
                                <div class="paper-meta"><?= $p['year'] ?> · <?= e($p['uploader_name']) ?></div>
                            </div>
                        </div>
                        <span class="badge <?= typeBadge($p['type']) ?>"><?= e($p['type']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?= BASE_URL ?>/papers.php" style="font-size:12px;color:var(--accent);display:block;margin-top:10px;">VIEW ALL →</a>
                <?php endif; ?>
            </div>

            <!-- active study rooms -->
            <div class="card">
                <div class="section-label">ACTIVE STUDY ROOMS</div>
                <?php if (empty($rooms)): ?>
                    <p style="color:var(--muted);font-size:13px;">NO ACTIVE ROOMS</p>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                    <div class="paper-item">
                        <div class="flex-center gap-10">
                            <div class="room-dot" style="background:var(--teal)"></div>
                            <div>
                                <div class="paper-title"><?= e($r['name']) ?></div>
                                <div class="paper-meta"><?= $r['member_count'] ?> MEMBERS . <?= e($r['owner_name']) ?></div>
                            </div>
                        </div>
                        <span class="badge badge-teal"><?= e($r['level']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?= BASE_URL ?>/studyroom.php" style="font-size:12px;color:var(--accent);display:block;margin-top:10px;">JOIN →</a>
                <?php endif; ?>
            </div>

        </div><!-- end grid 2 -->

        <!-- quick actions -->
        <div class="card mt-16">
            <div class="section-label">QUICK ACTIONS</div>
            <div class="flex-center gap-10 flex-wrap">
                <a href="<?= BASE_URL ?>/papers.php"     class="btn btn-outline">📄 BROWSE PAPERS</a>
                <a href="<?= BASE_URL ?>/upload.php"     class="btn btn-outline">⬆ UPLOAD PAPER</a>
                <a href="<?= BASE_URL ?>/summarizer.php" class="btn btn-outline">✦ AI SUMMARIZER</a>
                <a href="<?= BASE_URL ?>/studyroom.php"  class="btn btn-outline">⬡ STUDY ROOMS</a>
                <?php if ($user['role'] === 'Student'): ?>
                <a href="<?= BASE_URL ?>/progress.php"   class="btn btn-outline">◈ MY PROGRESS</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>