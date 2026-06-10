<?php
require_once '../config/db.php';
requireAdmin();

// approve
if (isset($_GET['approve'])) {
    $pid = (int)$_GET['approve'];
    $pdo->prepare("UPDATE papers SET status = 'approved' WHERE id = ?")->execute([$pid]);

    //  notify uploader
    $p = $pdo->prepare("SELECT uploaded_by, title FROM papers WHERE id = ?");
    $p->execute([$pid]);
    $paper = $p->fetch();
    if ($paper) {
        $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?,?,?)")
            ->execute([$paper['uploaded_by'], 'paper_approved',
                "✅ Paper '{$paper['title']}' has been approved by the admin!"]);
    }
    setFlash('success', 'Paper successfully approved!');
    header('Location: ' . BASE_URL . '/admin/papers.php?filter=pending');
    exit;
}

// reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_paper'])) {
    $pid    = (int)$_POST['paper_id'];
    $reason = trim($_POST['reason'] ?? 'The content does not meet the standards.');
    $pdo->prepare("UPDATE papers SET status = 'rejected', reject_reason = ? WHERE id = ?")
        ->execute([$reason, $pid]);

    // notify uploader
    $p = $pdo->prepare("SELECT uploaded_by, title FROM papers WHERE id = ?");
    $p->execute([$pid]);
    $paper = $p->fetch();
    if ($paper) {
        $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?,?,?)")
            ->execute([$paper['uploaded_by'], 'paper_rejected',
                "❌ Paper '{$paper['title']}' rejected. Reason: $reason"]);
    }
    setFlash('danger', 'Paper has been rejected.');
    header('Location: ' . BASE_URL . '/admin/papers.php?filter=pending');
    exit;
}

// delete
if (isset($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $p   = $pdo->prepare("SELECT file_path FROM papers WHERE id = ?");
    $p->execute([$pid]);
    $paper = $p->fetch();
    if ($paper) {
        $fp = __DIR__ . '/../' . $paper['file_path'];
        if (file_exists($fp)) unlink($fp);
        $pdo->prepare("DELETE FROM papers WHERE id = ?")->execute([$pid]);
    }
    setFlash('success', 'Paper was successfully deleted.');
    header('Location: ' . BASE_URL . '/admin/papers.php');
    exit;
}

// fetch papers
$filter = $_GET['filter'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$sql    = "SELECT p.*, u.name AS uploader FROM papers p JOIN users u ON p.uploaded_by = u.id WHERE 1=1";
$params = [];

if ($filter !== 'All') { $sql .= " AND p.status = ?"; $params[] = $filter; }
if ($search)           { $sql .= " AND (p.subject LIKE ? OR p.title LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql .= " ORDER BY p.created_at DESC";
$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$papers = $stmt->fetchAll();

$pendingCount  = $pdo->query("SELECT COUNT(*) FROM papers WHERE status='pending'")->fetchColumn();
$approvedCount = $pdo->query("SELECT COUNT(*) FROM papers WHERE status='approved'")->fetchColumn();
$rejectedCount = $pdo->query("SELECT COUNT(*) FROM papers WHERE status='rejected'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Papers — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once '../includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>APPROVE PAPERS 📄</h1>
            <p><?= $pendingCount ?> pending · <?= $approvedCount ?> approved · <?= $rejectedCount ?> rejected</p>
        </div>

        <?php showFlash(); ?>

        <!-- filter tabs & search -->
        <div class="flex-between mb-20" style="flex-wrap:wrap;gap:10px;">
            <div class="flex-center gap-8">
                <?php foreach (['All','pending','approved','rejected'] as $f): ?>
                <a href="?filter=<?= $f ?>"
                   class="btn <?= $filter===$f?'btn-primary':'btn-ghost' ?> btn-sm">
                    <?= ucfirst($f) ?>
                    <?php if ($f==='pending' && $pendingCount > 0): ?>
                        <span style="background:var(--danger);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px;"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <form method="GET" style="display:flex;gap:8px;">
                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                <input type="text" name="search" placeholder="SEARCH SUBJECT..."
                       value="<?= e($search) ?>" style="width:220px;">
                <button type="submit" class="btn btn-primary btn-sm">SEARCH</button>
            </form>
        </div>

        <!-- papers list -->
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php if (empty($papers)): ?>
                <div class="card loading">There is no paper for this filter.</div>
            <?php else: ?>
                <?php foreach ($papers as $p): ?>
                <div class="card">
                    <div class="flex-between" style="flex-wrap:wrap;gap:10px;">
                        <!-- paper info -->
                        <div class="flex-center gap-14" style="flex:1;">
                            <div>
                                <div style="font-weight:500;font-size:14px;color:var(--text);">
                                    <?= e($p['title']) ?>
                                </div>
                                <div style="font-size:12px;color:var(--muted);">
                                    <?= e($p['level']) ?> · <?= e($p['subject']) ?> · <?= $p['year'] ?>
                                    · by <?= e($p['uploader']) ?>
                                    · <?= date('d M Y', strtotime($p['created_at'])) ?>
                                </div>
                                <?php if ($p['reject_reason']): ?>
                                <div style="color:var(--danger);font-size:11px;margin-top:3px;">
                                    Reason: <?= e($p['reject_reason']) ?>
                                </div>
                                <?php endif; ?>
                                <div style="margin-top:6px;display:flex;gap:6px;">
                                    <span class="badge <?= match($p['status']){'approved'=>'badge-teal','rejected'=>'badge-danger',default=>'badge-amber'} ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                    <span class="badge badge-muted"><?= e($p['type']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- actions -->
                        <div class="flex-center gap-8" style="flex-shrink:0;">
                            <?php if ($p['status'] === 'pending'): ?>
                                <a href="?approve=<?= $p['id'] ?>&filter=pending"
                                   class="btn btn-teal btn-sm">✓ APPROVE</a>
                                <button class="btn btn-danger btn-sm"
                                        onclick="toggleForm('reject-<?= $p['id'] ?>')">
                                    ✗ REJECT
                                </button>
                            <?php endif; ?>
                            <a href="?delete=<?= $p['id'] ?>&filter=<?= $filter ?>"
                               class="btn btn-ghost btn-sm"
                               data-confirm="Delete this paper permanently?">
                               🗑
                            </a>
                        </div>
                    </div>

                    <!-- reject form (hidden) -->
                    <div id="reject-<?= $p['id'] ?>"
                         style="display:none;margin-top:12px;background:#0a0a20;padding:14px;border-radius:8px;">
                        <form method="POST">
                            <input type="hidden" name="reject_paper" value="1">
                            <input type="hidden" name="paper_id"    value="<?= $p['id'] ?>">
                            <div style="display:flex;gap:10px;align-items:flex-end;">
                                <div style="flex:1;">
                                    <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:5px;">
                                        Reason for rejection (will be notified to the uploader)
                                    </label>
                                    <input type="text" name="reason"
                                           placeholder="e.g. Content is incomplete..." required>
                                </div>
                                <button type="submit" class="btn btn-danger">CONFRIM</button>
                                <button type="button" class="btn btn-ghost"
                                        onclick="toggleForm('reject-<?= $p['id'] ?>')">CANCEL</button>
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