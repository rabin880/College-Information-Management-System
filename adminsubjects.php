<?php

$servername = "localhost"; 
$username = "root";
$password = "";
$dbname = "cims";


// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";


// Fetch logo
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
$logoURL = ($result && $result->num_rows > 0) ? '../logoimages/' . $result->fetch_assoc()['logo'] : 'default-logo.png';

// Fetch subjects
$sql = "SELECT 
            subject.subid,
            subject.subname, 
            subject.subcode, 
            faculty.name AS faculty_name, 
            subject.rank,
            subject.fullmarks, 
            subject.passmarks, 
            subject.crhr
        FROM subject 
        JOIN faculty ON subject.faculty = faculty.fcid WHERE faculty.status = 1";
$result = $conn->query($sql);
$subjectList = $result->fetch_all(MYSQLI_ASSOC);

// Fetch faculty options
$sql = "SELECT fcid, name, type FROM faculty where status=1";
$result = $conn->query($sql);
$facultyOptions = "";
while ($row = $result->fetch_assoc()) {
    $facultyOptions .= '<option value="' . htmlspecialchars($row['fcid']) . '">' . htmlspecialchars($row['name']) . '</option>';
}

// Fetch teacher options
$sql = "SELECT stid, name FROM stafflog WHERE role IN (2, 3)";
$result = $conn->query($sql);
$teacherOptions = "";
while ($row = $result->fetch_assoc()) {
    $teacherOptions .= '<option value="' . htmlspecialchars($row['stid']) . '">' . htmlspecialchars($row['name']) . '</option>';
}

// Add a new subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subjectName = $_POST['subjectName'];
    $subjectCode = $_POST['subjectCode'];
    $facultyId = $_POST['facultyId'];
    $ClassRank = $_POST['subjectClassRank']; 
    $fullMarks = $_POST['subjectFullMark'];
    $passMarks = $_POST['subjectPassMark'];
    $credithour = $_POST['addCreditHours'];


    // Validate that Full Marks is greater than Pass Marks
    if ($fullMarks <= $passMarks) {
        $successMessage = "Full marks must be greater than pass marks!";
    } else {
        // Check if the subject code already exists
        $sql = "SELECT COUNT(*) AS count FROM subject WHERE subcode = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $subjectCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row['count'] > 0) {
            $successMessage = "Subject code already exists!";
        } else {
            // Insert the new subject into the database
            $sql = "INSERT INTO subject (subname, subcode, faculty, rank, fullmarks, passmarks, crhr) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiiiii", $subjectName, $subjectCode, $facultyId, $ClassRank, $fullMarks, $passMarks, $credithour);

            if ($stmt->execute()) {
                $successMessage = "Subject added successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $successMessage = "Error adding subject: " . $conn->error;
            }
        }
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_subject'])) {
    $subjectId = intval($_POST['subid']);

    // Step 1: Check if the subject is used in marks table
    $checkQuery = "SELECT COUNT(*) FROM marks WHERE subject_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $subjectId);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        // Subject is used in exams, show JavaScript alert
        echo "<script>alert('This subject is used in exams and cannot be deleted.'); window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        exit();
    } else {
        // Step 2: If subject is not used, proceed with deletion
        $sql = "DELETE FROM subject WHERE subid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $subjectId);

        if ($stmt->execute()) {
            echo "<script>alert('Subject deleted successfully!'); window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        } else {
            echo "<script>alert('Error deleting subject: " . $conn->error . "'); window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        }
        $stmt->close();
    }
}



// Fetch subject details for editing
if (isset($_GET['getSubjectDetails'])) {
    $subjectId = $_GET['getSubjectDetails'];
    
    $sql = "SELECT * FROM subject WHERE subid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $subject = $result->fetch_assoc();
        echo json_encode($subject);
    } else {
        echo json_encode(['error' => 'Subject not found.']);
    }
    exit();
}


