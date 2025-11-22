<?php
require_once('conn.php');

$currentUser = null;

if (isset($_COOKIE['session_token'])) {
    $sessionToken = $_COOKIE['session_token'];

    $stmt = $conn->prepare("SELECT sessions.user_id, users.username, users.email, users.role, users.avatar_filename, users.balance FROM sessions JOIN users ON sessions.user_id = users.id WHERE sessions.session_token = ? AND sessions.expires_at > NOW()");
    $stmt->bind_param("s", $sessionToken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $currentUser = $result->fetch_assoc();
    } else {
        setcookie('session_token', '', time() - 3600, "/");
    }
}
?>
