<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cims";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'], $_POST['classname'], $_POST['rank'])) {
    $classId = intval($_POST['id']);
    $className = $conn->real_escape_string($_POST['classname']);
    $sessionRank = intval($_POST['rank']);

    $sql = "UPDATE `class` SET `classname` = '$className', `rank` = $sessionRank WHERE `cid` = $classId";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Class updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating class: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>