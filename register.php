<?php
session_start();
include 'connect.php'; // Your database connection file

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $full_name = clean($_POST['full_name'] ?? '');
    $student_id = clean($_POST['student_id'] ?? '');
    $phone_number = clean($_POST['phone_number'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $section = clean($_POST['section'] ?? '');
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // Position is no longer directly stored in users table for candidates
    // It's handled in the candidates table linked to an event.

    // Basic validation
    if (!$full_name || !$student_id || !$phone_number || !$department || !$section || !$username || !$password || !$email) {
        echo "Please fill in all required fields.";
        exit;
    }
    // Minimal email validation: must contain '@'
    if (strpos($email, '@') === false) {
        echo "Please provide a valid email address.";
        exit;
    }
    if ($role !== 'voter' && $role !== 'candidate') {
        echo "Invalid role specified.";
        exit;
    }

    // Hash password securely
    $password_hash = password_hash($password, PASSWORD_DEFAULT);


    // Helper: check uniqueness in users table for a column/value
    $checkUnique = function($column, $value, $label) use ($conn) {
        $sql = "SELECT id FROM users WHERE $column = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        if ($exists) {
            echo "$label already registered. Please use a different $label.";
            exit;
        }
    };

    if ($role === 'voter') {
        // Enforce uniqueness across users
        $checkUnique('username', $username, 'Username');
        $checkUnique('student_id', $student_id, 'Student ID');
        $checkUnique('phone_number', $phone_number, 'Phone Number');
        $checkUnique('email', $email, 'Email');

        // Insert voter into users table with all info
        $role_value = 'voter';
        // Detect if 'email' column exists in users
        $hasUserEmail = false;
        $rsEU = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
        if ($rsEU && $rsEU->num_rows > 0) { $hasUserEmail = true; }
        if ($rsEU) { $rsEU->close(); }

        if ($hasUserEmail) {
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, student_id, phone_number, department, section, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $username, $full_name, $student_id, $phone_number, $department, $section, $email, $password_hash, $role_value);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, student_id, phone_number, department, section, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $username, $full_name, $student_id, $phone_number, $department, $section, $password_hash, $role_value);
        }
        if ($stmt->execute()) {
            echo "Voter registration successful! You can now login.";
        } else {
            echo "Registration failed: " . $stmt->error;
        }
        $stmt->close();
    } else if ($role === 'candidate') {
        // Candidate registration should allow using existing user credentials
        $position = clean($_POST['position'] ?? '');
        $event_id = intval($_POST['event_id'] ?? 0);

        // Prevent duplicate candidate for the same event (by name + event)
        $stmt = $conn->prepare("SELECT id FROM candidates WHERE name = ? AND event_id = ? LIMIT 1");
        $stmt->bind_param("si", $full_name, $event_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "You are already registered as a candidate for this event.";
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Check if a user already exists with this username; if yes, verify password
        $existingUserId = null;
        $existingRole = null;
        $existingHash = null;
        $stmt = $conn->prepare("SELECT id, role, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $existingUserId = (int)$row['id'];
                $existingRole = $row['role'];
                $existingHash = $row['password'];
            }
        }
        $stmt->close();

        $createNewUser = $existingUserId === null;
        if (!$createNewUser) {
            if (!$existingHash || !password_verify($password, $existingHash)) {
                echo "Username already exists. Please enter your existing password to continue.";
                exit;
            }
        }

        // Handle optional photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $file = $_FILES['photo'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (isset($allowed[$mime]) && $file['size'] <= 2 * 1024 * 1024) {
                    $ext = $allowed[$mime];
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', strtolower($full_name));
                    $destDir = __DIR__ . DIRECTORY_SEPARATOR . 'can_photos';
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, 0755, true);
                    }
                    $filename = $safeName . '_' . time() . '.' . $ext;
                    $destPath = $destDir . DIRECTORY_SEPARATOR . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        // store relative path for web access
                        $photoPath = 'can_photos/' . $filename;
                    }
                }
            }
        }

        // Insert candidate into candidates table with all info (including optional photo if column exists)
        // Detect if 'photo' column exists
        $hasPhotoCol = false;
        $rs = $conn->query("SHOW COLUMNS FROM candidates LIKE 'photo'");
        if ($rs && $rs->num_rows > 0) { $hasPhotoCol = true; }
        if ($rs) { $rs->close(); }

        // Detect if 'email' column exists in candidates
        $hasCandidateEmail = false;
        $rsCE = $conn->query("SHOW COLUMNS FROM candidates LIKE 'email'");
        if ($rsCE && $rsCE->num_rows > 0) { $hasCandidateEmail = true; }
        if ($rsCE) { $rsCE->close(); }
        // Detect if 'email' column exists in users (for creating login)
        $hasUserEmail = false;
        $rsEU = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
        if ($rsEU && $rsEU->num_rows > 0) { $hasUserEmail = true; }
        if ($rsEU) { $rsEU->close(); }

        // Transaction: insert candidate and corresponding user atomically
        $conn->begin_transaction();
        try {
            // Build candidate insert based on available columns
            $columns = ['name', 'position', 'event_id', 'votes'];
            $placeholders = ['?', '?', '?', '0'];
            $types = 'ssi';
            $values = [$full_name, $position, $event_id];

            // Discover existing columns
            $colsRes = $conn->query("SHOW COLUMNS FROM candidates");
            $existingCols = [];
            if ($colsRes) {
                while ($c = $colsRes->fetch_assoc()) { $existingCols[$c['Field']] = true; }
                $colsRes->close();
            }

            $maybeAdd = function($col, $val, $type) use (&$columns, &$placeholders, &$types, &$values, $existingCols) {
                if (isset($existingCols[$col])) {
                    $columns[] = $col;
                    $placeholders[] = '?';
                    $types .= $type;
                    $values[] = $val;
                }
            };

            $maybeAdd('student_id', $student_id, 's');
            $maybeAdd('phone_number', $phone_number, 's');
            $maybeAdd('department', $department, 's');
            $maybeAdd('section', $section, 's');
            if ($hasCandidateEmail) { $maybeAdd('email', $email, 's'); }
            if ($hasPhotoCol) { $maybeAdd('photo', $photoPath, 's'); }

            $sql = "INSERT INTO candidates (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) { throw new Exception($stmt->error); }
            $stmt->close();

            // Create user record if needed. Keep role as 'voter' so candidate can still log in using voter portal.
            if ($createNewUser) {
                $role_value = 'voter';
                if ($hasUserEmail) {
                    $stmt = $conn->prepare("INSERT INTO users (username, full_name, student_id, phone_number, department, section, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssss", $username, $full_name, $student_id, $phone_number, $department, $section, $email, $password_hash, $role_value);
                } else {
                    $stmt = $conn->prepare("INSERT INTO users (username, full_name, student_id, phone_number, department, section, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssss", $username, $full_name, $student_id, $phone_number, $department, $section, $password_hash, $role_value);
                }
                if (!$stmt->execute()) { throw new Exception($stmt->error); }
                $stmt->close();
            }

            $conn->commit();
            echo "Candidate registration successful! You can now login as a voter.";
        } catch (Exception $ex) {
            $conn->rollback();
            echo "Registration failed: " . $ex->getMessage();
        }
    }

    $conn->close();
} else {
    echo "Invalid request method.";
}
?>