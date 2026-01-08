<?php

session_start();

// Check if the student is logged in
if (!isset($_SESSION['user_id'])) {
    die("No student is logged in.");
}



$host = 'localhost';
$user = 'root'; 
$password = ''; 
$database = 'cims'; 
$errorMessage = '';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "SELECT name, logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
    $collegeName = $row['name'];
} else {
    $logoURL = 'default-logo.png';
    $collegeName = "Default College Name";
}



$student_id = $_SESSION['user_id'];
$batchId = $_SESSION['user_batch'];  
$facultyId= $_SESSION['faculty_type']; 








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

if (isset($_GET['fetch']) && $_GET['fetch'] === 'results') {
    $examId = intval($_GET['exam_id']);

    if ($examId && $student_id) {
        // Fetch student details
        $studentQuery = "SELECT sid, name FROM studentlog WHERE sid = ? AND batch = ?";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->bind_param("ii", $student_id, $batchId);
        $studentStmt->execute();
        $studentResult = $studentStmt->get_result();
        $studentData = $studentResult->fetch_assoc();

        if (!$studentData) {
            echo json_encode(['error' => 'Student not found']);
            exit;
        }

        // Initialize student details
        $student = [
            'name' => $studentData['name'],
            'marks' => [],
            'total_credits' => 0,
            'weighted_marks' => 0,
            'failed' => false
        ];

        // Fetch marks for this student and exam
        $marksQuery = "
            SELECT m.subject_id, m.mark, s.subname, s.fullmarks, s.crhr 
            FROM marks m
            JOIN subject s ON m.subject_id = s.subid
            WHERE m.exam_id = ? AND m.student_id = ?";
        $marksStmt = $conn->prepare($marksQuery);
        $marksStmt->bind_param("ii", $examId, $student_id);
        $marksStmt->execute();
        $marksResult = $marksStmt->get_result();

        while ($row = $marksResult->fetch_assoc()) {
            // Append marks details
            $student['marks'][] = [
                'subject' => $row['subname'],
                'mark' => $row['mark'],
                'fullmarks' => $row['fullmarks'],
                'crhr' => $row['crhr']
            ];
            
            // Calculate GPA
            $gpa = ($row['mark'] / $row['fullmarks']) * 4;
            $student['weighted_marks'] += $gpa * $row['crhr'];
            $student['total_credits'] += $row['crhr'];

            // Check if the student failed any subject
            if ($row['mark'] < 0.4 * $row['fullmarks']) {
                $student['failed'] = true;
            }
        }

        // Calculate CGPA and Grade
        if ($student['total_credits'] > 0) {
            $student['cgpa'] = round($student['weighted_marks'] / $student['total_credits'], 2);
            $student['grade'] = getGrade($student['cgpa'] * 25);
        } else {
            $student['cgpa'] = null;
            $student['grade'] = null;
        }

        // Result status
        $student['result'] = $student['failed'] ? 'Fail' : 'Pass';

        echo json_encode($student);
        exit;
    }
}

