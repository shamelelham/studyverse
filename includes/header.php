<?php

// default title kalau tak set
if (!isset($pageTitle)) {
    $pageTitle = 'StudyVerse';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="STUDYVERSE — YOUR UNIVERSE OF LEARNING">

    <title><?= e($pageTitle) ?> — StudyVerse</title>

    <!-- main CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <!-- favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png">
</head>
<body>