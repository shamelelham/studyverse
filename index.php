<?php

require_once 'config/db.php';

// Redirect kalau dah login
if (isLoggedIn()) {
    $go = $_SESSION['user_role'] === 'Admin'
        ? BASE_URL . '/admin/dashboard.php'
        : BASE_URL . '/dashboard.php';
    header('Location: ' . $go);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVerse — Community Learning Platform</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* ── NAVBAR ──────────────────────────────────────── */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(7,7,26,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            z-index: 100;
        }
        .navbar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 18px;
            color: var(--text);
            text-decoration: none;
        }

        .navbar-logo-img{
    width:32px;
    height:32px;
    object-fit:contain;
}

.hero-logo-img{
    width:90px;
    height:90px;
    object-fit:contain;
    filter: drop-shadow(0 0 20px rgba(124,106,247,.45));
}

.footer-logo-img{
    width:24px;
    height:24px;
    object-fit:contain;
}
        .navbar-btns { display: flex; gap: 10px; }

        /* ── HERO ────────────────────────────────────────── */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px 60px;
            background: radial-gradient(ellipse at 50% 0%, #2a1060 0%, #07071a 60%);
            position: relative;
            overflow: hidden;
        }
        /* Glow effects */
        .hero::before {
            content: '';
            position: absolute;
            top: -100px; left: 50%;
            transform: translateX(-50%);
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(124,106,247,0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent-dim);
            border: 1px solid rgba(124,106,247,.4);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            color: var(--accent-l);
            margin-bottom: 24px;
        }
        .hero-logo   { font-size: 72px; margin-bottom: 16px; line-height: 1; }
        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #fff 0%, var(--accent-l) 50%, var(--teal) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.15;
        }
        .hero p {
            font-size: 18px;
            color: var(--muted);
            max-width: 560px;
            margin: 0 auto 36px;
            line-height: 1.7;
        }
        .hero-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .btn-hero-primary {
            background: var(--accent);
            color: #fff;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero-primary:hover {
            background: var(--accent-l);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(124,106,247,0.4);
        }
        .btn-hero-outline {
            background: transparent;
            color: var(--text);
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 500;
            border-radius: 10px;
            border: 1px solid var(--border);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero-outline:hover {
            border-color: var(--accent);
            color: var(--accent-l);
            transform: translateY(-2px);
        }
        .hero-note { font-size: 12px; color: var(--muted); }

        /* stats */
        .stats-bar {
            display: flex;
            gap: 40px;
            justify-content: center;
            flex-wrap: wrap;
            padding: 32px 20px;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .stat-item { text-align: center; }
        .stat-num  { font-size: 28px; font-weight: 700; color: var(--accent-l); }
        .stat-lbl  { font-size: 13px; color: var(--muted); margin-top: 2px; }

        /* features */
        .section { padding: 80px 40px; max-width: 1100px; margin: 0 auto; }
        .section-title {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text);
        }
        .section-sub {
            text-align: center;
            color: var(--muted);
            font-size: 15px;
            margin-bottom: 48px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px 24px;
            transition: all 0.2s;
        }
        .feature-card:hover {
            border-color: var(--accent);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(124,106,247,0.1);
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 14px;
            display: block;
        }
        .feature-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .feature-card p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.7;
        }

        /* hows work */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .step-card {
            text-align: center;
            padding: 24px 16px;
        }
        .step-num {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: var(--accent-dim);
            border: 1px solid rgba(124,106,247,.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            color: var(--accent-l);
            margin: 0 auto 14px;
        }
        .step-card h3 { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
        .step-card p  { font-size: 12px; color: var(--muted); line-height: 1.6; }

        /* roles */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .role-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px 24px;
        }
        .role-card ul {
            list-style: none;
            padding: 0;
            margin: 12px 0 0;
        }
        .role-card ul li {
            font-size: 13px;
            color: var(--muted);
            padding: 5px 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .role-card ul li:last-child { border-bottom: none; }
        .role-card ul li::before   { content: '✓'; color: var(--teal); font-size: 11px; flex-shrink: 0; }

        @media (max-width: 768px) {
            .hero h1           { font-size: 32px; }
            .hero p            { font-size: 15px; }
            .features-grid,
            .roles-grid        { grid-template-columns: 1fr; }
            .steps-grid        { grid-template-columns: repeat(2, 1fr); }
            .navbar            { padding: 14px 20px; }
            .section           { padding: 50px 20px; }
            .stats-bar         { gap: 24px; }
        }
    </style>
</head>
<body>

<!-- navbar -->
<nav class="navbar">
    <a href="<?= BASE_URL ?>/" class="navbar-logo">
        <img src="<?= BASE_URL ?>/assets/images/logo.png" 
     alt="StudyVerse Logo"
     class="navbar-logo-img">

<span>STUDYVERSE</span>
    </a>
    <!--<div class="navbar-btns">
        <a href="<?= BASE_URL ?>/login.php"    class="btn btn-ghost btn-sm">Login</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Free Register</a>
    </div> -->
</nav>

<!-- ── hero ──────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-badge">
        ✦ MALAYSIA COMMUNITY LEARNING PLATFORM
    </div>
    <div class="hero-logo">
    <img src="<?= BASE_URL ?>/assets/images/logo.png" 
         alt="StudyVerse Logo"
         class="hero-logo-img">
</div>
    <h1>LEARN TOGETHER,<br>GO FURTHER</h1>
    <p>Community-Based Learning Platform for Malaysian Students from Primary School to University. Find Past Year Papers, Use AI Summarizer and Study Together!
</p>
    <div class="hero-btns">
        <a href="<?= BASE_URL ?>/register.php" class="btn-hero-primary">
            🚀 GET STARTED !
        </a>
        <a href="<?= BASE_URL ?>/login.php" class="btn-hero-outline">
            LOGIN →
        </a>
    </div>
    <p class="hero-note">✓ FREE · ✓ NO CREDIT CARD · ✓ FOR ALL MALAYSIAN STUDENTS</p>
</section>

<!-- ── stats ─────────────────────────────────────────────── -->
<div class="stats-bar">
    <?php
    // fetch live stats dari database
    $totalUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
    $totalPapers = $pdo->query("SELECT COUNT(*) FROM papers WHERE status='approved'")->fetchColumn();
    $totalRooms  = $pdo->query("SELECT COUNT(*) FROM study_rooms WHERE is_active=1")->fetchColumn();
    $stats = [
        [$totalUsers  ?: '100+', 'REGISTERED STUDENTS'],
        [$totalPapers ?: '500+', 'ACTIVE STUDY ROOMS'],
        [$totalRooms  ?: '50+',  'EDUCATION LEVELS'],
        ['4',                    'EVERYTHING YOU NEED'],
    ];
    foreach ($stats as [$num, $lbl]):
    ?>
    <div class="stat-item">
        <div class="stat-num"><?= $num ?></div>
        <div class="stat-lbl"><?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── FEATURES ──────────────────────────────────────────── -->
<div style="background:var(--bg);">
    <div class="section">
        <h2 class="section-title">EVERYTHING YOU NEED</h2>
        <p class="section-sub">COMPLETE FEATURES FOR MORE EFFECTIVE LEARNING
</p>
        <div class="features-grid">
            <?php
            $features = [
                ['📄', 'QUESTION BANK', 'Past year papers, trial exams and revision for Primary, Secondary, STPM and University all in one place.'],
                ['✦',  'AI SUMMARIZER', 'Paste your notes and the AI ​​will summarize them into key points, key terms and difficulty level within a few seconds.'],
                ['⬡',  'STUDY ROOMS', 'Create or join study rooms to discuss topics, share materials and learn with other students & lecturers.'],
                ['⬆',  'OPEN UPLOAD',  'All users can contribute papers to the community. Each upload will be reviewed by the admin before being displayed.'],
                ['◈',  'PROGRESS TRACKER', 'Track study hours, scores and papers completed for each subject. Monitor your learning progress.'],
                ['✉',  'DIRECT MESSAGE',   'Send messages directly to students or lecturers. Ask questions, request notes or just chat.'],
            ];
            foreach ($features as [$icon, $title, $desc]):
            ?>
            <div class="feature-card">
                <span class="feature-icon"><?= $icon ?></span>
                <h3><?= $title ?></h3>
                <p><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- hows works -->
<div style="background:var(--bg-card);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
    <div class="section">
        <h2 class="section-title">HOW DOES IT WORK?</h2>
        <p class="section-sub">4 EASY STEPS TO START LEARNING</p>
        <div class="steps-grid">
            <?php
            $steps = [
                ['1', 'REGISTER ACCOUNT', 'Register for free as a Student or Lecturer in a few minutes.'],
                ['2', 'CHOOSE LEVEL',  'Set your education level Primary, Secondary, STPM or University.'],
                ['3', 'EXPLORE CONTENT', 'Browse past year papers, join study rooms or use AI summarizer.'],
                ['4', 'STUDY TOGETHER', 'Interact with other students and lecturers to learn more effectively.'],
            ];
            foreach ($steps as [$num, $title, $desc]):
            ?>
            <div class="step-card">
                <div class="step-num"><?= $num ?></div>
                <h3><?= $title ?></h3>
                <p><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- user role -->
<div style="background:var(--bg);">
    <div class="section">
        <h2 class="section-title">FOR ALL TYPE USERS</h2>
        <p class="section-sub">3 DIFFERENT ROLES WITH APPROPRIATE FEATURES</p>
        <div class="roles-grid">
            <?php
            $roles = [
                ['📚', 'STUDENT', 'var(--teal)', [
                    'Browse & download past year papers',
                    'Upload papers (pending approval)',
                    'Join & create study rooms',
                    'Use AI note summarizer',
                    'Track subject progress',
                    'Direct messaging',
                ]],

            ['🎓', 'LECTURER', 'var(--amber)', [
                'Upload & manage papers',
                'Create & manage study rooms',
                'Guide students in discussions',
                'Use AI summarizer',
                'Direct messaging',
                'View joined study rooms',
            ]],

            ['🛡️', 'ADMIN', 'var(--danger)', [
                'Approve / reject paper uploads',
                'Manage all users',
                'Monitor study rooms',
                'View system analytics',
                'Ban / unban users',
    '           Delete inappropriate content',
            ]],
        ];
            foreach ($roles as [$icon, $role, $color, $items]):
            ?>
            <div class="role-card" style="border-color:<?= $color ?>44;">
                <div style="font-size:28px;margin-bottom:8px;"><?= $icon ?></div>
                <div style="font-size:16px;font-weight:600;color:<?= $color ?>;"><?= $role ?></div>
                <ul>
                    <?php foreach ($items as $item): ?>
                    <li><?= $item ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>