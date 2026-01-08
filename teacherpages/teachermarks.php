<?php
session_start();


// Check if the teacher is logged in
if (!isset($_SESSION['user_id'])) {
    die("No teacher is logged in.");
}

$teacher_id = $_SESSION['user_id']; // Retrieve teacher ID from session

$host = 'localhost';
$user = 'root'; 
$password = ''; 
$database = 'cims'; 
$errorMessage = '';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png';
}




// Fetch batches as per faculty
if (isset($_GET['fetch']) && $_GET['fetch'] === 'batches') {
    $facultyId = $_GET['faculty_id'];
    $query = "SELECT batch_id, batch_year FROM batches WHERE faculty_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
    echo json_encode($batches);
    exit;
}

// Fetch  classes for a given faculty and batch
if (isset($_GET['fetch']) && $_GET['fetch'] === 'classes') {
    $facultyId = intval($_GET['faculty_id']);
    $batchId = intval($_GET['batch_id']);
    
    $query = "SELECT cid, classname, rank FROM class WHERE faculty = ? AND batch = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $facultyId, $batchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    echo json_encode($classes);
    exit;
}

// Fetch exams for a given class and batch
if (isset($_GET['fetch']) && $_GET['fetch'] === 'exam') {
    if (isset($_GET['class_id']) && isset($_GET['batch'])) {
        $classId = intval($_GET['class_id']);  // Ensure class_id is treated as an integer
        $batch = $_GET['batch'];  // Batch can be a string (e.g., "2022")

        $query = "SELECT exam_id, exam_name FROM exam WHERE class_id = ? AND batch = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $classId, $batch);  // Binding class_id (integer) and batch (string)
        $stmt->execute();
        $result = $stmt->get_result();
        $exams = [];

        while ($row = $result->fetch_assoc()) {
            $exams[] = $row;
        }

        echo json_encode($exams);
    } else {
        echo json_encode(['error' => 'Class ID or Batch not provided']);
    }
    exit;
}

// Modify the students fetch endpoint
if (isset($_GET['fetch']) && $_GET['fetch'] === 'students') {
    $facultyId = $_GET['faculty_id']; // Get the faculty parameter
    $batch = $_GET['batch']; // Get the batch parameter

    // Modified query to filter by faculty and batch
    $query = "SELECT sid, name FROM studentlog WHERE faculty = ? AND batch = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $facultyId, $batch); // 'i' for integer faculty, 's' for string batch
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'sid' => $row['sid'],
            'name' => $row['name']
        ];
    }
    
    echo json_encode($students);
    exit;
}


// Fetch subjects for a given exam and logged-in teacher
if (isset($_GET['fetch']) && $_GET['fetch'] === 'subjects') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }

    $examId = intval($_GET['exam_id']); // Ensure exam_id is treated as an integer
    $teacherId = intval($_SESSION['user_id']); // Retrieve teacher_id from session

    $query = "SELECT subject.subid, subject.subname, subject.fullmarks 
              FROM subject 
              JOIN exam_subjects ON subject.subid = exam_subjects.subject_id
              JOIN subject_teacher ON subject.subid = subject_teacher.subject_id
              WHERE exam_subjects.exam_id = ? AND subject_teacher.teacher_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $examId, $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = [];
    
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode($subjects);
    exit;
}



// Fetch existing marks for the given exam and class
if (isset($_GET['fetch']) && $_GET['fetch'] === 'existing_marks') {
    $examId = $_GET['exam_id'];
    $classId = $_GET['class_id'];

    // Query to fetch marks
    $query = "SELECT marks.student_id, marks.subject_id, marks.mark
              FROM marks
              JOIN studentlog ON marks.student_id = studentlog.sid
              WHERE marks.exam_id = ? AND studentlog.classid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $examId, $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Store marks in an associative array
    $existingMarks = [];
    while ($row = $result->fetch_assoc()) {
        $existingMarks[$row['student_id']][$row['subject_id']] = $row['mark'];
    }
    echo json_encode($existingMarks);
    exit;
}


