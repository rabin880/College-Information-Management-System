<?php
session_start();
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'cims';
$errorMessage = '';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Fetch college name along with logo
$sql = "SELECT name, logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
    $collegeName = $row['name'];
} else {
    $logoURL = 'default-logo.png';
    $collegeName = 'College Name'; // Default name if not found
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
        // Fetch students for the selected batch and class using FIND_IN_SET to check for classId in the comma-separated list
        $studentsQuery = "SELECT s.sid, s.name FROM studentlog s WHERE s.batch = ? AND FIND_IN_SET(?, s.classid) > 0";
        $studentsStmt = $conn->prepare($studentsQuery);
        $studentsStmt->bind_param("ii", $batchId, $classId); // Binding batchId and classId
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
    <title>Admin Dashhboard</title>
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
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table th, .table td {
        white-space: nowrap; /* Prevent text wrapping */
    }

    .table-hover tbody tr:hover {
        background-color: #f9f9f9;
    }

    .table thead th {
        position: sticky;
        top: 0;
        background-color: #ffffff;
        z-index: 2;
    }


    @media print {
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin: 0;
            padding: 0;
        }

        #resultTableContainer {
            width: 100%;
            margin-top: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .table th {
            background-color: #f2f2f2;
        }

        h1, h3 {
            text-align: center;
        }

        /* Hide non-print elements */
        .header, .sidebar, #logout-btn {
            display: none;
        }

        /* Styling for the class name */
        #classNameDisplay h3 {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* Styling for the print button */
        .btn {
            display: none;
        }
    }
    @media print {
        /* Hide unnecessary elements like navigation bars, buttons, etc. */
        .no-print, button, .header, .footer {
            display: none !important;
        }

        /* Fit the content to the page width */
        #printableArea {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }




    </style>
</head>

<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2>Admin</h2>
    <a href="logout.php" class="btn btn-danger" id="logout-btn">Log out</a>
</header>   

<div class="d-flex">
<!-- Sidebar navigation -->
<nav class="sidebar bg-dark text-white p-3" style="width: 150px;">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex align-items-center" href="admindashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> 
                    Dashboard
                </a>
            </li>

            <!-- Collapsible Manage Settings -->
            <li class="nav-item">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#manageSettings" role="button" aria-expanded="false" aria-controls="manageSettings">
                    <i class="fas fa-cogs me-1"></i> Settings
                </a>
                <div class="collapse" id="manageSettings">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a href="basicSettings.php" class="nav-link text-white d-flex align-items-center" style="white-space: nowrap;">
                                <i class="fas fa-tools me-1"></i> Basic 
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="noticeSettings.php" class="nav-link text-white">
                                <i class="fas fa-bullhorn me-1"></i>  Notices
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="eventSettings.php" class="nav-link text-white">
                                <i class="fas fa-calendar-alt me-1"></i> Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="gallerySettings.php" class="nav-link text-white">
                                <i class="fas fa-image me-1"></i> Gallery
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Faculty -->
            <li class="nav-item">
                <a href="adminfaculty.php" class="nav-link text-white">
                    <i class="fas fa-chalkboard-teacher me-2"></i> Faculty
                </a>
            </li>

            <!-- Batch -->
            <li class="nav-item">
                <a href="adminbatch.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i> Batch
                </a>
            </li>


            <!-- Collapsible Manage Settings -->
            <li class="nav-item">
                <a class="nav-link text-white" data-bs-toggle="collapse" href="#manageclass" role="button" aria-expanded="false" aria-controls="manageclass">
                    <i class="fas fa-cogs me-1"></i> Class
                </a>
                <div class="collapse" id="manageclass">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a href="adminclass.php" class="nav-link text-white d-flex align-items-center" style="white-space: nowrap;">
                            <i class="fas fa-plus me-1"></i> ADD 
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="activeclass.php" class="nav-link text-white">
                            <i class="fas fa-user-check me-1"></i>  Assign 
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </li>
            <!-- Subjects -->
            <li class="nav-item">
                <a href="adminsubjects.php" class="nav-link text-white">
                    <i class="fas fa-book me-2"></i> Subjects
                </a>
            </li>

           
              <!-- Teachers -->
              <li class="nav-item">
                <a href="adminteachers.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i> Teachers
                </a>
            </li>

            <!-- Students -->
            <li class="nav-item">
                <a href="adminstudents.php" class="nav-link text-white">
                    <i class="fas fa-user-graduate me-2"></i> Students
                </a>
            </li>

            <!-- Exam -->
            <li class="nav-item">
                <a href="adminexam.php" class="nav-link text-white">
                    <i class="fas fa-pen me-2"></i> Exam
                </a>
            </li>

            <!-- Marks -->
            <li class="nav-item">
                <a href="adminmarks.php" class="nav-link text-white">
                <i class="fas fa-clipboard-list me-2"></i> Marks
                </a>
            </li>

            <!-- Result -->
            <li class="nav-item">
                <a href="adminresult.php" class="nav-link text-white">
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
                        <option value="">Select Faculty</option>
                        <?php
                        $query = "SELECT fcid, name FROM faculty where status=1";
                        $result = $conn->query($query);
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
        
        <!-- Print Button -->
        <button id="printButton" class="btn btn-primary">Print Result Sheet</button>
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
    let resultsData = [];
    
    document.getElementById('exam').addEventListener('change', function() {
        createResults(); 
    });
                    
            document.getElementById('faculty').addEventListener('change', function () {
                const facultyId = this.value;

                fetch(`?fetch=batches&faculty_id=${facultyId}`)
                    .then(response => response.json())
                    .then(data => {
                        const batchSelect = document.getElementById('batch');
                        batchSelect.innerHTML = '<option value="">Select Batch</option>';
                        data.forEach(batch => {
                            batchSelect.innerHTML += `<option value="${batch.batch_id}">${batch.batch_year}</option>`;
                        });

                        document.getElementById('class').innerHTML = '<option value="">Select Class</option>';
                        
                    })
                    .catch(error => console.error('Error fetching batches:', error));
            });

            // retrieve class for given faculty
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

            // retrieve exams for a given class and batch
            document.getElementById('class').addEventListener('change', function() {
                const classId = this.value;
                const batchYear = document.getElementById('batch').value; 

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
                resultsData = data; 
                const tbody = document.getElementById('resultsTBody');
                const thead = document.querySelector('#resultsTable thead');
                tbody.innerHTML = '';
                thead.innerHTML = '';
                

                if (data.length > 0) {
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

                    data.forEach(student => {
                        const tr = document.createElement('tr');
                        let subjects = '';
                        let isFail = false;

                        student.marks.forEach(mark => {
                            if (mark.mark < mark.fullmarks * 0.4) {
                                isFail = true; 
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



    document.getElementById('printButton').addEventListener('click', function() {
        // Get selected values
        const facultySelect = document.getElementById('faculty');
        const facultyName = facultySelect.options[facultySelect.selectedIndex].text;
        
        const batchSelect = document.getElementById('batch');
        const batchName = batchSelect.options[batchSelect.selectedIndex].text;
        
        const classSelect = document.getElementById('class');
        const className = classSelect.options[classSelect.selectedIndex].text;
        
        const examSelect = document.getElementById('exam');
        const examName = examSelect.options[examSelect.selectedIndex].text;
        
        // Create a print-friendly HTML
        let printContent = `
            <style>
                @page {
                    size: A4 portrait;
                    margin: 10mm;
                }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .print-header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .print-header h3 {
                    margin: 5px 0 0 0;
                    font-size: 18px;
                    font-weight: normal;
                }
                .print-info {
                    margin-bottom: 20px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 10px;
                }
                .print-info p {
                    margin: 5px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: center;
                }
                th {
                    background-color: #f2f2f2;
                }
                .no-print {
                    display: none;
                }
            </style>
            <div class="print-header">
                <h1><?php echo htmlspecialchars($collegeName); ?></h1>
                <h3>Result Sheet</h3>
            </div>
            <div class="print-info">
                <p><strong>Faculty:</strong> ${facultyName}</p>
                <p><strong>Batch:</strong> ${batchName}</p>
                <p><strong>Class:</strong> ${className}</p>
                <p><strong>Exam:</strong> ${examName}</p>
            </div>
            ${document.getElementById('resultsTable').outerHTML}
        `;
        
        // Open a new window for printing
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Result Sheet - ${className} - ${examName}</title>
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    });




</script>

</body>

</html>
