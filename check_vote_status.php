<?php
include 'connect.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'voter') {
    echo json_encode(['has_voted' => false, 'error' => 'Access Denied']);
    exit();
}

$user_id = null;
$event_id = $_GET['event_id'] ?? null;

// Get user_id from session username
$stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt_user->bind_param("s", $_SESSION['username']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $user_id = $user_data['id'];
}
$stmt_user->close();

if (!$user_id || !$event_id) {
    echo json_encode(['has_voted' => false, 'error' => 'Invalid request']);
    exit();
}

// Check if user has voted in this event
$stmt_check = $conn->prepare("SELECT COUNT(*) AS count FROM votes WHERE user_id = ? AND event_id = ?");
$stmt_check->bind_param("ii", $user_id, $event_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row = $result_check->fetch_assoc();
$has_voted = ($row['count'] > 0);

echo json_encode(['has_voted' => $has_voted]);

$stmt_check->close();
$conn->close();
?>