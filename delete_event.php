<?php
session_start();
require 'connect.php';

header('Content-Type: text/plain');

// Only admin can delete events
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Forbidden: Admin access required.';
    exit;
}

$event_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($event_id <= 0) {
    http_response_code(400);
    echo 'Invalid event id.';
    exit;
}

$conn->begin_transaction();
try {
    // Delete votes for candidates in this event
    $sqlVotes = "DELETE v FROM votes v JOIN candidates c ON v.candidate_id = c.id WHERE c.event_id = ?";
    $stmt = $conn->prepare($sqlVotes);
    $stmt->bind_param('i', $event_id);
    if (!$stmt->execute()) { throw new Exception($stmt->error); }
    $stmt->close();

    // Delete candidates for this event
    $sqlCand = "DELETE FROM candidates WHERE event_id = ?";
    $stmt = $conn->prepare($sqlCand);
    $stmt->bind_param('i', $event_id);
    if (!$stmt->execute()) { throw new Exception($stmt->error); }
    $stmt->close();

    // Finally, delete the event
    $sqlEvent = "DELETE FROM events WHERE id = ?";
    $stmt = $conn->prepare($sqlEvent);
    $stmt->bind_param('i', $event_id);
    if (!$stmt->execute()) { throw new Exception($stmt->error); }
    $stmt->close();

    $conn->commit();
    echo 'Event deleted successfully.';
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo 'Failed to delete event: ' . $e->getMessage();
}

$conn->close();
