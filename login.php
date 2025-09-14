<?php
session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = null;
    $password = null;

    // Check if the form is for the admin or voter
    $login_type = null;
    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $login_type = 'admin';
    } elseif (isset($_POST['voter'])) {
        $username = $_POST['voter'];
        $password = $_POST['password'];
        $login_type = 'voter';
    }

    if ($username && $password) {
        // Find the user in the database
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Check if the role matches the form type
            $role = $row['role'];
            $role_matches =
                ($login_type === 'admin' && $role === 'admin') ||
                ($login_type === 'voter' && $role === 'voter');

            if (!$role_matches) {
                if ($login_type === 'admin') {
                    header("Location: index.html?error=admin");
                } else {
                    header("Location: index.html?error=1");
                }
                exit();
            }

            // Verify the password
            if (password_verify($password, $row['password'])) {
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $row['id']; // Store user_id in session
                $_SESSION['role'] = $row['role'];

                if ($row['role'] === 'admin') {
                    // Set JS sessionStorage flag for admin and redirect
                    echo '<script>sessionStorage.setItem("admin_logged_in", "1");window.location.href="admin_dashboard.html";</script>';
                    exit();
                } elseif ($row['role'] === 'voter') {
                    echo '<script>sessionStorage.setItem("voter_logged_in", "1");window.location.href="user_dashboard.html";</script>';
                    exit();
                } else {
                    // Handle other roles if necessary, or redirect to a default page
                    header("Location: index.html");
                    exit();
                }
            } else {
                if ($login_type === 'admin') {
                    header("Location: index.html?error=admin");
                } else {
                    header("Location: index.html?error=1");
                }
                exit();
            }
        } else {
            if ($login_type === 'admin') {
                header("Location: index.html?error=admin");
            } else {
                header("Location: index.html?error=1");
            }
            exit();
        }

        $stmt->close();
    }
}

$conn->close();
?>