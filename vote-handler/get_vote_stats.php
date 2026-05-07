<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_SERVER['HTTP_X_CSRF_TOKEN']) ||
    !isset($_SESSION['csrf_token']) ||
    $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']
) {
    echo json_encode(['error' => 'Invalid request or CSRF token.']);
    exit;
}

try {
    // Total registered voters
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
    $stmt->execute(['voter']);
    $total_voters = (int)$stmt->fetchColumn();

    // Total voted (unique voters)
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT voter_id) FROM votes');
    $stmt->execute();
    $voted_count = (int)$stmt->fetchColumn();

    echo json_encode([
        'total_voters' => $total_voters,
        'voted_count' => $voted_count
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}