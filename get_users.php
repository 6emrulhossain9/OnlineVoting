<?php
include 'connect.php';

// Check if an admin is logged in
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit();
}

// Select all columns except password for security
// Include email if the column exists
$hasEmail = false;
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($res && $res->num_rows > 0) { $hasEmail = true; }
if ($res) { $res->close(); }

$sql = $hasEmail
    ? "SELECT id, username, full_name, student_id, phone_number, department, section, email, role FROM users"
    : "SELECT id, username, full_name, student_id, phone_number, department, section, role FROM users";
$result = $conn->query($sql);

$users = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);

$conn->close();
?>