// Handle the form submission for adding or updating marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marks'])) {
    $marks = $_POST['marks'];
    $examId = $_POST['exam_id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        foreach ($marks as $studentId => $studentMarks) {
            foreach ($studentMarks as $subjectId => $mark) {
                // Check if mark is numeric and not empty
                if (trim($mark) !== '' && is_numeric($mark)) {
                    // Fetch full marks for the subject
                    $fullMarksQuery = "SELECT fullmarks FROM subject WHERE subid = ?";
                    $stmtFullMarks = $conn->prepare($fullMarksQuery);
                    $stmtFullMarks->bind_param("i", $subjectId);
                    $stmtFullMarks->execute();
                    $fullMarksResult = $stmtFullMarks->get_result();

                    if ($fullMarksResult->num_rows > 0) {
                        $fullMarksRow = $fullMarksResult->fetch_assoc();
                        $fullMarks = $fullMarksRow['fullmarks'];

                        // Validate obtained marks
                        if ($mark > $fullMarks) {
                            throw new Exception("Obtained marks ($mark) cannot exceed full marks ($fullMarks) for subject ID $subjectId.");
                        }

                        // Check if the mark already exists for this student, subject, and exam
                        $checkQuery = "SELECT * FROM marks WHERE exam_id = ? AND student_id = ? AND subject_id = ?";
                        $stmtCheck = $conn->prepare($checkQuery);
                        $stmtCheck->bind_param("iii", $examId, $studentId, $subjectId);
                        $stmtCheck->execute();
                        $resultCheck = $stmtCheck->get_result();

                        if ($resultCheck->num_rows > 0) {
                            // If mark already exists, update it
                            $updateQuery = "UPDATE marks SET mark = ? WHERE exam_id = ? AND student_id = ? AND subject_id = ?";
                            $stmtUpdate = $conn->prepare($updateQuery);
                            $stmtUpdate->bind_param("iiii", $mark, $examId, $studentId, $subjectId);
                            $stmtUpdate->execute();
                        } else {
                            // If no mark exists, insert a new entry
                            $insertQuery = "INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (?, ?, ?, ?)";
                            $stmtInsert = $conn->prepare($insertQuery);
                            $stmtInsert->bind_param("iiii", $examId, $studentId, $subjectId, $mark);
                            $stmtInsert->execute();
                        }
                    }
                }
            }
        }

        // Commit the transaction
        $conn->commit();
        $errorMessage = 'Marks have been saved successfully!';
    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        $errorMessage = 'Error saving marks: ' . $e->getMessage();
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Dashhboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    display: block;
    height: 100vh;
    background-color: #f0f2f5;
    padding-top: 60px; /* Prevent the content from being hidden under the fixed header */
}

/* Header */
header {
    background-color: rgb(16, 3, 65);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    box-shadow: 0 4px 8px #1514461a;
    border: 2px solid #000;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #283D75;
    padding: 10px;
    position: fixed;
    top: 60px; /* Aligns under the fixed header */
    bottom: 0;
    left: 0; /* Ensures it is aligned to the left */
    overflow-y: auto;
    scrollbar-width: none; /* Hide scrollbar on Firefox */
    border-right: 2px solid #000; /* Add subtle border for separation */
    z-index: 999; /* Ensure the sidebar is above other content */
}

/* Hide scrollbar on WebKit browsers (Chrome, Safari) */
.sidebar::-webkit-scrollbar {
    display: none;
}

/* Sidebar links */
.sidebar ul {
    list-style-type: none;
}

.sidebar ul li {
    margin-bottom: 12px; /* Reduce this value to decrease the space between items */
}

.sidebar ul li a {
    text-decoration: none;
    color: white;
    font-size: 15px;
    padding: 12px 8px; /* Adjust the padding (vertical horizontal) between menu items */
    display: block;  /* Make the link fill the entire area, improving clickability */
    border-radius: 4px; /* Optional: Adds rounded corners to the menu items */
    transition: background-color 0.2s ease; /* Smooth hover effect */
}

