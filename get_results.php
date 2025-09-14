<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Access Denied!";
    exit;
}

// Modified SQL to include event title
$sql = "SELECT c.*, e.title AS event_title FROM candidates c JOIN events e ON c.event_id = e.id ORDER BY c.votes DESC";
$result = $conn->query($sql);

$candidates = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
}

echo json_encode($candidates);

$conn->close();
?>