<?php
// main dashboard - student & lecturer
require_once 'config/db.php';
requireLogin();

// admin redirect to admin dashboard
if (currentUser()['role'] === 'Admin') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$user = currentUser();

// papers count based on user level
$stmt = $pdo->prepare("SELECT COUNT(*) FROM papers WHERE level = ? AND status = 'approved'");
$stmt->execute([$user['level']]);
$paperCount = $stmt->fetchColumn();

// total active rooms
$roomCount = $pdo->query("SELECT COUNT(*) FROM study_rooms WHERE is_active = 1")->fetchColumn();

// study hours & avg score
$hoursStmt = $pdo->prepare("SELECT COALESCE(SUM(study_hours), 0) FROM progress WHERE user_id = ?");
$hoursStmt->execute([$user['id']]);
$totalHours = number_format($hoursStmt->fetchColumn(), 1);

$scoreStmt = $pdo->prepare("SELECT COALESCE(AVG(score), 0) FROM progress WHERE user_id = ?");
$scoreStmt->execute([$user['id']]);
$avgScore = round($scoreStmt->fetchColumn());

// rooms joined by user
$joinedStmt = $pdo->prepare("SELECT COUNT(*) FROM room_members WHERE user_id = ?");
$joinedStmt->execute([$user['id']]);
$joinedRooms = $joinedStmt->fetchColumn();

// recent papers based on level
$recentStmt = $pdo->prepare("
    SELECT p.*, u.name AS uploader_name
    FROM papers p
    JOIN users u ON p.uploaded_by = u.id
    WHERE p.level = ? AND p.status = 'approved'
    ORDER BY p.created_at DESC
    LIMIT 4
");
$recentStmt->execute([$user['level']]);
$recentPapers = $recentStmt->fetchAll();

// active study rooms
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

// FIX: function name was 'type' — renamed to 'typeBadge'
function typeBadge($type) {
    return match($type) {
        'Past Year' => 'badge-accent',
        'Trial'     => 'badge-amber',
        default     => 'badge-teal',
    };
}

// level badge color for rooms
function levelBadge($level) {
    return match($level) {
        'University'       => 'badge-accent',
        'STPM'             => 'badge-amber',
        'Secondary School' => 'badge-teal',
        default            => 'badge-muted',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
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
            <h1>Welcome, <?= e($user['name']) ?> 👋</h1>
            <p><?= e($user['role']) ?> · <?= e($user['level']) ?></p>
        </div>

        <!-- stats row -->
        <div class="<?= $user['role'] === 'Student' ? 'grid-4' : 'grid-2' ?> mb-24">
            <div class="card stat-card">
                <div class="value" style="color:var(--accent-l)"><?= $paperCount ?></div>
                <div class="label">Papers Available</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--teal)"><?= $joinedRooms ?></div>
                <div class="label">Rooms Joined</div>
            </div>
            <?php if ($user['role'] === 'Student'): ?>
            <div class="card stat-card">
                <div class="value" style="color:var(--amber)"><?= $avgScore ?>%</div>
                <div class="label">Avg Score</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--success)"><?= $totalHours ?>h</div>
                <div class="label">Study Hours</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- unread messages alert -->
        <?php if ($unreadMsgs > 0): ?>
        <div class="alert alert-info mb-16">
            📬 You have <strong><?= $unreadMsgs ?></strong> unread message<?= $unreadMsgs > 1 ? 's' : '' ?>.
            <a href="<?= BASE_URL ?>/messages.php" style="margin-left:8px;">Read now →</a>
        </div>
        <?php endif; ?>

        <!-- two column -->
        <div class="grid-2">

            <!-- recent papers -->
            <div class="card">
                <div class="section-label">Recent Papers</div>
                <?php if (empty($recentPapers)): ?>
                    <p style="color:var(--muted);font-size:13px;">No papers available for your level yet.</p>
                <?php else: ?>
                    <?php foreach ($recentPapers as $p): ?>
                    <div class="paper-item" style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;">
                        <div class="flex-center gap-10" style="flex:1;">
                            <div class="paper-icon">📄</div>
                            <div>
                                <div style="font-weight:500;font-size:14px;color:var(--text);">
                                    <?= e($p['title']) ?>
                                </div>
                                <div style="font-size:12px;color:var(--muted);">
                                    <?= $p['year'] ?> · <?= e($p['uploader_name']) ?>
                                </div>
                            </div>
                        </div>
                        <span class="badge <?= typeBadge($p['type']) ?>">
                            <?= strtoupper(e($p['type'])) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?= BASE_URL ?>/papers.php"
                       style="font-size:12px;color:var(--accent);display:block;margin-top:10px;">
                        View all →
                    </a>
                <?php endif; ?>
            </div>

            <!-- active study rooms -->
            <div class="card">
                <div class="section-label">Active Study Rooms</div>
                <?php if (empty($rooms)): ?>
                    <p style="color:var(--muted);font-size:13px;">No active rooms.</p>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                    <div class="paper-item">
                        <div class="flex-center gap-10" style="flex:1;">
                            <!-- active dot -->
                            <div style="width:8px;height:8px;border-radius:50%;background:var(--teal);flex-shrink:0;"></div>
                            <div>
                                <!-- room name + level badge side by side -->
                                <div style="font-weight:500;font-size:14px;color:var(--text);">
                                    <?= e($r['name']) ?>
                                </div>
                                <div style="font-size:12px;color:var(--muted);">
                                    <?= $r['member_count'] ?> members · <?= e($r['level']) ?> · Owner : <?= e($r['owner_name']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?= BASE_URL ?>/studyroom.php"
                       style="font-size:12px;color:var(--accent);display:block;margin-top:10px;">
                        Join a room →
                    </a>
                <?php endif; ?>
            </div>

        </div><!-- end grid-2 -->

        <!-- quick actions -->
        <div class="card mt-16">
            <div class="section-label">Quick Actions</div>
            <div class="flex-center gap-10 flex-wrap">
                <a href="<?= BASE_URL ?>/papers.php"     class="btn btn-outline">📄 Browse Papers</a>
                <a href="<?= BASE_URL ?>/upload.php"     class="btn btn-outline">⬆ Upload Paper</a>
                <a href="<?= BASE_URL ?>/summarizer.php" class="btn btn-outline">✦ AI Summarizer</a>
                <a href="<?= BASE_URL ?>/studyroom.php"  class="btn btn-outline">⬡ Study Rooms</a>
                <?php if ($user['role'] === 'Student'): ?>
                <a href="<?= BASE_URL ?>/progress.php"   class="btn btn-outline">◈ My Progress</a>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>