<?php
// ============================================================
// api/delete_paper.php — Delete own paper
// SAVE AS: api/delete_paper.php
// ============================================================
require_once '../config/db.php';
requireLogin();

$user    = currentUser();
$paperId = (int)($_POST['paper_id'] ?? 0);

if (!$paperId) {
    header('Location: ' . BASE_URL . '/upload.php');
    exit;
}

// Security: only owner OR admin boleh delete
$stmt = $pdo->prepare("SELECT file_path, uploaded_by FROM papers WHERE id = ?");
$stmt->execute([$paperId]);
$paper = $stmt->fetch();

if ($paper && ($paper['uploaded_by'] == $user['id'] || $user['role'] === 'Admin')) {
    // Delete physical file
    $filePath = __DIR__ . '/../' . $paper['file_path'];
    if (file_exists($filePath)) unlink($filePath);

    // Delete from DB (cascade delete comments sekali)
    $pdo->prepare("DELETE FROM papers WHERE id = ?")->execute([$paperId]);
}

// Redirect back
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/upload.php';
header('Location: ' . $referer);
exit;