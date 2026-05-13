<?php
require_once 'config/db.php';
requireLogin();

$faqs = [
    [
        'q' => 'How do I change my education level?',
        'a' => 'Go to Profile → Edit Profile → change Education Level → Save. Dashboard and papers will auto-update according to the new level.'
    ],
    [
        'q' => 'Who can upload papers?',
        'a' => 'All registered users (Students & Lecturers) can upload papers. Uploaded papers will be marked as "Pending" and must be approved by the Admin before becoming visible.'
    ],
    [
        'q' => 'How does the AI Summarizer work?',
        'a' => 'Paste your notes into the AI Summarizer and click "Summarize". The AI will generate key points, key terms and difficulty levels within seconds.'
    ],
    [
        'q' => 'Can I create my own study room?',
        'a' => 'Yes! Go to Study Rooms → click "Create Room" → enter the room name, choose level and type (Public/Private) → click Create.'
    ],
    [
        'q' => 'What is the difference between Student and Lecturer?',
        'a' => 'Lecturers can create and manage study rooms. Students can track subject progress. Both can upload papers, use the AI Summarizer and send messages.'
    ],
    [
        'q' => 'My paper was rejected — what should I do?',
        'a' => 'Check the rejection reason in the Upload page. Fix the paper content and upload it again. Make sure the paper is relevant and complete.'
    ],
    [
        'q' => 'Forgot your password?',
        'a' => 'Click "Forgot Password?" on the login page. Enter your email and follow the instructions provided.'
    ],
    [
        'q' => 'How do I send a message to a lecturer?',
        'a' => 'Go to Messages → select the lecturer from the contact list → type your message → click Send.'
    ],
];
?>
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & FAQ — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>HELP & FAQ ❓</h1>
            <p>Frequently asked questions about STUDYVERSE</p>
        </div>

        <!-- Search hint -->
        <div class="alert alert-info mb-20">
            💡 Click on the question to see the answer.
        </div>

        <!-- FAQ list -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="card faq-item" id="faq-<?= $i ?>">
                <div class="faq-question">
                    <span style="font-weight:500;font-size:14px;color:var(--text);"><?= e($faq['q']) ?></span>
                    <span class="faq-icon">+</span>
                </div>
                <div class="faq-answer"><?= e($faq['a']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Contact support -->
        <!-- <div class="card mt-16" style="text-align:center;padding:28px;">
            <div style="font-size:24px;margin-bottom:8px;">📬</div>
            <div style="font-weight:500;color:var(--text);margin-bottom:6px;">Can't find the answer?</div>
            <p style="color:var(--muted);font-size:13px;margin-bottom:16px;">
                Contact admin.
            </p>
            <a href="<?= BASE_URL ?>/messages.php" class="btn btn-primary btn-sm">Send Message</a>
        </div> -->

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>