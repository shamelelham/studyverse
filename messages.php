<?php
require_once 'config/db.php';
requireLogin();
$user = currentUser();

$toId = isset($_GET['to']) ? (int)$_GET['to'] : 0;
$search = trim($_GET['search'] ?? '');

// send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send']) && $toId) {
    $msg = trim($_POST['message'] ?? '');

    if ($msg) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
        $stmt->execute([$user['id'], $toId, $msg]);

        $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")
            ->execute([$toId, $user['id']]);
    }

    header("Location: " . BASE_URL . "/messages.php?to=$toId");
    exit;
}

// contacts + search
$sql = "
    SELECT u.id, u.name, u.role,
    (SELECT COUNT(*) FROM messages 
     WHERE sender_id=u.id AND receiver_id=? AND is_read=0) AS unread
    FROM users u
    WHERE u.id != ?
";

$params = [$user['id'], $user['id']];

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.role LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY u.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

// conversation thread
$thread = [];
$contact = null;

if ($toId) {
    $cStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $cStmt->execute([$toId]);
    $contact = $cStmt->fetch();

    $tStmt = $pdo->prepare("
        SELECT m.*, u.name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id=? AND m.receiver_id=?)
           OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.created_at ASC
        LIMIT 100
    ");
    $tStmt->execute([$user['id'], $toId, $toId, $user['id']]);
    $thread = $tStmt->fetchAll();

    $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")
        ->execute([$toId, $user['id']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages — StudyVerse</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

<style>
.messages-layout{
    display:grid;
    grid-template-columns:280px 1fr;
    gap:18px;
    height:620px;
}

.contact-list{
    height:620px;
    overflow:hidden;
}

.contact-scroll{
    height:500px;
    overflow-y:auto;
}

.contact-search{
    padding:12px;
    border-bottom:1px solid var(--border);
}

.contact-search input{
    width:100%;
}

.thread-card{
    height:620px;
    display:flex;
    flex-direction:column;
    padding:0;
    overflow:hidden;
}

.thread-area{
    flex:1;
    overflow-y:auto;
    padding:18px;
}

.empty-chat{
    height:620px;
    display:flex;
    align-items:center;
    justify-content:center;
}
</style>
</head>

<body>
<div class="layout">
<?php require_once 'includes/sidebar.php'; ?>

<main class="main-content">

<div class="page-header">
    <h1>MESSAGES ✉</h1>
    <p>SEARCH AND CHAT WITH STUDENTS OR LECTURERS</p>
</div>

<div class="messages-layout">

    <!-- contact list -->
    <div class="contact-list">
        <div class="contact-header">CONVERSATIONS</div>

        <form method="GET" class="contact-search">
            <input type="text"
                   name="search"
                   placeholder="SEARCH CONTACT..."
                   value="<?= e($search) ?>">
        </form>

        <div class="contact-scroll">
            <?php if (empty($contacts)): ?>
                <div style="padding:16px;color:var(--muted);font-size:13px;">
                    NO CONTACT FOUND.
                </div>
            <?php endif; ?>

            <?php foreach ($contacts as $c): ?>
                <a href="<?= BASE_URL ?>/messages.php?to=<?= $c['id'] ?>" style="text-decoration:none;">
                    <div class="contact-item <?= $toId==$c['id'] ? 'active' : '' ?>">
                        <div class="flex-between">
                            <span class="contact-name"><?= e($c['name']) ?></span>

                            <?php if ($c['unread'] > 0): ?>
                                <span class="unread-badge"><?= $c['unread'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="contact-last"><?= e($c['role']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- thread -->
    <?php if ($contact): ?>
        <div class="card thread-card">

            <div style="padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
                <div class="avatar avatar-sm <?= $contact['role']==='Lecturer' ? 'avatar-amber' : 'avatar-accent' ?>">
                    <?= strtoupper(substr($contact['name'], 0, 1)) ?>
                </div>

                <div>
                    <div style="font-size:14px;font-weight:600;"><?= e($contact['name']) ?></div>
                    <div style="font-size:11px;color:var(--muted);"><?= e($contact['role']) ?></div>
                </div>
            </div>

            <div class="thread-area" id="threadArea">
                <?php foreach ($thread as $m): ?>
                    <?php $isMe = $m['sender_id'] == $user['id']; ?>

                    <div class="msg-bubble <?= $isMe ? 'me' : 'other' ?>">
                        <div class="msg-text <?= $isMe ? 'me' : 'other' ?>">
                            <?= nl2br(e($m['message'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" style="padding:12px;border-top:1px solid var(--border);display:flex;gap:8px;">
                <input type="hidden" name="send" value="1">
                <input type="text" name="message" placeholder="TYPE A MESSAGE..." autocomplete="off" style="flex:1;" required>
                <button type="submit" class="btn btn-primary">SEND</button>
            </form>

        </div>

        <script>
            const ta = document.getElementById('threadArea');
            if (ta) ta.scrollTop = ta.scrollHeight;
        </script>

    <?php else: ?>
        <div class="card empty-chat">
            <div style="color:var(--muted);font-size:13px;text-align:center;">
                SELECT A CONTACT TO START MESSAGING
            </div>
        </div>
    <?php endif; ?>

</div>

</main>
</div>
</body>
</html>