/* Main Content */
.main-content {
    margin-left: 160px; /* Set the same margin as sidebar width */
    padding: 20px;
    height: calc(100vh - 60px); /* Full height minus header */
    background-color: white; /* Match body background */
    overflow-y: auto; /* Scrollbars on vertical content */
    scrollbar-width: none; /* Hide scrollbar in Firefox */
    overflow-x: auto; /* Allow horizontal scrolling if needed */
    z-index: 1; /* Ensure content appears above the sidebar */
    position: relative;
}

/* Hide scrollbar in WebKit browsers (Chrome, Safari) */
.main-content::-webkit-scrollbar {
    display: none;
}



</style>
</head>

<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2>Teacher</h2>
    <a href="logout.php" class="btn btn-danger" id="logout-btn">Log out</a>
</header>   

<div class="d-flex">
   <!-- Sidebar navigation -->
   <nav class="sidebar bg-dark text-white p-3" style="width: 150px;">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex align-items-center" href="teacherdashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> 
                    Dashboard
                </a>
            </li>

           

            <!-- Marks -->
            <li class="nav-item">
                <a href="teachermarks.php" class="nav-link text-white">
                <i class="fas fa-clipboard-list me-2"></i> Marks
                </a>
            </li>

            <!-- Result -->
            <li class="nav-item">
                <a href="teacherresult.php" class="nav-link text-white">
                    <i class="fas fa-chart-line me-2"></i> Result
                </a>
            </li>

           
        </ul>
    </nav>

    <!-- Main content area -->
    <div class="main-content flex-grow-1 p-3">
        <div class="container mt-5 ">
            <form method="POST" id="marksForm">
                <div class="form-group-wrapper">
                    <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="faculty" class="form-label">Faculty</label>
                        <select class="form-select" id="faculty" name="faculty">
                            <option value="" selected>Select Faculty</option>
                            <?php
                            $faculty_id = $_SESSION['faculty_type']; // Get faculty ID from session
                            $query = "SELECT fcid, name FROM faculty WHERE fcid = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $faculty_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['fcid']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>


                    <div class="col-md-6 mb-3">
                        <label for="batch" class="form-label">Batch</label>
                        <select class="form-select" id="batch" name="batch">
                            <option value="">Select Batch</option>
                        </select>
                    </div> 

                    <div class="col-md-6 mb-3">
                        <label for="class" class="form-label">Class</label>
                        <select class="form-select" id="class" name="class">
                            <option value="">Select Class</option>
                        </select>
                    </div>

                        <div class="col-md-6 mb-3">
                            <label for="exam" class="form-label">Exam</label>
                            <select class="form-select" id="exam" name="exam">
                                <option value="">Select Exam</option>
                            </select>
                        </div>
                    </div> 

                    <div id="marksInputContainer"></div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 mt-3">Add Marks</button>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-info mt-3"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
    </div>
</div>

<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>

