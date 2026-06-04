<?php
require_once 'config/db.php';
requireLogin();

$user = currentUser();

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* POST COMMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && $viewId) {
    $comment = trim($_POST['comment']);

    if ($comment) {
        $stmt = $pdo->prepare(
            "INSERT INTO paper_comments (paper_id, user_id, comment) VALUES (?, ?, ?)"
        );
        $stmt->execute([$viewId, $user['id'], $comment]);
    }

    header("Location: " . BASE_URL . "/papers.php?id=" . $viewId);
    exit;
}

/* SINGLE PAPER VIEW */
$paper = null;
$comments = [];

if ($viewId) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS uploader_name, u.role AS uploader_role
        FROM papers p
        JOIN users u ON p.uploaded_by = u.id
        WHERE p.id = ? AND p.status = 'approved'
    ");
    $stmt->execute([$viewId]);
    $paper = $stmt->fetch();

    if ($paper) {
        $pdo->prepare("UPDATE papers SET views = views + 1 WHERE id = ?")
            ->execute([$viewId]);

        $commStmt = $pdo->prepare("
            SELECT c.*, u.name AS user_name, u.role AS user_role
            FROM paper_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.paper_id = ?
            ORDER BY c.created_at ASC
        ");
        $commStmt->execute([$viewId]);
        $comments = $commStmt->fetchAll();
    }
}

/* PAPER LIST */
$search = trim($_GET['search'] ?? '');
$filterLevel = $_GET['level'] ?? 'All';
$filterType = $_GET['type'] ?? 'All';

$sql = "
    SELECT p.*, u.name AS uploader_name
    FROM papers p
    JOIN users u ON p.uploaded_by = u.id
    WHERE p.status = 'approved'
";

$params = [];

if ($filterLevel !== 'All') {
    $sql .= " AND p.level = ?";
    $params[] = $filterLevel;
}

if ($filterType !== 'All') {
    $sql .= " AND p.type = ?";
    $params[] = $filterType;
}

