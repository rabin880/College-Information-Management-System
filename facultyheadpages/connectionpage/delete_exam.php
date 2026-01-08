<?php
// Include your database connection file
include 'db_connection.php'; // Adjust the path if necessary

if (isset($_GET['exam_id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $examId = intval($_GET['exam_id']);

    // Step 1: Delete associated marks
    $deleteMarksQuery = "DELETE FROM marks WHERE exam_id = ?";
    $deleteMarksStmt = $conn->prepare($deleteMarksQuery);
    $deleteMarksStmt->bind_param("i", $examId);
    $deleteMarksStmt->execute();
    $deleteMarksStmt->close();

    // Step 2: Delete the exam
    $deleteExamQuery = "DELETE FROM exam WHERE exam_id = ?";
    $deleteExamStmt = $conn->prepare($deleteExamQuery);
    $deleteExamStmt->bind_param("i", $examId);

    if ($deleteExamStmt->execute()) {
        echo "<script>alert('Exam and associated marks deleted successfully!'); window.location.href = '../facultyheadexam.php';</script>";
    } else {
        echo "<script>alert('Error deleting exam: " . $conn->error . "'); window.location.href = '../facultyheadexam.php';</script>";
    }
    $deleteExamStmt->close();
} else {
    // Redirect if confirmation is not provided
    header("Location: ../facultyheadexam.php");
    exit();
}
?>