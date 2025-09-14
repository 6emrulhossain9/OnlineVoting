<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== ($_GET['role'] ?? '')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Fetch user id from DB if needed
include 'connect.php';
$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$user_id = $user_data['id'] ?? null;
$full_name = $user_data['full_name'] ?? null;
$stmt->close();
$conn->close();

echo json_encode([
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'user_id' => $user_id,
    'full_name' => $full_name
]);
?>
