<?php
require_once '../config/db.php';
requireLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$notes = trim($input['notes'] ?? '');

if (!$notes) {
    echo json_encode(['error' => 'PLEASE ENTER NOTES FIRST.']);
    exit;
}

/* ambik ayat dari notes */
$sentences = preg_split('/(\.|\!|\?)+/', $notes);

/* buang empty */
$sentences = array_filter(array_map('trim', $sentences));

/* ambik max 4 point */
$points = array_slice($sentences, 0, 10);

$points = array_map(function($p) {
    $words = explode(' ', $p);
    if (count($words) > 18) {
        $p = implode(' ', array_slice($words, 0, 18)) . '...';
    }
    return strtoupper($p);
}, $points);

/* key terms simple */
preg_match_all('/\b[A-Za-z]{5,}\b/', $notes, $matches);

$keywords = array_unique($matches[0]);
$keywords = array_slice($keywords, 0, 5);
$keywords = array_map('strtoupper', $keywords);

/* difficulty logic */
$wordCount = str_word_count($notes);

if ($wordCount < 80) {
    $difficulty = 'Easy';
} elseif ($wordCount < 180) {
    $difficulty = 'Medium';
} else {
    $difficulty = 'Hard';
}

echo json_encode([
    'title' => 'AI GENERATED SUMMARY',
    'points' => $points,
    'keyTerms' => $keywords,
    'difficulty' => $difficulty
]);