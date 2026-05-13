<?php
$user = currentUser();
$curPage = basename($_SERVER['PHP_SELF'], '.php');

$adminNav = [
    ['page' => 'DASHBORD', 'href' => BASE_URL.'/admin/dashboard.php', 'icon' => '⬡', 'label' => 'DASHBOARD'],
    ['page' => 'USERS', 'href' => BASE_URL.'/admin/users.php', 'icon' => '👥', 'label' => 'MANAGE USERS'],
    ['page' => 'PAPERS', 'href' => BASE_URL.'/admin/papers.php', 'icon' => '📄', 'label' => 'APPROVE PAPERS'],
    ['page' => 'ROOMS', 'href' => BASE_URL.'/admin/rooms.php', 'icon' => '⬡', 'label' => 'MONITOR ROOMS'],
];

$studentNav = [
    ['page' => 'DASHBOARD', 'href' => BASE_URL.'/dashboard.php', 'icon' => '⬡', 'label' => 'DASHBOARD'],
    ['page' => 'PAPERS', 'href' => BASE_URL.'/papers.php', 'icon' => '📄', 'label' => 'PAPERS'],
    ['page' => 'UPLOAD', 'href' => BASE_URL.'/upload.php', 'icon' => '⬆', 'label' => 'UPLOAD PAPER'],
    ['page' => 'SUMMERIZER', 'href' => BASE_URL.'/summarizer.php', 'icon' => '✦', 'label' => 'AI SUMMARIZER'],
    ['page' => 'STUDYROOMS', 'href' => BASE_URL.'/studyroom.php', 'icon' => '⬡', 'label' => 'STUDY ROOMS'],
    ['page' => 'MESSAGE', 'href' => BASE_URL.'/messages.php', 'icon' => '✉', 'label' => 'MESSAGES'],
    ['page' => 'PROGRESS', 'href' => BASE_URL.'/progress.php', 'icon' => '◈', 'label' => 'MY PROGRESS'],
];

$lecturerNav = [
    ['page' => 'DASHBOARD', 'href' => BASE_URL.'/dashboard.php', 'icon' => '⬡', 'label' => 'DASHBOARD'],
    ['page' => 'PAPERS', 'href' => BASE_URL.'/papers.php', 'icon' => '📄', 'label' => 'PAPERS'],
    ['page' => 'UPLOAD', 'href' => BASE_URL.'/upload.php', 'icon' => '⬆', 'label' => 'UPLOAD PAPER'],
    ['page' => 'SUMMARIZER', 'href' => BASE_URL.'/summarizer.php', 'icon' => '✦', 'label' => 'AI SUMMARIZER'],
    ['page' => 'STUDYROOMS', 'href' => BASE_URL.'/studyroom.php', 'icon' => '⬡', 'label' => 'STUDY ROOMS'],
    ['page' => 'MESSAGE', 'href' => BASE_URL.'/messages.php', 'icon' => '✉', 'label' => 'STUDY ROOMS'],
];

$bottomNav = [
    ['page' => 'PROFILE', 'href' => BASE_URL.'/profile.php', 'icon' => '◎', 'label' => 'PROFILE'],
    ['page' => 'ABOUT', 'href' => BASE_URL.'/about.php', 'icon' => 'ℹ', 'label' => 'ABOUT'],
    ['page' => 'HELP', 'href' => BASE_URL.'/help.php', 'icon' => '?', 'label' => 'HELP'],
];

// nav ikut role
if ($user['role'] === 'Admin') $navItems = $adminNav;
elseif ($user['role'] === 'Lecturer') $navItems = $lecturerNav;
else $navItems = $studentNav;

// role badge & avatar color
$roleColor  = match($user['role']) {
    'ADMIN' => 'badge-danger',
    'LECTURER' => 'badge-amber',
    default  => 'badge-teal'
};
$avatarColor = match($user['role']) {
    'ADMIN' => 'avatar-danger',
    'LECTURER' => 'avatar-amber',
    default => 'avatar-accent'
};
?>

<aside class="sidebar">

    <!-- logo -->
    <img src="<?= BASE_URL ?>/assets/images/logo.png" 
                alt="StudyVerse Logo"
                class="logo-img">

    <!-- role badge -->
    <div class="sidebar-role">
        <span class="badge <?= $roleColor ?>"><?= e($user['role']) ?></span>
    </div>

    <!-- main nav -->
    <nav>
        <?php foreach ($navItems as $item): ?>
            <a href="<?= $item['href'] ?>"
               class="nav-link <?= $curPage === $item['page'] ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>

        <!-- bottom nav — student & lecturer only -->
        <?php if ($user['role'] !== 'Admin'): ?>
            <div class="sidebar-divider"></div>
            <?php foreach ($bottomNav as $item): ?>
                <a href="<?= $item['href'] ?>"
                   class="nav-link <?= $curPage === $item['page'] ? 'active' : '' ?>">
                    <span class="nav-icon"><?= $item['icon'] ?></span>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <!-- user info + logout -->
    <div class="sidebar-user">
        <a href="<?= BASE_URL ?>/profile.php" class="sidebar-user-btn">
            <div class="avatar avatar-sm <?= $avatarColor ?>">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div style="overflow:hidden;">
                <div class="sidebar-user-name"><?= e($user['name']) ?></div>
                <div class="sidebar-user-role"><?= e($user['level'] ?: $user['role']) ?></div>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
            ↩ LOGOUT
        </a>
    </div>

</aside>