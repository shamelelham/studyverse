<?php
require_once 'config/db.php';
requireLogin();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';
$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// create room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $name  = trim($_POST['room_name']  ?? '');
    $level = $_POST['room_level'] ?? 'Secondary School';
    $type  = $_POST['room_type']  ?? 'Public';
    $desc  = trim($_POST['room_desc']  ?? '');

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO study_rooms (name, description, level, type, owner_id) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $desc, $level, $type, $user['id']]);
        $newId = $pdo->lastInsertId();
        // auto-join creator
        $pdo->prepare("INSERT IGNORE INTO room_members (room_id, user_id) VALUES (?,?)")->execute([$newId, $user['id']]);
        header("Location: " . BASE_URL . "/studyroom.php?action=chat&id=$newId");
        exit;
    }
}

// join room
if ($action === 'join' && $roomId) {
    $pdo->prepare("INSERT IGNORE INTO room_members (room_id, user_id) VALUES (?,?)")->execute([$roomId, $user['id']]);
    header("Location: " . BASE_URL . "/studyroom.php?action=chat&id=$roomId");
    exit;
}

// send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_msg']) && $roomId) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg) {
        $stmt = $pdo->prepare("INSERT INTO room_messages (room_id, user_id, message) VALUES (?,?,?)");
        $stmt->execute([$roomId, $user['id'], $msg]);
    }
    header("Location: " . BASE_URL . "/studyroom.php?action=chat&id=$roomId");
    exit;
}

// delete room (owner or admin only) 
if (isset($_GET['delete_room']) && $roomId) {
    $check = $pdo->prepare("SELECT owner_id FROM study_rooms WHERE id=?");
    $check->execute([$roomId]);
    $room  = $check->fetch();
    if ($room && ($room['owner_id'] == $user['id'] || $user['role'] === 'Admin')) {
        $pdo->prepare("DELETE FROM study_rooms WHERE id=?")->execute([$roomId]);
    }
    header("Location: " . BASE_URL . "/studyroom.php");
    exit;
}

// load chat data
$room     = null;
$chatMsgs = [];
$mCount   = 0;
$isMember = false;

if ($action === 'chat' && $roomId) {
    $rs = $pdo->prepare("SELECT r.*, u.name AS owner_name FROM study_rooms r JOIN users u ON r.owner_id = u.id WHERE r.id = ?");
    $rs->execute([$roomId]);
    $room = $rs->fetch();

    if ($room) {
        $ms = $pdo->prepare("SELECT m.*, u.name AS sender_name, u.role AS sender_role FROM room_messages m JOIN users u ON m.user_id = u.id WHERE m.room_id = ? ORDER BY m.created_at ASC LIMIT 100");
        $ms->execute([$roomId]);
        $chatMsgs = $ms->fetchAll();

        $mc = $pdo->prepare("SELECT COUNT(*) FROM room_members WHERE room_id=?");
        $mc->execute([$roomId]);
        $mCount = $mc->fetchColumn();

        $im = $pdo->prepare("SELECT id FROM room_members WHERE room_id=? AND user_id=?");
        $im->execute([$roomId, $user['id']]);
        $isMember = (bool)$im->fetch();
    }
}

