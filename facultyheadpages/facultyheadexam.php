<?php 
session_start();
ob_start(); // Start output buffering

ini_set('display_errors', 1); // Enable error reporting for debugging
error_reporting(E_ALL); // Report all errors

// Database connection
$host = 'localhost';
$user = 'root'; 
$password = ''; 
$database = 'cims'; 

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logo for the form (if needed)
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
$logoURL = ($result && $result->num_rows > 0) ? '../logoimages/' . $result->fetch_assoc()['logo'] : 'default-logo.png';

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

// Fetch active classes for a given faculty and batch
if (isset($_GET['fetch']) && $_GET['fetch'] === 'classes') {
    $facultyId = intval($_GET['faculty_id']);
    $batchId = intval($_GET['batch_id']);
    
    $query = "SELECT cid, classname, rank FROM class WHERE faculty = ? AND batch = ? AND status = 1";
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

// Fetch rank for a given class
if (isset($_GET['fetch']) && $_GET['fetch'] === 'class_rank') {
    $classId = intval($_GET['class_id']);
    
    $query = "SELECT rank FROM class WHERE cid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['rank' => $row['rank']]);
    } else {
        echo json_encode(['rank' => null]);
    }
    exit;
}

// Fetch subjects based on class rank and faculty ID
if (isset($_GET['fetch']) && $_GET['fetch'] === 'subjects') {
    $rank = intval($_GET['class']);
    $facultyId = intval($_GET['faculty']);

    // Modified query to get subjects based on rank and faculty
    $query = "SELECT subid, subname FROM subject s 
              JOIN faculty f ON s.faculty = f.fcid 
              WHERE s.rank = ? AND s.faculty = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $rank, $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    echo json_encode($subjects);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_exam'])) {
    $examId = intval($_POST['exam_id']);

    // Step 1: Check if the exam is used in the marks table
    $checkQuery = "SELECT COUNT(*) FROM marks WHERE exam_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $examId);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        // Exam is used in marks table, ask for confirmation
        echo "<script>
            if (confirm('This exam has marks recorded. Do you want to delete the exam and its associated marks?')) {
                // User confirmed, proceed with deletion
                window.location.href = 'connectionpage/delete_exam.php?exam_id=$examId&confirm=yes';
            } else {
                // User canceled, redirect back
                window.location.href = '" . $_SERVER['PHP_SELF'] . "';
            }
        </script>";
        exit();
    } else {
        // Step 2: If the exam is not used, proceed with deletion
        $sql = "DELETE FROM exam WHERE exam_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $examId);

        if ($stmt->execute()) {
            echo "<script>alert('Exam deleted successfully!'); window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
            exit();
        } else {
            echo "<script>alert('Error deleting exam: " . $conn->error . "'); window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
        }
        $stmt->close();
    }
}

// Handle form submission for exam creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['examName'])) {
    // Initialize response array
    $response = ['status' => 'error', 'message' => ''];

    // Collect POST data
    $classId = intval($_POST['class']); // Make sure you are using the actual class ID, not rank
    $examName = $_POST['examName'];
    $examDate = $_POST['examDate'];
    $faculty = $_POST['faculty'];
    $duration = $_POST['duration'];
    $remarks = $_POST['remarks'];
    $subjects = $_POST['subjects'];
    $batch = $_POST['batch']; 

    // Validate input
    $currentDate = new DateTime();
    $submittedDate = DateTime::createFromFormat('Y-m-d', $examDate);

    if (empty($classId)) {
        $response['message'] = 'Please select a valid class.';
    } elseif (empty($subjects)) {
        $response['message'] = 'Please select at least one subject.';
    } elseif (empty($batch)) {
        $response['message'] = 'Please select a valid batch.'; 
    } elseif (!$submittedDate || $submittedDate < $currentDate) {
        $response['message'] = 'The exam date must be today or a future date.';
    } else {
        // Insert exam data
        $query = "INSERT INTO exam (exam_name, exam_date, faculty_id, class_id, duration, remarks, batch) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssiiisi", $examName, $examDate, $faculty, $classId, $duration, $remarks, $batch); // Correct binding
        if ($stmt->execute()) {
            $examId = $stmt->insert_id;

            // Insert subjects
            $subjectQuery = "INSERT INTO exam_subjects (exam_id, subject_id) VALUES (?, ?)";
            $subjectStmt = $conn->prepare($subjectQuery);
            foreach ($subjects as $subject) {
                $subjectStmt->bind_param("ii", $examId, $subject);
                $subjectStmt->execute();
            }
            // Set success response
            $response['status'] = 'success';
            $response['message'] = 'Exam created successfully!';
        } else {
            // Error during execution
            $response['message'] = 'Error: ' . $stmt->error;
        }
    }

    // Return JSON response
    echo json_encode($response);
    header('Location: '.$_SERVER['REQUEST_URI']);
    exit;
}

