<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit;
}

$change_id = (int)($_POST['change_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($change_id === 0 || empty($comment)) {
    $_SESSION['error'] = 'Data tidak lengkap.';
    header("Location: detail.php?id={$change_id}");
    exit;
}

// Check if change exists and user has access
$sql = "SELECT * FROM change_requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $change_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Change request tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$change = $result->fetch_assoc();

// Check permission
if (!canPerformAction('view_all_changes')) {
    $user_id = $_SESSION['user_id'];
    if ($change['requester_id'] != $user_id && $change['assigned_to'] != $user_id) {
        $_SESSION['error'] = 'Anda tidak memiliki akses untuk comment di change request ini.';
        header('Location: list.php');
        exit;
    }
}

// Insert comment
$user_id = $_SESSION['user_id'];
$is_internal = 0; // bisa dimodifikasi jika ingin fitur internal notes

$sql = "INSERT INTO change_comments (change_id, user_id, comment, is_internal) 
        VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $change_id, $user_id, $comment, $is_internal);

if ($stmt->execute()) {
    // Log audit
    logAudit($user_id, 'add_comment', 'change_request', $change_id, null, json_encode(['comment' => substr($comment, 0, 100)]));
    
    $_SESSION['success'] = 'Comment berhasil ditambahkan.';
} else {
    $_SESSION['error'] = 'Gagal menambahkan comment.';
}

header("Location: detail.php?id={$change_id}");
exit;
