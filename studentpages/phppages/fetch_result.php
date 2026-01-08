<?php
session_start();
include 'db_connection.php'; // Include your database connection file

if (isset($_POST['exam_id'])) {
    $examId = $_POST['exam_id'];
    $studentId = $_SESSION['user_id']; // Get student ID from session

    // Fetch result for the selected exam and student
    $query = "SELECT total_credits, grade, gpa, rank 
              FROM student_results 
              WHERE exam_id = $examId AND student_id = $studentId";
    $result = $conn->query($query);

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        echo "<div class='alert alert-success'>
                <h5>Result Details</h5>
                <p><strong>Total Marks:</strong> {$row['total_marks']}</p>
                <p><strong>Grade:</strong> {$row['grade']}</p>
                <p><strong>GPA:</strong> {$row['gpa']}</p>
                <p><strong>Rank:</strong> {$row['rank']}</p>
              </div>";
    } else {
        echo "<div class='alert alert-warning'>No result found for this exam.</div>";
    }
}
?>