ob_end_flush(); // End output buffering and flush
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty head Dashhboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0, 0, 0, 0.5); 
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            text-align: center;
        }

        .modal-content button {
            margin-top: 10px;
        }
        /* Form container styles */
        .exam-form {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Form title styles */
        .form-title {
            color: #2c3e50;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        /* Label styles */
        .form-label {
            color: #495057;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        /* Input field styles */
        .form-control, .form-select {
            border: 1.5px solid #dee2e6;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
            background-color: #fff;
        }

        /* Form group spacing */
        .form-group-wrapper {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        /* Required field indicator */
        .required-field::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
        }

        /* Button styles */
        .form-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            padding: 1rem;
            border-top: 2px solid #e9ecef;
        }

        .btn {
            padding: 0.75rem 2rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #4a90e2;
            border: none;
        }

        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        /* Input placeholder styles */
        ::placeholder {
            color: #adb5bd;
            opacity: 0.8;
        }

        /* Invalid input styles */
        .form-control:invalid:focus, .form-select:invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>

</head>

<body>


<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2>Faculty Head </h2>
    <a href="../index.php" class="btn btn-danger" id="logout-btn">Log out</a>
</header>   

<div class="d-flex">
    <!-- Sidebar navigation -->
    <nav class="sidebar bg-dark text-white p-3" style="width: 150px;">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex align-items-center" href="facultyheaddashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> 
                    Dashboard
                </a>
            </li>

            <!-- Teachers -->
            <li class="nav-item">
                <a href="facultyheadteachers.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i> Teachers
                </a>
            </li>

            <!-- Students -->
            <li class="nav-item">
                <a href="facultyheadstudents.php" class="nav-link text-white">
                    <i class="fas fa-user-graduate me-2"></i> Students
                </a>
            </li>

            <!-- Exam -->
            <li class="nav-item">
                <a href="facultyheadexam.php" class="nav-link text-white">
                    <i class="fas fa-pen me-2"></i> Exam
                </a>
            </li>

            <!-- Marks -->
            <li class="nav-item">
                <a href="facultyheadmarks.php" class="nav-link text-white">
                    <i class="fas fa-pen me-2"></i> Marks
                </a>
            </li>

            <!-- Result -->
            <li class="nav-item">
                <a href="facultyheadresult.php" class="nav-link text-white">
                    <i class="fas fa-chart-line me-2"></i> Result
                </a>
            </li>

        </ul>
    </nav>


    <!-- Main content area -->
    <div class="main-content flex-grow-1 p-3 ">
    
        
        <div class="container mt-5">
        <h2 class="form-title" style="text-align: center;">Create New Exam</h2>
        <!-- <h2>Create New Exam</h2> -->
            <form id="examForm" class="exam-form mt-4" onsubmit="return validateForm(event)">
                <div class="form-group-wrapper">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="examName" class="form-label" >Exam Name</label>
                        <input type="text" class="form-control" id="examName" placeholder="Enter exam name" name="examName"  pattern="^[A-Za-z0-9\s]+$" title="Only letters, numbers, and spaces are allowed."required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="examDate" class="form-label" min="<?= date('Y-m-d'); ?>" title="Please select today's date or a future date." >Exam Start Date</label>
                        <input type="date" class="form-control" id="examDate" name="examDate" required>
                    </div>
                    
                </div> 

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
                </div>   
                
                <div class="mb-3">
                    <label for="subjects" class="form-label">Subjects</label>
                    <select class="form-select" id="subjects" name="subjects[]" multiple>
                        <option value="">Select Subjects</option>
                    </select>
                    <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple subjects.</small>
                </div>
                <div class="mb-3">
                    <label for="duration" class="form-label" >Duration (in hours)</label>
                    <input type="number" class="form-control" id="duration" name="duration" min="1" max="3" step="1" title="Please enter a valid number greater than 0 and less than 3" required>
                </div>
                <div class="mb-3">
                    <label for="remarks" class="form-label"  >Remarks</label>
                    <input type="text" class="form-control" id="remarks" name="remarks" pattern="^[A-Za-z\s]+$" title="Only letters and spaces are allowed.">
                </div>
                <button type="submit" class="btn btn-primary" onclick="createExam()">Create Exam</button>
                <div id="messageModal" class="modal">
                    <div class="modal-content">
                        <p id="modalMessage"></p>
                        <button class="btn btn-primary" onclick="closeModal()">OK</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
                <div class="container mt-5">
                    <h2 class="form-title" style="text-align: center;">Created Exams</h2>
                    <table >
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Date</th>
                                <th>Faculty</th>
                                <th>Class</th>
                                <th>Batch</th>
                                <th>Duration</th>
                                <th>Remarks</th>
                                <th>Subjects</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            // Assuming session is already started and faculty_id is stored
                            $faculty_id = $_SESSION['faculty_type'];

                            // Query to fetch exams only for the logged-in faculty
                            $query = "SELECT exam.exam_id, exam.exam_name, exam.exam_date, faculty.name AS faculty_name, 
                                            class.classname, exam.duration, exam.remarks, exam.batch,
                                            GROUP_CONCAT(subject.subname SEPARATOR ', ') AS subjects 
                                    FROM exam
                                    INNER JOIN faculty ON exam.faculty_id = faculty.fcid
                                    INNER JOIN class ON exam.class_id = class.cid
                                    INNER JOIN exam_subjects ON exam.exam_id = exam_subjects.exam_id
                                    INNER JOIN subject ON exam_subjects.subject_id = subject.subid
                                    WHERE exam.faculty_id = '$faculty_id'
                                    GROUP BY exam.exam_id";

                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>{$row['exam_name']}</td>
                                            <td>{$row['exam_date']}</td>
                                            <td>{$row['faculty_name']}</td>
                                            <td>{$row['classname']}</td>
                                            <td>{$row['batch']}</td>
                                            <td>{$row['duration']} hrs</td>
                                            <td>{$row['remarks']}</td>
                                            <td>{$row['subjects']}</td>
                                            <td>
                                                <form method='POST' style='display:inline;'>
                                                    <input type='hidden' name='exam_id' value='{$row['exam_id']}'>
                                                    <button type='submit' name='delete_exam' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this exam?\");'>Delete</button>
                                                </form>
                                            </td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No exams found.</td></tr>";
                            }
                            ?>

                        </tbody>
                    </table>
                </div>





<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            document.getElementById('subjects').innerHTML = '<option value="">Select Subjects</option>';
        })
        .catch(error => console.error('Error fetching batches:', error));
});

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

