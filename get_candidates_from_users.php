<?php
include 'connect.php';

$sql = "SELECT id, full_name AS name, position, 0 AS votes FROM users WHERE role='candidate'";
$result = $conn->query($sql);

$candidates = [];
while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}

header('Content-Type: application/json');
echo json_encode($candidates);
?>