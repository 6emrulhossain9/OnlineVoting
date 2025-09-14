<?php
include 'connect.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Access Denied!";
    exit();
}
$title = $_POST['title'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
// Use prepared statement
$stmt = $conn->prepare("INSERT INTO events (title, start_time, end_time) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $title, $start_time, $end_time);
if ($stmt->execute()) {
    echo "Event added successfully";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>