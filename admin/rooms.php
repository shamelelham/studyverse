<?php
require_once '../config/db.php';
requireAdmin();

// delete room
if (isset($_GET['delete_room'])) {
    $rid = (int)$_GET['delete_room'];
    $pdo->prepare("DELETE FROM study_rooms WHERE id = ?")->execute([$rid]);
    setFlash('success', 'Room deleted successfully.');
    header('Location: ' . BASE_URL . '/admin/rooms.php');
    exit;
}

// delete single message
if (isset($_GET['delete_msg'])) {
    $mid = (int)$_GET['delete_msg'];
    $rid = (int)($_GET['room_id'] ?? 0);
    $pdo->prepare("DELETE FROM room_messages WHERE id = ?")->execute([$mid]);
    setFlash('success', 'Message deleted successfully.');
    header('Location: ' . BASE_URL . "/admin/rooms.php?view=$rid");
    exit;
}

// toggle active / inactive
if (isset($_GET['toggle'])) {
    $rid = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE study_rooms SET is_active = NOT is_active WHERE id = ?")->execute([$rid]);
    header('Location: ' . BASE_URL . '/admin/rooms.php');
    exit;
}

// view room messages
$viewRoom = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$roomInfo = null;
$roomMsgs = [];

if ($viewRoom) {
    $rs = $pdo->prepare("SELECT r.*, u.name AS owner_name FROM study_rooms r JOIN users u ON r.owner_id = u.id WHERE r.id = ?");
    $rs->execute([$viewRoom]);
    $roomInfo = $rs->fetch();

    if ($roomInfo) {
        $ms = $pdo->prepare("
            SELECT m.*, u.name AS sender, u.role AS sender_role
            FROM room_messages m
            JOIN users u ON m.user_id = u.id
            WHERE m.room_id = ?
            ORDER BY m.created_at DESC
            LIMIT 100
        ");
        $ms->execute([$viewRoom]);
        $roomMsgs = $ms->fetchAll();
    }
}

// fetch all rooms
$rooms = $pdo->query("
    SELECT r.*, u.name AS owner_name,
    (SELECT COUNT(*) FROM room_members  WHERE room_id = r.id) AS mc,
    (SELECT COUNT(*) FROM room_messages WHERE room_id = r.id) AS msgc
    FROM study_rooms r
    JOIN users u ON r.owner_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll();

$activeCount = count(array_filter($rooms, fn($r) => $r['is_active']));
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Rooms — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once '../includes/sidebar.php'; ?>
    <main class="main-content">

    <?php showFlash(); ?>

    <?php if ($viewRoom && $roomInfo): ?>
    <!-- view room messages-->
    <div class="flex-between mb-20">
        <div class="flex-center gap-12">
            <a href="<?= BASE_URL ?>/admin/rooms.php" class="btn btn-ghost btn-sm">← Back</a>
            <div>
                <h1 style="font-size:18px;margin:0;"><?= e($roomInfo['name']) ?></h1>
                <span style="color:var(--muted);font-size:12px;">
                    <?= e($roomInfo['level']) ?> · <?= $roomInfo['type'] ?>
                    · Owner: <?= e($roomInfo['owner_name']) ?>
                    · <?= count($roomMsgs) ?> messages
                </span>
            </div>
        </div>
        <a href="?delete_room=<?= $viewRoom ?>"
           class="btn btn-danger btn-sm"
           data-confirm="Delete room '<?= e($roomInfo['name']) ?>'?">
            🗑 Delete Room
        </a>
    </div>

    <?php if (empty($roomMsgs)): ?>
        <div class="card loading">No messages in this room.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($roomMsgs as $m): ?>
            <div class="card flex-between" style="padding:10px 16px;">
                <div style="flex:1;">
                    <div class="flex-center gap-8 mb-4">
                        <div class="avatar avatar-sm <?= $m['sender_role']==='Lecturer'?'avatar-amber':'avatar-accent' ?>">
                            <?= strtoupper(substr($m['sender'],0,1)) ?>
                        </div>
                        <span style="font-weight:500;font-size:13px;color:var(--text);"><?= e($m['sender']) ?></span>
                        <?php if ($m['sender_role']==='Lecturer'): ?>
                            <span class="badge badge-amber">Lecturer</span>
                        <?php endif; ?>
                        <span style="color:var(--muted);font-size:11px;">
                            <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                        </span>
                    </div>
                    <div style="color:var(--muted);font-size:13px;padding-left:36px;">
                        <?= nl2br(e($m['message'])) ?>
                    </div>
                </div>
                <a href="?delete_msg=<?= $m['id'] ?>&room_id=<?= $viewRoom ?>"
                   class="btn btn-danger btn-sm"
                  data-confirm="Delete message?"
                   style="flex-shrink:0;margin-left:12px;">
                    🗑
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- room list -->
    <div class="page-header">
        <h1>Monitor Rooms ⬡</h1>
        <p><?= count($rooms) ?> rooms · <?= $activeCount ?> active</p>
    </div>

    <?php if (empty($rooms)): ?>
        <div class="card loading">No study rooms available.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($rooms as $r): ?>
            <div class="room-item">
                <div class="flex-center gap-12" style="flex:1;">
                    <div class="room-dot"
                         style="background:<?= $r['is_active']?'var(--teal)':'var(--border)' ?>">
                    </div>
                    <div>
                        <div style="font-weight:500;font-size:14px;color:var(--text);">
                            <?= e($r['name']) ?>
                        </div>
                        <div style="font-size:12px;color:var(--muted);">
                            <?= e($r['level']) ?> · <?= $r['type'] ?>
                            · Owner: <?= e($r['owner_name']) ?>
                            · <?= $r['mc'] ?> members
                            · <?= $r['msgc'] ?> messages
                            · <?= date('d M Y', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <div class="flex-center gap-8" style="flex-shrink:0;">
                    <span class="badge <?= $r['is_active']?'badge-teal':'badge-muted' ?>">
                        <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                    <a href="?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm">
                        View Messages
                    </a>
                    <a href="?toggle=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">
                        <?= $r['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </a>
                    <a href="?delete_room=<?= $r['id'] ?>"
                       class="btn btn-danger btn-sm"
                       data-confirm="Delete room '<?= e($r['name']) ?>'?">
                        🗑
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>