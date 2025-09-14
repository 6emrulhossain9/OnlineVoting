<?php
session_start();
header('Content-Type: application/json');
include 'connect.php';

// If a specific event_id is requested, allow user access and return only those candidates
if (isset($_GET['event_id'])) {
    $eventId = intval($_GET['event_id']);
    $candidates = [];
    if ($eventId > 0) {
        // Include photo column if present
        $hasPhotoCol = false;
        $rs = $conn->query("SHOW COLUMNS FROM candidates LIKE 'photo'");
        if ($rs && $rs->num_rows > 0) { $hasPhotoCol = true; }
        if ($rs) { $rs->close(); }

        $select = $hasPhotoCol
            ? "SELECT id, name, position, event_id, votes, photo FROM candidates WHERE event_id = ?"
            : "SELECT id, name, position, event_id, votes FROM candidates WHERE event_id = ?";
        $stmt = $conn->prepare($select);
        $stmt->bind_param('i', $eventId);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $candidates[] = $row;
            }
        }
        $stmt->close();
    }
    echo json_encode($candidates);
    $conn->close();
    exit();
}

// Otherwise, only admins can fetch the full list (with event title and live vote counts)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    $conn->close();
    exit();
}

$sql = "SELECT c.*, e.title AS event_title FROM candidates c JOIN events e ON c.event_id = e.id";
$result = $conn->query($sql);

$candidates = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cid = (int)$row['id'];
        $voteRes = $conn->query("SELECT COUNT(*) as vote_count FROM votes WHERE candidate_id = $cid");
        $voteRow = $voteRes ? $voteRes->fetch_assoc() : ['vote_count' => 0];
        $row['votes'] = (int)$voteRow['vote_count'];
        $candidates[] = $row;
    }
}

echo json_encode($candidates);
$conn->close();
?>