if ($search) {
    $sql .= " AND (p.subject LIKE ? OR p.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$papers = $stmt->fetchAll();

function typeBadge($type) {
    return match($type) {
        'Past Year' => 'badge-accent',
        'Trial' => 'badge-amber',
        default => 'badge-teal',
    };
}

$levels = ['Primary School', 'Secondary School', 'STPM', 'University'];
$types = ['All', 'Past Year', 'Trial', 'Revision', 'Other'];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papers — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="main-content">

    <?php if ($viewId && !empty($paper)): ?>

        <a href="<?= BASE_URL ?>/papers.php" class="btn btn-ghost btn-sm mb-16">← BACK TO PAPERS</a>

        <div class="card mb-16">
            <div class="flex-between mb-16">
                <div>
                    <h1 style="font-size:20px;margin-bottom:8px;"><?= e($paper['title']) ?></h1>
                    <div class="flex-center gap-8 flex-wrap">
                        <span class="badge badge-accent"><?= e($paper['level']) ?></span>
                        <span class="badge <?= typeBadge($paper['type']) ?>"><?= e($paper['type']) ?></span>
                        <span class="badge badge-muted"><?= e($paper['year']) ?></span>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/<?= e($paper['file_path']) ?>" download class="btn btn-teal btn-sm">
                    ⬇ DOWNLOAD
                </a>
            </div>

            <div class="divider"></div>

            <div style="font-size:13px;color:var(--muted);margin-bottom:16px;">
                UPLOADED BY:
                <span style="color:var(--text)"><?= e($paper['uploader_name']) ?></span>
                &nbsp;·&nbsp; <?= e($paper['views']) ?> VIEWS

                <?php if (!empty($paper['description'])): ?>
                    &nbsp;·&nbsp; <?= e($paper['description']) ?>
                <?php endif; ?>
            </div>

            <?php if ($paper['file_type'] === 'pdf'): ?>
                <iframe src="<?= BASE_URL ?>/<?= e($paper['file_path']) ?>"
                        width="100%" height="500"
                        style="border:1px solid var(--border);border-radius:8px;margin-bottom:20px;">
                </iframe>
            <?php else: ?>
                <div style="background:#0a0a20;border-radius:8px;padding:40px;text-align:center;margin-bottom:20px;">
                    <div style="font-size:32px;margin-bottom:8px;">📄</div>
                    <div style="color:var(--muted);font-size:13px;">
                        PREVIEW NOT AVAILABLE. PLEASE DOWNLOAD.
                    </div>
                </div>
            <?php endif; ?>

            <div class="section-label">DISCUSSION (<?= count($comments) ?>)</div>

            <?php if (empty($comments)): ?>
                <p style="color:var(--muted);font-size:13px;">NO COMMENTS YET.</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div style="padding:10px 0;border-bottom:1px solid var(--border);">
                        <div class="flex-center gap-10 mb-8">
                            <div class="avatar avatar-sm <?= $c['user_role'] === 'Lecturer' ? 'avatar-amber' : 'avatar-accent' ?>">
                                <?= strtoupper(substr($c['user_name'], 0, 1)) ?>
                            </div>

                            <span style="color:var(--text);font-size:13px;font-weight:500;">
                                <?= e($c['user_name']) ?>
                            </span>

                            <?php if ($c['user_role'] === 'Lecturer'): ?>
                                <span class="badge badge-amber">LECTURER</span>
                            <?php endif; ?>

                            <span style="color:var(--muted);font-size:11px;">
                                <?= date('d M Y, H:i', strtotime($c['created_at'])) ?>
                            </span>
                        </div>

                        <p style="color:var(--muted);font-size:13px;margin:0 0 0 38px;">
                            <?= nl2br(e($c['comment'])) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" style="display:flex;gap:10px;margin-top:16px;">
                <input type="text" name="comment" placeholder="WRITE A COMMENT..." required style="flex:1;">
                <button type="submit" class="btn btn-primary">POST</button>
            </form>
        </div>

    <?php else: ?>

        <div class="page-header">
            <h1>QUESTION BANK 📄</h1>
            <p>PAST YEAR PAPERS, TRIAL EXAMS & REVISION MATERIALS</p>
        </div>

        <form method="GET">
            <div class="search-bar">
                <input type="text" name="search" class="search-input"
                       placeholder="SEARCH SUBJECT OR TITLE..."
                       value="<?= e($search) ?>">

                <select name="level">
                    <option value="All" <?= $filterLevel === 'All' ? 'selected' : '' ?>>ALL LEVELS</option>
                    <?php foreach ($levels as $l): ?>
                        <option value="<?= e($l) ?>" <?= $filterLevel === $l ? 'selected' : '' ?>>
                            <?= e($l) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="type">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= e($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                            <?= e($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">SEARCH</button>
            </div>
        </form>

        <div class="card">
            <?php if (empty($papers)): ?>
                <div class="loading">NO PAPERS FOUND.</div>
            <?php else: ?>
                <?php foreach ($papers as $p): ?>
                    <div class="paper-item">
                        <div class="flex-center gap-10" style="flex:1;">
                            <div class="paper-icon">📄</div>
                            <div>
                                <div class="paper-title"><?= e($p['subject']) ?></div>
                                <div class="paper-meta">
                                    <?= e($p['level']) ?> · <?= e($p['year']) ?> · BY <?= e($p['uploader_name']) ?>
                                </div>
                                <div style="margin-top:4px;">
                                    <span class="badge <?= typeBadge($p['type']) ?>">
                                        <?= e($p['type']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="text-align:right;flex-shrink:0;">
                            <div style="color:var(--muted);font-size:11px;">
                                <?= e($p['views']) ?> VIEWS
                            </div>
                            <a href="?id=<?= e($p['id']) ?>" class="btn btn-outline btn-sm" style="margin-top:6px;">
                                VIEW →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>