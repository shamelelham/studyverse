<?php
require_once 'config/db.php';
requireLogin();
$user = currentUser();

// add subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject = trim($_POST['subject'] ?? '');
    if ($subject) {
        $check = $pdo->prepare("SELECT id FROM progress WHERE user_id=? AND subject=?");
        $check->execute([$user['id'], $subject]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO progress (user_id, subject) VALUES (?,?)")
                ->execute([$user['id'], $subject]);
            setFlash('success', "Subject '$subject' added successfully!");
        } else {
            setFlash('danger', "Subject '$subject' already exists.");
        }
    }
    redirect('/progress.php');
}

//  update progress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $pid        = (int)$_POST['progress_id'];
    $score      = min(100, max(0, (int)$_POST['score']));
    $hours      = max(0, (float)$_POST['hours']);
    $papersDone = max(0, (int)$_POST['papers_done']);

    $pdo->prepare("UPDATE progress SET score=?, study_hours=?, papers_done=? WHERE id=? AND user_id=?")
        ->execute([$score, $hours, $papersDone, $pid, $user['id']]);
    setFlash('success', 'Progress updated!');
    redirect('/progress.php');
}

// delete subject
if (isset($_GET['delete']) && $_GET['delete']) {
    $pid = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM progress WHERE id=? AND user_id=?")->execute([$pid, $user['id']]);
    setFlash('success', 'Subject deleted successfully.');
    redirect('/progress.php');
}

// fetch progress
$progStmt = $pdo->prepare("SELECT * FROM progress WHERE user_id=? ORDER BY subject ASC");
$progStmt->execute([$user['id']]);
$progList = $progStmt->fetchAll();

// calculate totals
$totalHours  = array_sum(array_column($progList, 'study_hours'));
$totalPapers = array_sum(array_column($progList, 'papers_done'));
$avgScore    = count($progList)
    ? round(array_sum(array_column($progList, 'score')) / count($progList))
    : 0;
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>My Progress ◈</h1>
            <p>Track your learning progress</p>
        </div>

        <?php showFlash(); ?>

        <!-- stats  -->
        <div class="grid-4 mb-24">
            <div class="card stat-card">
                <div class="value" style="color:var(--accent-l)"><?= count($progList) ?></div>
                <div class="label">Total Subjects</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--teal)"><?= number_format($totalHours, 1) ?>h</div>
                <div class="label">Study Hours</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--amber)"><?= $totalPapers ?></div>
                <div class="label">Papers Completed</div>
            </div>
            <div class="card stat-card">
                <div class="value" style="color:var(--success)"><?= $avgScore ?>%</div>
                <div class="label">Avg Score</div>
            </div>
        </div>

        <!-- add subject -->
        <div class="card mb-16">
            <div class="flex-between mb-16">
                <div class="section-label" style="margin:0;">Subject Progress</div>
                <button class="btn btn-outline btn-sm" onclick="toggleForm('addSubjectForm')">
                    + ADD
                </button>
            </div>

            <!-- add subject form -->
            <div id="addSubjectForm" style="display:none;margin-bottom:16px;background:#0a0a20;padding:14px;border-radius:8px;">
                <form method="POST" style="display:flex;gap:10px;align-items:flex-end;">
                    <input type="hidden" name="add_subject" value="1">
                    <div style="flex:1;">
                        <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:5px;">Subject Name</label>
                        <input type="text" name="subject" placeholder="e.g. Chemistry, Add Maths..." required>
                    </div>
                    <button type="submit" class="btn btn-primary">ADD</button>
                    <button type="button" class="btn btn-ghost" onclick="toggleForm('addSubjectForm')">CANCEL</button>
                </form>
            </div>

            <!-- progress list -->
            <?php if (empty($progList)): ?>
                <p style="color:var(--muted);font-size:13px;text-align:center;padding:20px;">
                    No subjects available. Add now!
                </p>
            <?php else: ?>
                <?php foreach ($progList as $p):
                    $color = $p['score'] >= 80
                        ? 'var(--success)'
                        : ($p['score'] >= 60 ? 'var(--amber)' : 'var(--danger)');
                ?>
                <div class="progress-item">
                    <div class="progress-head">
                        <span style="font-size:14px;font-weight:500;color:var(--text);"><?= e($p['subject']) ?></span>
                        <div class="flex-center gap-12">
                            <span style="color:var(--muted);font-size:11px;">
                                <?= $p['study_hours'] ?>h · <?= $p['papers_done'] ?> papers
                            </span>
                            <span style="color:<?= $color ?>;font-size:13px;font-weight:600;">
                                <?= $p['score'] ?>%
                            </span>
                            <button class="btn btn-ghost btn-sm"
                                    onclick="toggleForm('edit-<?= $p['id'] ?>')">Edit</button>
                            <a href="?delete=<?= $p['id'] ?>"
                               class="btn btn-danger btn-sm"
                               data-confirm="Delete subject <?= e($p['subject']) ?>?">🗑</a>
                        </div>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-fill" style="width:<?= $p['score'] ?>%;background:<?= $color ?>;"></div>
                    </div>

                    <!-- edit form -->
                    <div id="edit-<?= $p['id'] ?>" style="display:none;margin-top:12px;background:#0a0a20;padding:14px;border-radius:8px;">
                        <form method="POST">
                            <input type="hidden" name="update_progress" value="1">
                            <input type="hidden" name="progress_id" value="<?= $p['id'] ?>">
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:10px;align-items:flex-end;">
                                <div>
                                    <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:5px;">Score (%)</label>
                                    <input type="number" name="score" min="0" max="100" value="<?= $p['score'] ?>">
                                </div>
                                <div>
                                    <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:5px;">Study Hours</label>
                                    <input type="number" name="hours" step="0.5" min="0" value="<?= $p['study_hours'] ?>">
                                </div>
                                <div>
                                    <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:5px;">Papers Done</label>
                                    <input type="number" name="papers_done" min="0" value="<?= $p['papers_done'] ?>">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">SAVE</button>
                                <button type="button" class="btn btn-ghost btn-sm"
                                        onclick="toggleForm('edit-<?= $p['id'] ?>')">CANCEL</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>