<?php
session_start();
header('Content-Type: application/json');
include 'connect.php';


$sql = "SELECT id, title, start_time, end_time FROM events ORDER BY start_time ASC";
$result = $conn->query($sql);

$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

echo json_encode($events);
$conn->close();
?>