// Edit Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_subject'])) {
    $subjectId = $_POST['subjectId'];
    $subjectName = $_POST['subjectName'];
    $subjectCode = $_POST['subjectCode'];
    $fullMarks = $_POST['editSubjectFullMark'];
    $passMarks = $_POST['editSubjectPassMark'];
    $edcredithour = $_POST['editCreditHours'];

    // Validate marks
    if ($fullMarks <= $passMarks) {
        $successMessage = "Full marks must be greater than pass marks!";
    } else {
        // Check for duplicate subject code (EXCLUDING CURRENT SUBJECT)
        $sql = "SELECT COUNT(*) AS count FROM subject WHERE subcode = ? AND subid != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $subjectCode, $subjectId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row['count'] > 0) {
            $successMessage = "Subject code already exists!";
        } else {
            // Corrected query and parameter order
            $sql = "UPDATE subject SET 
                    subname = ?, 
                    fullmarks = ?, 
                    passmarks = ?, 
                    crhr = ?
                    WHERE subid = ?";
            
            $stmt = $conn->prepare($sql);
            // Correct parameter order: s=string, i=integer
            // Order matches: subname,fullmarks, passmarks, crhr, subid
            $stmt->bind_param("siiii", 
                $subjectName,
                $fullMarks,
                $passMarks,
                $edcredithour,  // credit hours
                $subjectId      // WHERE clause
            );

            if ($stmt->execute()) {
                $successMessage = "Subject updated successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $successMessage = "Error updating subject: " . $conn->error;
            }
        }
    }
}



if (isset($_GET['fetchData'])) {
    $type = $_GET['type'];
    $id = intval($_GET['id']); // Faculty or Class ID

    if ($type == 'class') {
        // Fetch classes based on the selected faculty
        $sql = "SELECT cid, classname, rank FROM class WHERE faculty = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $classes = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($classes);
    } elseif ($type == 'subject') {
        $facultyId = intval($_GET['facultyId']);
        // Join with class table to get the correct rank
        $sql = "SELECT s.subid, s.subname 
                FROM subject s
                INNER JOIN class c ON s.rank = c.rank 
                WHERE c.cid = ? AND s.faculty = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($subjects);
    }
    exit();
}




//add teacher 
if (isset($_POST['assign_teacher'])) {
    $facultyId = intval($_POST['facultyId']);
    $classId = intval($_POST['classId']);
    $subjectId = intval($_POST['subjectId']);
    $teacherId = intval($_POST['teacherId']);
    
    // Check if a teacher is already assigned to this subject and class
    $checkSql = "SELECT id FROM subject_teacher WHERE subject_id = ? AND class_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $subjectId, $classId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing assignment
        $updateSql = "UPDATE subject_teacher 
                     SET teacher_id = ?, faculty_id = ?
                     WHERE subject_id = ? AND class_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("iiii", $teacherId, $facultyId, $subjectId, $classId);
    } else {
        // Create new assignment
        $insertSql = "INSERT INTO subject_teacher (subject_id, teacher_id, faculty_id, class_id) 
                     VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("iiii", $subjectId, $teacherId, $facultyId, $classId);
    }
    
    if ($stmt->execute()) {
        // Store success message in session
        session_start();
        $_SESSION['message'] = 'Teacher assigned successfully!';
        // Redirect to the same page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['message'] = 'Error assigning teacher: ' . $conn->error;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
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
    <link rel="stylesheet" href="admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .all-faculties-option {
        background-color:rgb(248, 248, 250);
        font-weight: bold;
        font-style: italic;
        color: #6c757d;
        }

        /* If you want to style all options */
        .form-select option {
        padding: 8px;
        background-color: white;
        }

        /* If you want to style the dropdown when it's open */
        .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>

<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2>Admin</h2>
    <a href="../index.php" class="btn btn-danger" id="logout-btn">Log out</a>
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

