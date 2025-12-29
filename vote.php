<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'voter') {
    echo "Access Denied!";
    exit;
}

// Helper: fetch user id
$user_id = null;
$stmt_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt_user->bind_param("s", $_SESSION['username']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user && $result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $user_id = (int)$user_data['id'];
}
$stmt_user->close();

if (!$user_id) {
    echo "User not found.";
    exit;
}

// Bulk votes path: accept a JSON array in 'votes'
if (isset($_POST['votes'])) {
    $raw = $_POST['votes'];
    $votes = is_array($raw) ? $raw : json_decode($raw, true);
    if (!is_array($votes)) {
        echo "Invalid vote data provided.";
        exit;
    }

    $conn->begin_transaction();
    try {
        foreach ($votes as $v) {
            $candidate_id = isset($v['candidate_id']) ? (int)$v['candidate_id'] : 0;
            $event_id = isset($v['event_id']) ? (int)$v['event_id'] : 0;
            if ($candidate_id <= 0 || $event_id <= 0) {
                throw new Exception('Invalid candidate or event id.');
            }

            // Check event window using database time (avoids PHP timezone mismatch)
            $stmt_evt = $conn->prepare("SELECT start_time, end_time, (NOW() < start_time) AS not_started, (NOW() > end_time) AS is_closed FROM events WHERE id = ?");
            $stmt_evt->bind_param("i", $event_id);
            $stmt_evt->execute();
            $res_evt = $stmt_evt->get_result();
            $evt = $res_evt ? $res_evt->fetch_assoc() : null;
            $stmt_evt->close();
            if (!$evt) { throw new Exception('Event not found.'); }
            if ((int)$evt['not_started'] === 1) { throw new Exception('Voting has not started for one of the selected events.'); }
            if ((int)$evt['is_closed'] === 1) { throw new Exception('Voting is closed for one of the selected events.'); }

            // Already voted in this event?
            $stmt_check = $conn->prepare("SELECT COUNT(*) AS count FROM votes WHERE user_id = ? AND event_id = ?");
            $stmt_check->bind_param("ii", $user_id, $event_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $row = $res_check ? $res_check->fetch_assoc() : ['count' => 0];
            $stmt_check->close();
            if ((int)$row['count'] > 0) {
                throw new Exception('You have already voted in one of the selected events.');
            }

            // Increment candidate votes (ensure candidate belongs to event)
            $stmt_candidate = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ? AND event_id = ?");
            $stmt_candidate->bind_param("ii", $candidate_id, $event_id);
            if (!$stmt_candidate->execute() || $stmt_candidate->affected_rows !== 1) {
                $err = $stmt_candidate->error ?: 'Candidate not found for event.';
                $stmt_candidate->close();
                throw new Exception("Failed to update candidate votes: $err");
            }
            $stmt_candidate->close();

            // Record vote
            $stmt_record_vote = $conn->prepare("INSERT INTO votes (user_id, candidate_id, event_id) VALUES (?, ?, ?)");
            $stmt_record_vote->bind_param("iii", $user_id, $candidate_id, $event_id);
            if (!$stmt_record_vote->execute()) {
                $err = $stmt_record_vote->error;
                $stmt_record_vote->close();
                throw new Exception("Failed to record vote: $err");
            }
            $stmt_record_vote->close();
        }

        $conn->commit();
        echo "Vote successful!";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Vote failed: " . $e->getMessage();
    }

    $conn->close();
    exit;
}

// Single vote path (legacy)
$candidate_id = isset($_POST['candidate_id']) ? (int)$_POST['candidate_id'] : 0;
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if ($candidate_id <= 0 || $event_id <= 0) {
    echo "Invalid vote data provided.";
    exit;
}

// Check event time window (must be currently active) using database time
$stmt_evt = $conn->prepare("SELECT start_time, end_time, (NOW() < start_time) AS not_started, (NOW() > end_time) AS is_closed FROM events WHERE id = ?");
$stmt_evt->bind_param("i", $event_id);
$stmt_evt->execute();
$res_evt = $stmt_evt->get_result();
$evt = $res_evt ? $res_evt->fetch_assoc() : null;
$stmt_evt->close();
if (!$evt) { echo "Event not found."; exit; }
if ((int)$evt['not_started'] === 1) { echo "Voting has not started for this event."; exit; }
if ((int)$evt['is_closed'] === 1) { echo "Voting is closed for this event."; exit; }

// Check if user has already voted in this specific event
$stmt_check = $conn->prepare("SELECT COUNT(*) AS count FROM votes WHERE user_id = ? AND event_id = ?");
$stmt_check->bind_param("ii", $user_id, $event_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row = $result_check ? $result_check->fetch_assoc() : ['count' => 0];
$stmt_check->close();

if ((int)$row['count'] > 0) {
    echo "You have already voted in this event!";
    exit;
}

$conn->begin_transaction();
try {
    // 1. Increment candidate's votes
    $stmt_candidate = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ? AND event_id = ?");
    $stmt_candidate->bind_param("ii", $candidate_id, $event_id);
    if (!$stmt_candidate->execute() || $stmt_candidate->affected_rows !== 1) {
        $err = $stmt_candidate->error ?: 'Candidate not found for event.';
        $stmt_candidate->close();
        throw new Exception("Failed to update candidate votes: $err");
    }
    $stmt_candidate->close();

    // 2. Record the vote in the votes table
    $stmt_record_vote = $conn->prepare("INSERT INTO votes (user_id, candidate_id, event_id) VALUES (?, ?, ?)");
    $stmt_record_vote->bind_param("iii", $user_id, $candidate_id, $event_id);
    if (!$stmt_record_vote->execute()) {
        $err = $stmt_record_vote->error;
        $stmt_record_vote->close();
        throw new Exception("Failed to record vote: $err");
    }
    $stmt_record_vote->close();

    $conn->commit();
    echo "Vote successful!";
} catch (Exception $e) {
    $conn->rollback();
    echo "Vote failed: " . $e->getMessage();
}

$conn->close();
?>
