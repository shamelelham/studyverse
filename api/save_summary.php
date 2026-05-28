<?php
// ============================================================
// api/save_summary.php — Save AI summary to database
// SAVE AS: api/save_summary.php
// ============================================================
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$notes   = trim($input['notes']   ?? '');
$summary = $input['summary']      ?? null;
$title   = trim($input['title']   ?? 'My Summary');

if (!$summary) {
    echo json_encode(['success' => false, 'error' => 'No summary data.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO ai_summaries (user_id, title, original, summary) VALUES (?,?,?,?)");
$stmt->execute([
    currentUser()['id'],
    $title,
    $notes,
    json_encode($summary)
]);

echo json_encode(['success' => true]);