<h4 class="text-center mb-3">View Subjects </h4>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-info">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php
    
    $facultyQuery = "SELECT fcid, name FROM faculty WHERE status = 1"; 
    $facultyResult = $conn->query($facultyQuery);
    $faculties = $facultyResult->fetch_all(MYSQLI_ASSOC);

    $selectedFaculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';

    $sql = "SELECT 
                subject.subid,
                subject.subname, 
                subject.subcode, 
                faculty.name AS faculty_name, 
                subject.rank,
                subject.fullmarks, 
                subject.passmarks, 
                subject.crhr
            FROM subject 
            JOIN faculty ON subject.faculty = faculty.fcid
            WHERE faculty.status = 1";

    if ($selectedFaculty) {
        $sql .= " AND faculty.fcid = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($selectedFaculty) {
        $stmt->bind_param("i", $selectedFaculty);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $subjectList = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-start align-items-center mt-3">

    <button type="button" class="btn btn-success d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
        <i class="fas fa-book me-2"></i> Add Subject
    </button>

    <button type="button" class="btn btn-success d-flex align-items-center me-3" data-bs-toggle="modal" data-bs-target="#assignTeacherModal">
        <i class="fas fa-book me-2"></i> Assign Teacher
    </button>

    <form method="get" action="" class="btn btn-success d-flex align-items-center me-4">
        <label for="faculty">Select Faculty:</label>
        <select name="faculty" id="faculty" onchange="this.form.submit()">
        <option value="" class="all-faculties-option">--All Faculties--</option>
            <?php foreach ($faculties as $faculty): ?>
                <option value="<?= $faculty['fcid'] ?>" <?= $faculty['fcid'] == $selectedFaculty ? 'selected' : '' ?>>
                    <?= htmlspecialchars($faculty['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>


<!-- Table for displaying subjects -->
<table border="1">
    <thead>
        <tr>
            <th>Subject Name</th>
            <th>Subject Code</th>
            <th>Faculty</th>
            <th>Class Rank</th>
            <th>Full Marks</th>
            <th>Pass Marks</th>
            <th>Credit Hours</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (count($subjectList) > 0): ?>
        <?php foreach ($subjectList as $subject): ?>
            <tr>
                <td><?php echo htmlspecialchars($subject['subname']); ?></td>
                <td><?php echo htmlspecialchars($subject['subcode']); ?></td>
                <td><?php echo htmlspecialchars($subject['faculty_name']); ?></td>
                <td><?php echo htmlspecialchars($subject['rank']); ?></td>
                <td><?php echo htmlspecialchars($subject['fullmarks']); ?></td>
                <td><?php echo htmlspecialchars($subject['passmarks']); ?></td>
                <td><?php echo htmlspecialchars($subject['crhr']); ?></td>
                <td>
                    <div class="d-flex align-items-center">
                        <button onclick="editSubject(<?php echo htmlspecialchars($subject['subid']); ?>,
                            '<?php echo htmlspecialchars($subject['subname']); ?>',
                            <?php echo htmlspecialchars($subject['fullmarks']); ?>,
                            <?php echo htmlspecialchars($subject['passmarks']); ?>,
                            <?php echo htmlspecialchars($subject['crhr']); ?>)" 
                            type="button" class="btn btn-primary mx-1 edit-button" 
                            data-bs-toggle="modal" data-bs-target="#editSubjectModal" >Edit
                        </button>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="subid" value="<?php echo $subject['subid']; ?>">
                            <button class="delete-button" name="delete_subject">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">No subjects found</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>


</div>
</div>
<!-- Assign Subject Teacher Modal -->
<div class="modal fade" id="assignTeacherModal" tabindex="-1" aria-labelledby="assignTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignTeacherModalLabel">Assign Subject Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Assign Teacher Form -->
                <form id="assignTeacherForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="assign_teacher" value="1">
                    
                    <!-- Select Faculty -->
                    <div class="mb-3">
                        <label for="facultySelect" class="form-label">Select Faculty</label>
                        <select class="form-select" id="facultySelect" name="facultyId" required>
                            <option value="" disabled selected>Select Faculty</option>
                            <?php echo $facultyOptions; ?>
                        </select>
                    </div>

                    <!-- Select Class -->
                    <div class="mb-3">
                        <label for="classSelect" class="form-label">Select Class</label>
                        <select class="form-select" id="classSelect" name="classId" required>
                            <option value="" disabled selected>Select Class</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subjectSelect" class="form-label">Select Subject</label>
                        <select class="form-select" id="subjectSelect" name="subjectId" required>
                            <option value="" disabled selected>Select Subject</option>
                        </select>
                    </div>
                    
                    <!-- Select Teacher -->
                    <div class="mb-3">
                        <label for="teacherSelect" class="form-label">Select Teacher</label>
                        <select class="form-select" id="teacherSelect" name="teacherId" required>
                            <option value="" disabled selected>Select Teacher</option>
                            <?php echo $teacherOptions; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-2">Assign Teacher</button>
                </form>
            </div>
        </div>
    </div>
</div>





<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubjectModalLabel">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Subject Form -->
                <form id="subjectForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="add_subject" value="1">

                    <div class="mb-3">
                        <label for="subjectFacultySelect" class="form-label">Select Faculty</label>
                        <select class="form-select" id="subjectFacultySelect" name="facultyId" required>
                            <option value="" disabled selected>Select Faculty</option>
                            <?php echo $facultyOptions; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subjectName" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="subjectName" name="subjectName" required>
                    </div>
                    <div class="mb-3">
                        <label for="subjectCode" class="form-label">Subject Code</label>
                        <input type="text" class="form-control" id="subjectCode" name="subjectCode" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subjectClassRank" class="form-label">Class Rank</label>
                        <input type="number" class="form-control" id="subjectClassRank" name="subjectClassRank" required min="1" max="9">
                    </div>

                    <div class="mb-3">
                        <label for="subjectFullMark" class="form-label">Full Marks</label>
                        <input type="number" class="form-control" id="subjectFullMark" name="subjectFullMark" required>
                    </div>
                    <div class="mb-3">
                        <label for="subjectPassMark" class="form-label">Pass Marks</label>
                        <input type="number" class="form-control" id="subjectPassMark" name="subjectPassMark" required>
                    </div>

                     <!-- Credit Hours -->
                     <div class="mb-3">
                        <label for="addCreditHours" class="form-label">Credit Hours</label>
                        <input type="number" class="form-control" id="addCreditHours" name="addCreditHours" required>
                    </div>

                    <button type="submit" class="btn btn-primary mt-2">Add Subject</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Edit Subject Form -->
                <form id="editSubjectForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="edit_subject" value="1">
                    <input type="hidden" name="subjectId" id="subjectId"> <!-- Hidden field for subject ID -->
                    
                    <!-- Subject Name -->
                    <div class="mb-3">
                        <label for="editSubjectName" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="editSubjectName" name="subjectName" required>
                    </div>

                    <!-- Full Marks -->
                    <div class="mb-3">
                        <label for="editSubjectFullMark" class="form-label">Full Marks</label>
                        <input type="number" class="form-control" id="editSubjectFullMark" name="editSubjectFullMark" required>
                    </div>

                    <!-- Pass Marks -->
                    <div class="mb-3">
                        <label for="editSubjectPassMark" class="form-label">Pass Marks</label>
                        <input type="number" class="form-control" id="editSubjectPassMark" name="editSubjectPassMark" required>
                    </div>

                    <!-- Credit Hours -->
                    <div class="mb-3">
                        <label for="editCreditHours" class="form-label">Credit Hours</label>
                        <input type="number" class="form-control" id="editCreditHours" name="editCreditHours" required>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary mt-2">Update Subject</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script>

function editSubject(subjectId,subjectName,fullMarks,passMarks,crhr) {
        document.getElementById('subjectId').value = subjectId;
        document.getElementById('editSubjectName').value = subjectName;
        document.getElementById('editSubjectFullMark').value = fullMarks;
        document.getElementById('editSubjectPassMark').value = passMarks;
        document.getElementById('editCreditHours').value = crhr;
        
}


document.getElementById('facultySelect').addEventListener('change', function () {
    const facultyId = this.value;
    fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?fetchData&type=class&id=${facultyId}`)
        .then(response => response.json())
        .then(classes => {
            const classSelect = document.getElementById('classSelect');
            classSelect.innerHTML = '<option value="" disabled selected>Select Class</option>';
            classes.forEach(cls => {
                classSelect.innerHTML += `<option value="${cls.cid}">${cls.classname}</option>`;
            });
        });
});




document.getElementById('classSelect').addEventListener('change', function () {
    const classId = this.value;
    const facultyId = document.getElementById('facultySelect').value;
    
    fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?fetchData&type=subject&id=${classId}&facultyId=${facultyId}`)
        .then(response => response.json())
        .then(subjects => {
            const subjectSelect = document.getElementById('subjectSelect');
            subjectSelect.innerHTML = '<option value="" disabled selected>Select Subject</option>';
            if (subjects.length > 0) {
                subjects.forEach(subject => {
                    subjectSelect.innerHTML += `<option value="${subject.subid}">${subject.subname}</option>`;
                });
            } else {
                subjectSelect.innerHTML += '<option value="" disabled>No subjects found</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching subjects:', error);
            const subjectSelect = document.getElementById('subjectSelect');
            subjectSelect.innerHTML = '<option value="" disabled selected>Error loading subjects</option>';
        });
});


</script>

</body>

</html>