// Helper function to determine grade
function getGrade($average) {
    if ($average >= 90) return 'A+';
    if ($average >= 80) return 'A';
    if ($average >= 70) return 'B+';
    if ($average >= 60) return 'B';
    if ($average >= 50) return 'C';
    return 'F';
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="common.css">
    
    <style>
        .main-content {
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: flex-start; /* Align to the top */
            min-height: calc(100vh - 70px);
            padding: 20px 30px; /* Adjust padding for spacing */
        }

        .exam-form-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            padding: 10px; /* Balanced padding */
            margin-top: 12px; /* Spacing from the top */
            width: 100%;
            max-width: 900px; /* Adjusted width for better fit */
            transition: all 0.3s ease;
        }

        .exam-form-container:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-select,
        .form-label {
            transition: all 0.3s ease;
            font-size: 16px; /* Slightly larger font size for readability */
        }

        .form-select {
            padding: 6px 8px; /* Comfortable padding */
            height: 35px; /* Increased height for better usability */
            line-height: 1.2; /* Comfortable line height */
        }

        .form-group {
            margin-bottom: 10px; /* Adjusted spacing between fields */
        }

        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
            transition: all 0.3s ease;
            padding: 8px 16px; /* Comfortable button padding */
            font-size: 14px; /* Standard button font size */
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .nav-link {
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #3498db !important;
            transform: translateX(2px);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .print-marksheet, .print-marksheet * {
                visibility: visible;
            }
            .print-marksheet {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
            table {
                page-break-inside: avoid;
            }
        }

        /* Print layout styling */
        .print-marksheet {
            display: none;
            background: white;
            padding: 20px;
        }
        .marksheet-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .college-logo {
            height: 80px;
            margin-bottom: 10px;
        }
        .student-info {
            margin: 20px 0;
        }
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .marks-table th, .marks-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .final-result {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>


</head>

<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2 class="m-0">Student Dashboard</h2>
    <a href="logout.php" class="btn btn-danger" id="logout-btn">Log out</a>
</header>   

<div class="d-flex">
   <!-- Sidebar navigation -->
<nav class="sidebar bg-dark text-white p-3" style="width: 150px;">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex align-items-center" href="studentdashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> 
                    Dashboard
                </a>
            </li>

            <!-- Result -->
            <li class="nav-item">
                <a href="studentresult.php" class="nav-link text-white">
                    <i class="fas fa-chart-line me-2"></i> Result
                </a>
            </li>

            <!-- ID Card -->
            <li class="nav-item">
                <a href="idcard.php" class="nav-link text-white">
                    <i class="fas fa-id-card me-2"></i> ID Card
                </a>
            </li>
        </ul>
</nav>


<!-- Main content area -->
<div class="main-content flex-grow-1 p-3">
    <div class="container mt-5">
        <h1>Results</h1>
        <form id="resultForm">
            <div class="row">
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
        </form>
        
        <!-- Add print marksheet section -->
<div class="print-marksheet">
    <div class="marksheet-header">
        <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="College Logo" class="college-logo">
        <h1><?php echo htmlspecialchars($collegeName); ?></h1>
        <h2 id="printExamName"></h2>
        <h3>Academic Transcript</h3>
    </div>

    <div class="student-info">
        <p><strong>Student Name:</strong> <span id="printStudentName"></span></p>
        <p><strong>Batch:</strong> <?php echo htmlspecialchars($batchId); ?></p>
        <p><strong>Class:</strong> <span id="printClassName"></span></p>
    </div>

    <table class="marks-table">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Full Marks</th>
                <th>Pass Marks</th>
                <th>Marks Obtained</th>
                <th>Credit Hours</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody id="printMarksBody">
        </tbody>
    </table>

    <div class="final-result">
        <p>CGPA: <span id="printCGPA"></span></p>
        <p>Final Grade: <span id="printGrade"></span></p>
        <p>Result: <span id="printResult"></span></p>
    </div>

    <div class="signatures">
        <div style="float: left; width: 30%;">
            <p>_________________________</p>
            <p>Student Signature</p>
        </div>
        <div style="float: right; width: 30%;">
            <p>_________________________</p>
            <p>Exam Controller</p>
        </div>
    </div>
</div>

<!-- Modify the print button -->
<button class="btn btn-success no-print" onclick="printMarksheet()">Print Results</button>

        <div class="mt-4">
            <table class="table table-bordered" id="resultsTable">
                <thead id="resultsThead">
                    <!-- Headers will be populated dynamically -->
                </thead>
                <tbody id="resultsTBody">
                    <!-- Results will be populated dynamically -->
                </tbody>
            </table>
        </div>

    </div>
</div>

    </div>
</div>




<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
var batch = <?php echo json_encode($batchId); ?>;
var faculty = <?php echo json_encode($facultyId); ?>;

document.addEventListener("DOMContentLoaded", function () {
    const classSelect = document.getElementById('class');
    const examSelect = document.getElementById('exam');

    // Fetch classes automatically based on session faculty & batch
    function fetchClasses() {
        if (faculty && batch) {
            fetch(`?fetch=classes&faculty_id=${faculty}&batch_id=${batch}`)
                .then(response => response.json())
                .then(data => {
                    classSelect.innerHTML = '<option value="">Select Class</option>';
                    data.forEach(cls => {
                        classSelect.innerHTML += `<option value="${cls.cid}">${cls.classname}</option>`;
                    });
                })
                .catch(error => console.error('Error fetching classes:', error));
        }
    }

    // Fetch exams when a class is selected
    classSelect.addEventListener('change', function () {
        const classId = this.value;

        if (classId && batch) {
            fetch(`?fetch=exam&class_id=${classId}&batch=${batch}`)
                .then(response => response.json())
                .then(data => {
                    examSelect.innerHTML = '<option value="">Select Exam</option>';
                    if (Array.isArray(data)) {
                        data.forEach(exam => {
                            examSelect.innerHTML += `<option value="${exam.exam_id}">${exam.exam_name}</option>`;
                        });
                    } else {
                        console.error('Error fetching exams:', data.error || 'Unknown error');
                    }
                })
                .catch(error => console.error('Error fetching exams:', error));
        }
    });

    // Auto-fetch classes on page load
    fetchClasses();
});

document.addEventListener("DOMContentLoaded", function () {
    const examSelect = document.getElementById('exam');
    const resultsTBody = document.getElementById('resultsTBody');
    const resultsThead = document.querySelector('#resultsTable thead');

    function fetchResults() {
        const examId = examSelect.value;
        if (!examId) {
            alert('Please select an exam.');
            return;
        }

        fetch(`?fetch=results&exam_id=${examId}`)
            .then(response => response.json())
            .then(data => {
                // Update visible table
                resultsTBody.innerHTML = '';
                resultsThead.innerHTML = '';

                if (data.error) {
                    resultsTBody.innerHTML = `<tr><td colspan="100%">${data.error}</td></tr>`;
                    return;
                }

                // Create table headers
                let headerRow = `<tr><th>Student Name</th>`;
                if (data.marks) {
                    data.marks.forEach(mark => {
                        headerRow += `<th>${mark.subject} (${mark.fullmarks})</th>`;
                    });
                }
                headerRow += `<th>Grade</th><th>CGPA</th><th>Result</th></tr>`;
                resultsThead.innerHTML = headerRow;

                // Populate student results
                let subjects = '';
                data.marks.forEach(mark => {
                    subjects += `<td>${mark.mark}</td>`;
                });

                resultsTBody.innerHTML = `
                    <tr>
                        <td>${data.name}</td>
                        ${subjects}
                        <td>${data.failed ? '' : data.grade || ''}</td>
                        <td>${data.failed ? '' : data.cgpa || ''}</td>
                        <td>${data.result}</td>
                    </tr>
                `;

                // Update print marksheet section
                document.getElementById('printStudentName').textContent = data.name;
                document.getElementById('printExamName').textContent = examSelect.options[examSelect.selectedIndex].text;
                document.getElementById('printClassName').textContent = document.getElementById('class').options[document.getElementById('class').selectedIndex].text;
                
                const marksBody = document.getElementById('printMarksBody');
                marksBody.innerHTML = '';
                
                data.marks.forEach(mark => {
                    const passMarks = Math.ceil(mark.fullmarks * 0.4);
                    const percentage = mark.mark / mark.fullmarks;
                    marksBody.innerHTML += `
                        <tr>
                            <td>${mark.subject}</td>
                            <td>${mark.fullmarks}</td>
                            <td>${passMarks}</td>
                            <td>${mark.mark}</td>
                            <td>${mark.crhr}</td>
                            <td>${getLetterGrade(percentage)}</td>
                        </tr>
                    `;
                });

                document.getElementById('printCGPA').textContent = data.failed ? 'N/A' : data.cgpa;
                document.getElementById('printGrade').textContent = data.failed ? 'F' : data.grade;
                document.getElementById('printResult').textContent = data.result;
            })
            .catch(error => console.error('Error fetching results:', error));
    }

    // Grade calculation helper
    function getLetterGrade(percentage) {
        if (percentage >= 0.9) return 'A+';
        if (percentage >= 0.8) return 'A';
        if (percentage >= 0.7) return 'B+';
        if (percentage >= 0.6) return 'B';
        if (percentage >= 0.5) return 'C';
        return 'F';
    }

    // Trigger result fetch when an exam is selected
    examSelect.addEventListener('change', fetchResults);
});

// Print function
function printMarksheet() {
    const printContent = document.querySelector('.print-marksheet').cloneNode(true);
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Marksheet</title>
                <style>
                    .print-marksheet {
                        font-family: Arial, sans-serif;
                        width: 100%;
                        padding: 20px;
                    }
                    .marksheet-header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .college-logo {
                        height: 80px;
                        margin-bottom: 10px;
                    }
                    .student-info {
                        margin: 20px 0;
                    }
                    .marks-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    .marks-table th, .marks-table td {
                        border: 1px solid #000;
                        padding: 8px;
                        text-align: center;
                    }
                    .final-result {
                        margin-top: 20px;
                        font-weight: bold;
                    }
                    .signatures {
                        margin-top: 50px;
                        display: flex;
                        justify-content: space-between;
                    }
                </style>
            </head>
            <body>
                ${printContent.innerHTML}
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}
</script>

</body>
</html>