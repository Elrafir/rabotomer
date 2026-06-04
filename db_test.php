<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "time_tracker");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$hash = password_hash('123456', PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE username = 'artist'");
$stmt->bind_param("s", $hash);
$stmt->execute();

echo "Updated hash for artist to: " . $hash . "\n";
$mysqli->close();
?>
