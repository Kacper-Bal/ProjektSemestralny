<?php
require_once('conn.php');

$currentUser = null;

if (isset($_COOKIE['session_token'])) {
    $sessionToken = $_COOKIE['session_token'];

    $query = "SELECT sessions.user_id, users.username, users.email, users.role
              FROM sessions
              JOIN users ON sessions.user_id = users.id
              WHERE sessions.session_token = '$sessionToken'
              AND sessions.expires_at > NOW()";

    $result = $conn->query($query);

    if ($result && $result->num_rows === 1) {
        $currentUser = $result->fetch_assoc();
    } else {
        setcookie('session_token', '', time() - 3600, "/");
    }
}
?>
