<?php
session_start();

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function flash($msg = null) {
    if ($msg) {
        $_SESSION['flash'] = $msg;
    } elseif (isset($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="flash-message success">' . e($m) . '</div>';
    }
    return '';
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    $valid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    if ($valid) unset($_SESSION['csrf_token']);
    return $valid;
}

function get_poster_url($poster) {
    if ($poster && file_exists(__DIR__ . '/../uploads/' . $poster)) {
        return '../uploads/' . $poster;
    }
    return 'https://via.placeholder.com/320x480?text=' . urlencode(substr($poster ?: 'No Poster', 0, 20));
}