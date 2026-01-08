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

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $classId = intval($_GET['id']);

    $sql = "SELECT * FROM `class` WHERE `cid` = $classId";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Class not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>