document.getElementById('class').addEventListener('change', function () {
    const classId = this.value; // Selected class ID
    const facultyId = document.getElementById('faculty').value;

    if (!classId || !facultyId) return; // Ensure both class and faculty are selected

    // Fetch rank based on selected class
    fetch(`?fetch=class_rank&class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.rank) {
                const rank = data.rank;

                // Fetch subjects based on rank and facultyId
                return fetch(`?fetch=subjects&class=${rank}&faculty=${facultyId}`);
            } else {
                throw new Error('Rank not found for the selected class.');
            }
        })
        .then(response => response.json())
        .then(subjects => {
            const subjectSelect = document.getElementById('subjects');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            subjects.forEach(subject => {
                subjectSelect.innerHTML += `<option value="${subject.subid}">${subject.subname}</option>`;
            });
        })
        .catch(error => console.error('Error:', error));
});


function createExam(){
    fetch('',{
        method: 'POST',
        body: new FormData(document.getElementById('examForm'))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalMessage').innerText = data.message;
            document.getElementById('messageModal').style.display = 'block';
        } else {
            document.getElementById('modalMessage').innerText = data.message;
            document.getElementById('messageModal').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalMessage').innerText = 'An error occurred. Please try again.';
        document.getElementById('messageModal').style.display = 'block';
    });
}
// Close the modal
function closeModal() {
    const modal = document.getElementById('messageModal');
    modal.style.display = 'none';
}

function validateForm(event) {
        const form = document.getElementById('examForm');
        if (!form.checkValidity()) {
            // Stop form submission if validation fails
            event.preventDefault();
            event.stopPropagation();
            alert("Please fix the errors in the form before submitting.");
            return false;
        }

        // Check for additional constraints (optional)
        const examDate = document.getElementById('examDate').value;
        const today = new Date().toISOString().split('T')[0];
        if (examDate < today) {
            alert("Exam Date must be today or a future date.");
            return false;
        }

        // Allow form submission if all validations pass
        return true;
    }

    
</script>
</body>
</html>