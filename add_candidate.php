<?php
include 'connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Access Denied!";
    exit();
}
$name = $_POST['name'];
$position = $_POST['position'];
$event_id = $_POST['event_id']; // New: Get event_id

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO candidates (name, position, event_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $position, $event_id); // 'i' for integer event_id
if ($stmt->execute()) {
    echo "Candidate added successfully";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>