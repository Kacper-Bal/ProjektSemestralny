<?php
require_once('conn.php');

if (isset($_COOKIE['session_token'])) {
    $sessionToken = $_COOKIE['session_token'];

    $query = "DELETE FROM sessions WHERE session_token = '$sessionToken'";
    $conn->query($query);

    setcookie('session_token', '', time() - 3600, "/");
}

header("Location: index.php");
exit;
?>
