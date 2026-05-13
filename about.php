<?php
require_once 'config/db.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About — StudyVerse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <?php require_once 'includes/sidebar.php'; ?>
    <main class="main-content">

        <div class="page-header">
            <h1>ABOUT</h1>
            <p>YOUR UNIVERSE OF LEARNING</p>
        </div>

        <!-- about card -->
        <div class="card mb-16"
            style="display:flex; align-items:flex-start; gap:20px;">

        <!-- logo -->
        <img src="<?= BASE_URL ?>/assets/images/logo.png"
            alt="StudyVerse Logo"
            style="
                width:70px;
                height:70px;
                object-fit:contain;
                flex-shrink:0;
            ">

        <!-- text -->
        <p style="
            font-size:14px;
            line-height:1.9;
            color:var(--text);
            max-width:680px;
            margin:0;
            ">

            <strong>STUDYVERSE</strong> is a community-based learning platform
            for Malaysian students from primary school to university.

            We believe knowledge grows when shared. Therefore, both students
            and lecturers can contribute content, making this platform a truly
            interactive learning experience <em>community-driven</em>.

        </p>

</div>

        <!-- features grid -->
        <div class="section-label">Features</div>
        <div class="grid-3" style="margin-bottom:24px;">
            <?php
            $features = [
                ['📄', 'QUESTION BANK', 'Past year papers, trial exams and revision for Primary, Secondary, STPM and University all in one place.'],
                ['✦', 'AI SUMMARIZER', 'Paste your notes and the AI ​​will summarize them into key points, key terms and difficulty level within a few seconds.'],
                ['⬡', 'STUDY ROOMS', 'Create or join study rooms to discuss topics, share materials and learn with other students & lecturers.'],
                ['⬆', 'OPEN UPLOAD',  'All users can contribute papers to the community. Each upload will be reviewed by the admin before being displayed.'],
                ['◈', 'PROGRESS TRACKER', 'Track study hours, scores and papers completed for each subject. Monitor your learning progress.'],
                ['✉', 'DIRECT MESSAGE', 'Send messages directly to students or lecturers. Ask questions, request notes or just chat.'],
            ];
            foreach ($features as [$icon, $title, $desc]):
            ?>
            <div class="card" style="text-align:center;">
                <div style="font-size:24px;margin-bottom:10px;"><?= $icon ?></div>
                <div style="font-weight:500;font-size:13px;color:var(--text);margin-bottom:6px;"><?= $title ?></div>
                <div style="font-size:12px;color:var(--muted);line-height:1.6;"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- user roles -->
        <div class="section-label">USER ROLE</div>
        <div class="grid-3">
            <?php
            $roles = [
                ['🛡️', 'ADMIN',    'var(--danger)', 'Manage users, approve papers, monitor study rooms & system analytics.'],
                ['🎓', 'LECTURER', 'var(--amber)',  'Upload papers, open study rooms, guide students in discussions.'],
                ['📚', 'STUDENT',  'var(--teal)',   'Browse papers, join study rooms, track progress & use AI summarizer.'],
            ];
            foreach ($roles as [$icon, $role, $color, $desc]):
            ?>
            <div class="card" style="border-color:<?= $color ?>44;">
                <div style="font-size:24px;margin-bottom:8px;"><?= $icon ?></div>
                <div style="font-weight:600;font-size:14px;color:<?= $color ?>;margin-bottom:8px;"><?= $role ?></div>
                <div style="font-size:12px;color:var(--muted);line-height:1.6;"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>