<?php
session_start();
include 'connect.php';

// This endpoint returns candidates with votes grouped by event for public/user dashboards.
// It does not require admin session and only exposes necessary fields.

$sql = "SELECT c.id, c.name, c.position, c.event_id, c.votes FROM candidates c";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