// room list
$rooms = $pdo->query("
    SELECT r.*, u.name AS owner_name,
    (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) AS mc
    FROM study_rooms r
    JOIN users u ON r.owner_id = u.id
    WHERE r.is_active = 1
    ORDER BY r.created_at DESC
")->fetchAll();

// total rooms joined by user
$myRoomsStmt = $pdo->prepare("SELECT COUNT(*) FROM room_members WHERE user_id=?");
$myRoomsStmt->execute([$user['id']]);
$myRoomsCount = $myRoomsStmt->fetchColumn();

$levels = ['Primary School', 'Secondary School', 'STPM', 'University'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Rooms — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

    <?php if ($action === 'chat' && $room): ?>
    <!-- chat view -->
    <div class="flex-between mb-20">
        <div class="flex-center gap-12">
            <a href="<?= BASE_URL ?>/studyroom.php" class="btn btn-ghost btn-sm">←  Back</a>
            <div>
                <h1 style="font-size:18px;margin:0;"><?= e($room['name']) ?></h1>
                <span style="color:var(--muted);font-size:12px;">
                    <?= $mCount ?> members · <?= e($room['level']) ?> · Owner : <?= e($room['owner_name']) ?>
                </span>
            </div>
        </div>
        <div class="flex-center gap-8">
            <?php if ($room['owner_id'] == $user['id'] || $user['role'] === 'Admin'): ?>
                <span class="badge badge-amber">Room Owner</span>
                <a href="?action=chat&id=<?= $roomId ?>&delete_room=1"
                   class="btn btn-danger btn-sm"
                   data-confirm="Delete room '<?= e($room['name']) ?>'?">
                   🗑 Delete Room
                </a>
            <?php endif; ?>
            <?php if (!$isMember): ?>
                <a href="?action=join&id=<?= $roomId ?>" class="btn btn-primary btn-sm">Join Room</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- chat messages -->
    <div class="chat-area" id="chatArea">
        <?php if (empty($chatMsgs)): ?>
            <div style="text-align:center;color:var(--muted);padding:40px;font-size:13px;">
                No messages yet. Start the conversation! 👋
            </div>
        <?php else: ?>
            <?php foreach ($chatMsgs as $m):
                $isMe = $m['user_id'] == $user['id'];
            ?>
            <div class="msg-bubble <?= $isMe ? 'me' : 'other' ?>">
                <?php if (!$isMe): ?>
                <div class="msg-meta">
                    <?php if ($m['sender_role'] === 'Lecturer'): ?>
                        <span class="badge badge-amber" style="margin-right:4px;">Lecturer</span>
                    <?php endif; ?>
                    <?= e($m['sender_name']) ?> · <?= date('H:i', strtotime($m['created_at'])) ?>
                </div>
                <?php endif; ?>
                <div class="msg-text <?= $isMe ? 'me' : ($m['sender_role']==='Lecturer' ? 'lecturer' : 'other') ?>">
                    <?= nl2br(e($m['message'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- send message form -->
    <form method="POST" id="chatForm" class="chat-input-bar">
        <input type="hidden" name="send_msg" value="1">
        <input type="text" id="chatInput" name="message"
               placeholder="Type a message... (Enter to send)"
               autocomplete="off" required style="flex:1;">
        <button type="submit" class="btn btn-primary">Send</button>
    </form>

    <?php else: ?>
    <!-- room list -->
    <div class="flex-between mb-20">
        <div class="page-header" style="margin:0;">
            <h1>Study Rooms ⬡</h1>
            <p>You have joined <?= $myRoomsCount ?> room<?= $myRoomsCount != 1 ? 's' : '' ?> · <?= count($rooms) ?> active rooms</p>
        </div>
        <button class="btn btn-outline" onclick="toggleForm('createRoomForm')">+ Create Room</button>
    </div>

    <!-- create room form (hidden by default) -->
    <div id="createRoomForm" class="card mb-20" style="display:none;">
        <div class="section-label">Create New Study Room</div>
        <form method="POST">
            <input type="hidden" name="create_room" value="1">
            <div class="form-group">
                <label>Room Name *</label>
                <input type="text" name="room_name" placeholder="e.g. SPM Chemistry Study Group" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Level</label>
                    <select name="room_level">
                        <?php foreach ($levels as $l): ?>
                        <option value="<?= $l ?>" <?= $user['level']===$l?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="room_type">
                        <option value="Public">Public</option>
                        <option value="Private">Private</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <textarea name="room_desc" rows="2" placeholder="What is this room about?"></textarea>
            </div>
            <div class="flex-center gap-10">
                <button type="submit" class="btn btn-primary">Create Room</button>
                <button type="button" class="btn btn-ghost" onclick="toggleForm('createRoomForm')">Cancel</button>
            </div>
        </form>
    </div>

    <!-- room list -->
    <?php if (empty($rooms)): ?>
        <div class="card loading">No study rooms yet. Create the first one!</div>
    <?php else: ?>
        <?php
        // get all rooms user has joined — untuk check joined status
        $joinedStmt = $pdo->prepare("SELECT room_id FROM room_members WHERE user_id = ?");
        $joinedStmt->execute([$user['id']]);
        $joinedRooms = array_column($joinedStmt->fetchAll(), 'room_id');
        ?>
        <?php foreach ($rooms as $r):
            $hasJoined = in_array($r['id'], $joinedRooms);
        ?>
        <div class="room-item">
            <div class="flex-center gap-12">
                <div class="room-dot" style="background:<?= $r['is_active'] ? 'var(--teal)' : 'var(--border)' ?>"></div>
                <div>
                    <div style="font-weight:500;font-size:14px;color:var(--text);"><?= e($r['name']) ?></div>
                    <div style="font-size:12px;color:var(--muted);">
                        <?= $r['mc'] ?> members · <?= e($r['level']) ?> · Owner : <?= e($r['owner_name']) ?>
                    </div>
                </div>
            </div>
            <div class="flex-center gap-8">
                <span class="badge <?= $r['type']==='Public' ? 'badge-teal' : 'badge-amber' ?>"><?= e($r['type']) ?></span>
                <?php if (!$r['is_active']): ?>
                    <!-- room offline -->
                    <span class="btn btn-ghost btn-sm" style="cursor:default;">Offline</span>
                <?php elseif ($hasJoined): ?>
                    <!-- already joined — show enter button -->
                    <a href="?action=chat&id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">Enter →</a>
                <?php else: ?>
                    <!-- not joined yet — show join button -->
                    <a href="?action=join&id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">Join</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; ?>

    </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>