document.getElementById('faculty').addEventListener('change', function () {
    const facultyId = this.value;

    // Fetch batches based on faculty
    fetch(`?fetch=batches&faculty_id=${facultyId}`)
        .then(response => response.json())
        .then(data => {
            const batchSelect = document.getElementById('batch');
            batchSelect.innerHTML = '<option value="">Select Batch</option>';
            data.forEach(batch => {
                batchSelect.innerHTML += `<option value="${batch.batch_id}">${batch.batch_year}</option>`;
            });

            // Clear classes and subjects as they depend on batches and faculty
            document.getElementById('class').innerHTML = '<option value="">Select Class</option>';
            
        })
        .catch(error => console.error('Error fetching batches:', error));
});
//class
document.getElementById('batch').addEventListener('change', function () {
    const facultyId = document.getElementById('faculty').value;
    const batchId = this.value;
    
    if (facultyId && batchId) {
        fetch(`?fetch=classes&faculty_id=${facultyId}&batch_id=${batchId}`)
            .then(response => response.json())
            .then(data => {
                const classSelect = document.getElementById('class');
                classSelect.innerHTML = '<option value="">Select Class</option>';
                data.forEach(cls => {
                    classSelect.innerHTML += `<option value="${cls.cid}">${cls.classname}</option>`;
                });
            })
            .catch(error => console.error('Error fetching classes:', error));
    }
});
// Fetch exams for a given class and batch
document.getElementById('class').addEventListener('change', function() {
    const classId = this.value;
    const batchYear = document.getElementById('batch').value;  // Get selected batch value

    // Ensure both classId and batchYear are selected
    if (classId && batchYear) {
        fetch(`?fetch=exam&class_id=${classId}&batch=${batchYear}`)
            .then(response => response.json())
            .then(data => {
                const examSelect = document.getElementById('exam');
                examSelect.innerHTML = '<option value="">Select Exam</option>';
                if (Array.isArray(data)) {
                    data.forEach(exam => {
                        examSelect.innerHTML += `<option value="${exam.exam_id}">${exam.exam_name}</option>`;
                    });
                } else {
                    console.error('Error fetching exams: ', data.error || 'Unknown error');
                }
            })
            .catch(error => console.error('Error fetching exams:', error));
    }
});




document.getElementById('exam').addEventListener('change', function() {
    const examId = this.value;
    const classId = document.getElementById('class').value;
    const batch = document.getElementById('batch').value; // Get the batch value
    const facultyId = document.getElementById('faculty').value; // Get the faculty value

    Promise.all([
        fetch(`?fetch=subjects&exam_id=${examId}`).then(response => response.json()),
        fetch(`?fetch=students&faculty_id=${facultyId}&batch=${batch}`).then(response => response.json()),
        fetch(`?fetch=existing_marks&exam_id=${examId}&class_id=${classId}`).then(response => response.json())
    ]).then(([subjects, students, existingMarks]) => {
        const marksInputContainer = document.getElementById('marksInputContainer');
        console.log(existingMarks)
        // Clear previous content
        marksInputContainer.innerHTML = '';
        
        // Create table only if there are students
        if (students.length === 0) {
            marksInputContainer.innerHTML = '<div class="alert alert-info">No students found for selected batch and faculty.</div>';
            return;
        }

        const table = document.createElement('table');
        table.className = 'table table-bordered';

        // Create header row
        const headerRow = table.insertRow();
        headerRow.insertCell().textContent = 'Student';
        subjects.forEach(subject => {
            const th = document.createElement('th');
            th.innerHTML = `${subject.subname} <br><small>(Full Marks: ${subject.fullmarks})</small>`;
            headerRow.appendChild(th);
        });

        // Create input rows for students
        students.forEach(student => {
            const row = table.insertRow();
            const nameCell = row.insertCell();
            nameCell.textContent = student.name;

            subjects.forEach(subject => {
                const markCell = row.insertCell();
                const input = document.createElement('input');
                input.type = 'number';
                input.name = `marks[${student.sid}][${subject.subid}]`;
                input.className = 'form-control';
                input.min = 0;
                input.max = subject.fullmarks;

                // Pre-populate existing marks
                if (existingMarks[student.sid] && existingMarks[student.sid][subject.subid]) {
                    input.value = existingMarks[student.sid][subject.subid];
                }

                markCell.appendChild(input);
            });
        });

        // Add hidden exam ID field
        const examInput = document.createElement('input');
        examInput.type = 'hidden';
        examInput.name = 'exam_id';
        examInput.value = examId;
        marksInputContainer.appendChild(examInput);

        marksInputContainer.appendChild(table);
    }).catch(error => {
        console.error('Error loading data:', error);
        marksInputContainer.innerHTML = '<div class="alert alert-danger">Error loading student data. Please try again.</div>';
    });
});



 </script>
</body>

</html>
<?php
// Close the database connection
$conn->close();
?>
