<?php
session_start();
include 'db_connection.php'; // Include your database connection file

if (isset($_SESSION['user_id'])) {
    $studentId = $_SESSION['user_id'];

    // Fetch faculty and batch of the logged-in student
    $query = "SELECT faculty, batch FROM studentlog WHERE sid = $studentId";
    $result = $conn->query($query);

    if ($result && $result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $facultyId = $student['faculty'];
        $batchId = $student['batch'];

        // Fetch classes for the student's faculty and batch
        $query = "SELECT cid, classname FROM class WHERE faculty = $facultyId AND batch = $batchId";
        $result = $conn->query($query);

        $options = '<option value="">Select Class</option>';
        while ($row = $result->fetch_assoc()) {
            $options .= "<option value='{$row['cid']}'>{$row['classname']}</option>";
        }
        echo $options;
    } else {
        echo '<option value="">No classes found</option>';
    }
} else {
    echo '<option value="">Unauthorized</option>';
}
?>