<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "steam";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}
define('PASSWORD_SALT', 'Kacper');
?>

