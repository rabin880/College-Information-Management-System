<?php
session_start();
include 'db_connection.php'; // Include your database connection file

if (isset($_POST['class_id'])) {
    $classId = $_POST['class_id'];

    // Fetch exams for the selected class
    $query = "SELECT exam_id, exam_name FROM exam WHERE class_id = $classId";
    $result = $conn->query($query);

    $options = '<option value="">Select Exam</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['exam_id']}'>{$row['exam_name']}</option>";
    }
    echo $options;
}
?>