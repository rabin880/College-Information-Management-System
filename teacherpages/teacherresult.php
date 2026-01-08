<?php
session_start();
$host = 'localhost';
$user = 'root'; 
$password = ''; 
$database = 'cims'; 
$errorMessage = '';

// Check if the teacher is logged in
if (!isset($_SESSION['user_id'])) {
    die("No teacher is logged in.");
}

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

if (isset($_GET['fetch']) && $_GET['fetch'] === 'results') {
    $examId = intval($_GET['exam_id']);
    $batchId = intval($_GET['batch_id']);
    $classId = intval($_GET['class_id']);

    if ($examId && $batchId && $classId) {
        // Fetch students for the selected batch and class
        $studentsQuery = "SELECT s.sid, s.name FROM studentlog s WHERE s.batch = ? AND FIND_IN_SET(?, s.classid)";
        $studentsStmt = $conn->prepare($studentsQuery);
        $studentsStmt->bind_param("ii", $batchId, $classId);
        $studentsStmt->execute();
        $studentsResult = $studentsStmt->get_result();
        $students = [];
        while ($student = $studentsResult->fetch_assoc()) {
            // Initialize each student with marks array and accumulators
            $students[$student['sid']] = [
                'name' => $student['name'], 
                'marks' => [], 
                'total_credits' => 0, 
                'weighted_marks' => 0,
                'failed' => false // flag to mark if student fails any subject
            ];
        }
    
        // Fetch marks for each subject in the exam
        $marksQuery = "
            SELECT m.student_id, m.subject_id, m.mark, s.subname, s.fullmarks, s.crhr 
            FROM marks m
            JOIN subject s ON m.subject_id = s.subid
            WHERE m.exam_id = ?";
        $marksStmt = $conn->prepare($marksQuery);
        $marksStmt->bind_param("i", $examId);
        $marksStmt->execute();
        $marksResult = $marksStmt->get_result();
    
        while ($row = $marksResult->fetch_assoc()) {
            $sid = $row['student_id'];
            if (isset($students[$sid])) {
                // Append subject marks details
                $students[$sid]['marks'][] = [
                    'subject' => $row['subname'],
                    'mark' => $row['mark'],
                    'fullmarks' => $row['fullmarks'],
                    'crhr' => $row['crhr']
                ];
                // Calculate GPA for the subject using the formula
                $gpa = ($row['mark'] / $row['fullmarks']) * 4;
                // Accumulate weighted grade points and total credits
                $students[$sid]['weighted_marks'] += $gpa * $row['crhr'];
                $students[$sid]['total_credits'] += $row['crhr'];
                // Mark as failed if any subject's mark is below 40% of full marks
                if ($row['mark'] < 0.4 * $row['fullmarks']) {
                    $students[$sid]['failed'] = true;
                }
            }
        }
    
        // Calculate CGPA and assign grade for each student
        // Also, prepare a ranking list for students who have passed all subjects
        $rankedStudents = [];
        foreach ($students as $sid => &$student) {
            if ($student['total_credits'] > 0) {
                // Apply the CGPA formula
                $student['cgpa'] = round($student['weighted_marks'] / $student['total_credits'], 2);
                // Convert CGPA to a percentage scale (CGPA * 25) to get grade
                $student['grade'] = getGrade($student['cgpa'] * 25);
            } else {
                $student['cgpa'] = null;
                $student['grade'] = null;
            }
            // Only include passed students (those with no failed subject) for ranking
            if (!$student['failed']) {
                $rankedStudents[] = [
                    'sid' => $sid,
                    'cgpa' => $student['cgpa']
                ];
            }
        }

        // Sort passed (non-failed) students by CGPA in descending order
        usort($rankedStudents, function($a, $b) {
            return $b['cgpa'] <=> $a['cgpa'];
        });

        // Assign ranks only to students who passed all subjects
        $rank = 1;
        foreach ($rankedStudents as $rankedStudent) {
            $students[$rankedStudent['sid']]['rank'] = $rank++;
        }

        echo json_encode(array_values($students));
        exit;
    }
}

// Helper function to determine grade based on average percentage marks
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
    <title>Teacher Dashhboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../adminpages/admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        

        /* Center Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex-grow: 1; /* Take remaining space */
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
                <a class="nav-link text-white d-flex align-items-center" href="">
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
    <div class="container mt-5">
        <h1>Results</h1>
        <form id="resultForm">
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
        </form>
        
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
    document.getElementById('exam').addEventListener('change', function() {
        createResults(); // Call createResults when exam is selected
    });
                    
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


            function createResults() {
        const faculty = document.getElementById('faculty').value;
        const batch = document.getElementById('batch').value;
        const classId = document.getElementById('class').value;
        const exam = document.getElementById('exam').value;

        if (!faculty || !batch || !classId || !exam) {
            alert('Please select all the options.');
            return;
        }

        fetch(`?fetch=results&exam_id=${exam}&batch_id=${batch}&class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('resultsTBody');
                const thead = document.querySelector('#resultsTable thead');
                tbody.innerHTML = '';
                thead.innerHTML = '';

                if (data.length > 0) {
                    // Create dynamic table headers based on subjects
                    const sampleStudent = data[0];
                    let headerRow = `<tr>
                        <th>Student Name</th>`;
                    if (sampleStudent.marks) {
                        sampleStudent.marks.forEach(mark => {
                            headerRow += `<th>${mark.subject} (${mark.fullmarks})</th>`;
                        });
                    }
                    headerRow += `
                        <th>Grade</th>
                        <th>CGPA</th>
                        <th>Result</th>
                        <th>Rank</th>
                    </tr>`;
                    thead.innerHTML = headerRow;

                    // Populate the table rows for each student
                    data.forEach(student => {
                        const tr = document.createElement('tr');
                        let subjects = '';
                        let isFail = false;

                        student.marks.forEach(mark => {
                            if (mark.mark < mark.fullmarks * 0.4) {
                                isFail = true; // Mark as failed if any subject is below 40%
                            }
                            subjects += `<td>${mark.mark}</td>`;
                        });

                        const result = isFail ? 'Fail' : 'Pass';

                        tr.innerHTML = `
                            <td>${student.name}</td>
                            ${subjects}
                            <td>${isFail ? '' : student.grade || ''}</td>
                            <td>${isFail ? '' : student.cgpa || ''}</td>
                            <td>${result}</td>
                            <td>${isFail ? '' : student.rank || ''}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="100%">No results found.</td></tr>';
                }
            })
            .catch(error => console.error('Error generating results:', error));
    }


        


</script>
</body>

</html>
