<?php
// student & lecturer boleh upload
// status default = pending (kena approval dari admin dulu)
require_once 'config/db.php';
requireLogin();
$user = currentUser();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $level = $_POST['level']   ?? 'Secondary School';
    $type    = $_POST['type']    ?? 'Past Year';
    $year    = (int)($_POST['year'] ?? date('Y'));
    $desc    = trim($_POST['desc'] ?? '');

    if (!$title || !$subject) {
        $error = 'Please fill in the title and subject name.';
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        $file    = $_FILES['file'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($ext, $allowed)) {
            $error = 'Formats are not allowed. Use PDF only.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size exceeds 10MB.';
        } else {
            // buat folder uploads/ kalau tak wujud
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Nama fail unik
            $filename  = uniqid('paper_', true) . '.' . $ext;
            $destPath  = $uploadDir . $filename;
            $fileType  = $ext === 'pdf' ? 'pdf' : 'image';

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $stmt = $pdo->prepare("
                    INSERT INTO papers (title, subject, level, type, year, description, file_path, file_type, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title, $subject, $level, $type, $year, $desc,
                    'uploads/' . $filename, $fileType, $user['id']
                ]);
                $success = 'Paper successfully uploaded! Waiting for admin approval.';
            } else {
                $error = 'Failed to save file. Check uploads folder permissions.';
            }
        }
    }
}

// my uploaded papers
$myStmt = $pdo->prepare("SELECT * FROM papers WHERE uploaded_by = ? ORDER BY created_at DESC");
$myStmt->execute([$user['id']]);
$myPapers = $myStmt->fetchAll();

$levels = ['Primary School', 'Secondary School', 'STPM', 'University'];
$years  = range(date('Y'), 2015);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Paper — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>UPLOAD PAPERS ⬆</h1>
            <p>EMPOWER LEARNING THROUGH COMMUNITY CONTRIBUTION</p>
        </div>

        <?php if ($error):   ?><div class="alert alert-danger"><?=  e($error)   ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <div class="card mb-24" style="max-width:680px;">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">

                <div class="form-group">
                    <label>TITLE *</label>
                    <input type="text" name="title" placeholder="e.g. SPM Mathematics Paper 1 2023"
                           value="<?= e($_POST['title'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>SUBJECT NAME *</label>
                    <input type="text" name="subject" placeholder="e.g. Mathematics, Biology, Physics"
                           value="<?= e($_POST['subject'] ?? '') ?>" required>
                </div>

                <div class="grid-3" style="gap:12px;">
                    <div class="form-group">
                        <label>LEVEL</label>
                        <select name="level">
                            <?php foreach ($levels as $l): ?>
                            <option value="<?= $l ?>" <?= ($user['level']===$l)?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>PAPER TYPE</label>
                        <select name="type">
                            <option>Past Year</option>
                            <option>Trial</option>
                            <option>Revision</option>
                            <option>Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>YEAR</label>
                        <select name="year">
                            <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>DESCRIPTION</label>
                    <textarea name="desc" rows="3"
                              placeholder="Summary of this paper..."><?= e($_POST['desc'] ?? '') ?></textarea>
                </div>

                <!-- Upload zone -->
                <div class="form-group">
                    <label>File (PDF / JPG / PNG, max 10MB) *</label>
                    <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                        <div class="upload-icon">⬆</div>
                        <p>CLICK OR DROP FILES.</p>
                        <span class="btn btn-outline btn-sm">CHOOSE</span>
                        <div id="fileName" style="margin-top:10px;font-size:12px;"></div>
                    </div>
                    <input type="file" id="fileInput" name="file"
                           accept=".pdf,.jpg,.jpeg,.png" style="display:none;" required>
                </div>

                <div class="flex-center gap-10">
                    <button type="submit" class="btn btn-primary">UPLOAD</button>
                    <button type="button" class="btn btn-ghost" onclick="resetUploadForm()">RESET</button>
                </div>
            </form>
        </div>

        <!-- my uploaded papers -->
        <?php if (!empty($myPapers)): ?>
        <div class="section-label">PAPERS YOU UPLOADED</div>
        <div class="card">
            <?php foreach ($myPapers as $p):
                $statusBadge = match($p['status']) {
                    'approved' => 'badge-teal',
                    'rejected' => 'badge-danger',
                    default    => 'badge-amber',
                };
            ?>
            <div class="paper-item" style="display:flex;justify-content:space-between;align-items:center;">
                <div class="flex-center gap-10" style="flex:1;">
                    <div class="paper-icon">📄</div>
                    <div>
                        <div style="font-weight:500;font-size:14px;color:var(--text);">
                            <?= e($p['title']) ?>
                        </div>
                        <div style="font-size:12px;color:var(--muted);">
                            <?= $p['year'] ?> · <?= e($p['level']) ?>
                        </div>
                        <?php if ($p['status'] === 'rejected' && $p['reject_reason']): ?>
                        <div style="color:var(--danger);font-size:11px;margin-top:3px;">
                            REASON : <?= e($p['reject_reason']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-center gap-8" style="margin-left:auto;">
                    <span class="badge <?= $statusBadge ?>"><?= ucfirst($p['status']) ?></span>
                    <!-- delete own paper -->
                    <form method="POST" action="<?= BASE_URL ?>/api/delete_paper.php" style="display:inline;">
                        <input type="hidden" name="paper_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                data-confirm="DELETE PAPERS?">🗑</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<script>
function resetUploadForm() {
    document.getElementById('uploadForm').reset();
    document.getElementById('fileInput').value = '';
    document.getElementById('fileName').textContent = '';
}
</script>
</body>
</html>