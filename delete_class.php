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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $classId = intval($_POST['id']);

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("DELETE FROM `class` WHERE `cid` = ?");
    $stmt->bind_param("i", $classId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Class deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting class: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>