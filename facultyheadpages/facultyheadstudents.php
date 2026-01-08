<?php
session_start(); 
$servername = "localhost"; 
$username = "root";
$password = "";
$dbname = "cims";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";

// Fetch logo
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png'; // Fallback logo if database query fails
}



$faculty_id = $_SESSION['faculty_type'];

$searchQuery = "";
$studentList = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['search'])) {
    $searchQuery = trim($_POST['search']);
    $sql = "SELECT s.sid, s.name, s.username, s.password, s.batch, s.address, s.gender, s.email, s.dob, 
                   s.phoneno, s.parentsname, s.parentscontact, s.photo, c.classname, b.batch_year
            FROM studentlog s
            LEFT JOIN class c ON s.classid = c.cid
            LEFT JOIN batches b ON s.batch = b.batch_id
            WHERE (s.name LIKE ? OR s.username LIKE ? OR b.batch_year LIKE ?)
            AND s.faculty = ?";

    $stmt = $conn->prepare($sql);
    $likeQuery = "%$searchQuery%";
    $stmt->bind_param("sssi", $likeQuery, $likeQuery, $likeQuery, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentList = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Default view (faculty-based filtering)
    $sql = "SELECT s.sid, s.name, s.username, s.password, s.batch, s.address, s.gender, s.email, s.dob, 
                   s.phoneno, s.parentsname, s.parentscontact, s.photo, c.classname, b.batch_year
            FROM studentlog s
            LEFT JOIN class c ON s.classid = c.cid
            LEFT JOIN batches b ON s.batch = b.batch_id
            WHERE s.faculty = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentList = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
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
    
    $query = "SELECT cid, classname, rank FROM class WHERE faculty = ? AND batch = ? AND status=1";
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




// Code for deleting student with his picture
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $sid = $_POST['sid'];

    // Fetch the student's photo path before deleting
    $sql = "SELECT photo FROM studentlog WHERE sid = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $stmt->bind_result($photo);
        $stmt->fetch();
        $stmt->close();

        // Delete the student's image file if it exists
        if ($photo && file_exists("../studentpic/" . $photo)) {
            unlink("../studentpic/" . $photo); // Delete the file
        }

        // Delete the marks first
        $sql_marks = "DELETE FROM marks WHERE student_id = ?";
        $stmt_marks = $conn->prepare($sql_marks);
        if ($stmt_marks) {
            $stmt_marks->bind_param("i", $sid);
            if ($stmt_marks->execute()) {
                // After marks are deleted, delete the student record
                $sql_student = "DELETE FROM studentlog WHERE sid = ?";
                $stmt_student = $conn->prepare($sql_student);
                if ($stmt_student) {
                    $stmt_student->bind_param("i", $sid);
                    if ($stmt_student->execute()) {
                        echo "<script>alert('Marks and Student deleted successfully');</script>";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        echo "Error deleting student: " . $stmt_student->error;
                    }
                    $stmt_student->close();
                } else {
                    echo "Error preparing student delete statement: " . $conn->error;
                }
            } else {
                echo "Error deleting marks: " . $stmt_marks->error;
            }
            $stmt_marks->close();
        } else {
            echo "Error preparing marks delete statement: " . $conn->error;
        }
    } else {
        echo "Error preparing select statement: " . $conn->error;
    }
}

// Add new student logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $classid = trim($_POST['class']); // Use classid instead of class name
    $batch = trim($_POST['batch']);
    $address = trim($_POST['address']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $faculty=trim($_POST['faculty']);
    $dob = trim($_POST['dob']);
    $phoneno = trim($_POST['phoneno']);
    $parentsname = trim($_POST['parentsname']);
    $parentscontact = trim($_POST['parentscontact']);

    // Validate required fields
    if (empty($name) || empty($username) || empty($password) || empty($classid) || empty($dob) || empty($phoneno) ||empty($faculty) || empty($batch)) {
        echo "<script>alert('Error: All required fields must be filled.');</script>";
        return;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Error: Invalid email format.');</script>";
        return;
    }

    // Validate phone number (digits only)
    if (!preg_match('/^\d{10}$/', $phoneno)) {
        echo "<script>alert('Error: Invalid phone number. Must be 10 digits.');</script>";
        return;
    }

    // Validate date of birth (Age must be at least 16 years)
    $dobTimestamp = strtotime($dob);
    $age = (int)((time() - $dobTimestamp) / (365.25 * 24 * 60 * 60));
    if ($age < 16) {
        echo "<script>alert('Error: Student must be at least 16 years old.');</script>";
        return;
    }

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoName = $_FILES['photo']['name'];
        $photoTmp = $_FILES['photo']['tmp_name'];
        $photoFolder = "../studentpic/";
        $photoPath = $photoFolder . basename($photoName);

        // Validate file type (e.g., only allow images)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($photoTmp);
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Error: Invalid photo format. Only JPG and PNG allowed.');</script>";
            return;
        }

        if (move_uploaded_file($photoTmp, $photoPath)) {
            // Prepare SQL to insert new student data into studentlog
            $sql = "INSERT INTO studentlog (name, username, password, classid, address, gender, email, dob, phoneno, parentsname, parentscontact, photo, batch, faculty) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssssss", $name, $username, $password, $classid, $address, $gender, $email, $dob, $phoneno, $parentsname, $parentscontact, $photoName, $batch, $faculty);

            if ($stmt->execute()) {
                // Get the inserted student ID (sid)
                $studentId = $conn->insert_id;
                echo "<script>alert('Student added successfully!');</script>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error inserting into studentlog: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "<script>alert('Error uploading photo.');</script>";
        }
    } else {
        echo "<script>alert('Error: Photo is required.');</script>";
    }
}



?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Faculty head  Dashhboard</title>
     <!-- Bootstrap 5 CSS -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2>Faculty Head</h2>
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

<div class="main-content flex-grow-1 p-3">
<div class="container mt-3">
    <h4 class="text-center mb-3">Manage Students </h4>
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal"  data-bs-target="#addStudentModal">
                <i class="fas fa-user-plus"></i> Add Student
            </button>

        </div>

        <!-- Right Side: Search -->
        <div class="d-flex align-items-center">
            <form method="post" action="" class="d-flex align-items-center gap-2">
                <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="form-control">
                <button type="submit" class="btn btn-info">Search</button>
            </form>
        </div>
    </div>
</div>


<div class="table-responsive">
    <!-- Student Table -->
    <table border="0.5">
        <thead>
            <tr>
                <th>Batch</th>
                <th>Name</th>
                <th>Username</th>
                <th>Address</th>
                <th>Phone No</th>
                <th>Parents Name</th>
                <th>Parents Contact</th>
                <th>Photo</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($studentList) > 0): ?>
                <?php foreach ($studentList as $student): ?>
                    <tr> 
                        <td><?php echo htmlspecialchars($student['batch_year']); ?></td>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td><?php echo htmlspecialchars($student['address']); ?></td>
                        <td><?php echo htmlspecialchars($student['phoneno']); ?></td>
                        <td><?php echo htmlspecialchars($student['parentsname']); ?></td>
                        <td><?php echo htmlspecialchars($student['parentscontact']); ?></td>
                        <td><img src="../studentpic/<?php echo htmlspecialchars($student['photo']); ?>" alt="Student Photo" width="50" height="50"></td>

                        <td style="display: none;"><?php echo htmlspecialchars($student['sid']); ?></td>
                        <td style="display: none;"><?php echo htmlspecialchars($student['gender']); ?></td>
                        <td style="display: none;"><?php echo htmlspecialchars($student['email']); ?></td>
                        <td style="display: none;"><?php echo htmlspecialchars($student['password']); ?></td>
                        <td style="display: none;"><?php echo htmlspecialchars($student['dob']); ?></td>
                        <td style="display: none;"><?php echo htmlspecialchars($student['faculty']); ?></td>
                        <td style="display: none;"><?php echo htmlspecialchars($student['classid']); ?></td>

                        <td>
                            <button 
                                type="button" 
                                class="edit-button btn btn-primary"
                                data-sid="<?php echo htmlspecialchars($student['sid']); ?>">
                                Edit
                            </button>

                            <button 
                                type="button" 
                                class="view-button btn btn-info"
                                data-student='<?php echo json_encode($student); ?>'>
                                View
                            </button>
                            
                            <form method="post" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                <input type="hidden" name="sid" value="<?php echo htmlspecialchars($student['sid']); ?>">
                                <input type="hidden" name="delete" value="true">
                                <button type="submit" class="delete-button">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11">No students found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    </div>
</div>




<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStudentModalLabel">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Batch:</strong> <span id="viewBatch"></span></div>
                    <div class="col-md-6"><strong>Name:</strong> <span id="viewName"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Username:</strong> <span id="viewUsername"></span></div>
                    <div class="col-md-6"><strong>Address:</strong> <span id="viewAddress"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Phone No:</strong> <span id="viewPhone"></span></div>
                    <div class="col-md-6"><strong>Email:</strong> <span id="viewEmail"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Date of Birth:</strong> <span id="viewDob"></span></div>
                    <div class="col-md-6"><strong>Gender:</strong> <span id="viewGender"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Parent's Name:</strong> <span id="viewParentsName"></span></div>
                    <div class="col-md-6"><strong>Parent's Contact:</strong> <span id="viewParentsContact"></span></div>
                </div>
                <div class="row mb-3 text-center">
                    <div class="col-md-12">
                        <img id="viewPhoto" src="" alt="Student Photo" class="img-thumbnail" width="150">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="studentForm" method="POST" enctype="multipart/form-data"> 
            <div class="row mb-3">
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


                <div class="col-md-4">
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

                <div class="col-md-4">
                    <label for="studentName" class="form-label" maxlength="20" pattern="^[A-Za-z]+$" title="Only alphabetic characters are allowed">Name</label>
                    <input type="text" class="form-control" name="name" id="studentName" placeholder="Enter Name" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="studentUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="studentUsername" placeholder="Enter Username" required maxlength="15">
                </div>
                <div class="col-md-4">
                    <label for="studentPassword" class="form-label">Password</label>
                    <input type="text" class="form-control" name="password" id="studentPassword" placeholder="Enter Password" required minlength="8" maxlength="15">
                </div>
                <div class="col-md-4">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" name="address" id="address" placeholder="Enter Address" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" name="gender" id="gender" required>
                        <option selected disabled>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter Email" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" 
                    title="Please enter a valid email address (e.g., user@example.com)">
                </div>
                <div class="col-md-4">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="dob" id="dob" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="phoneno" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="phoneno" id="phoneno" placeholder="Enter Phone Number" required pattern="^\d{10}$" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label for="parentsName" class="form-label" maxlength="20" pattern="^[A-Za-z]+$" title="Only alphabetic characters are allowed">Parent's Name</label>
                    <input type="text" class="form-control" name="parentsname" id="parentsName" placeholder="Enter Parent's Name" required>
                </div>
                <div class="col-md-4">
                    <label for="parentsContact" class="form-label">Parent's Contact</label>
                    <input type="tel" class="form-control" name="parentscontact" id="parentsContact" placeholder="Enter Parent's Contact" required pattern="^\d{10}$" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label for="photo" class="form-label">Photo</label>
                    <input type="file" class="form-control" name="photo" id="photo" accept="image/*">
                </div>
            </div>
            <div class="row mb-4">
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>   
            </div>
        </form>

            </div>
        </div>
    </div>
</div>



<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStudentForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="sid" id="editStudentId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudentName" class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="editStudentName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="editStudentUsername" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudentPassword" class="form-label">Password</label>
                            <input type="text" class="form-control" name="password" id="editStudentPassword" required minlength="8" maxlength="15">
                            <!-- <small class="text-muted">Leave blank to keep the current password.</small> -->
                            
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentAddress" class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" id="editStudentAddress" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editStudentEmail" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phoneno" id="editStudentPhone" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudentDob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" id="editStudentDob" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStudentGender" class="form-label">Gender</label>
                            <select class="form-control" name="gender" id="editStudentGender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editParentsName" class="form-label">Parent's Name</label>
                            <input type="text" class="form-control" name="parentsname" id="editParentsName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editParentsContact" class="form-label">Parent's Contact</label>
                            <input type="tel" class="form-control" name="parentscontact" id="editParentsContact" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudentPhoto" class="form-label">Upload Photo</label>
                            <input type="file" class="form-control" name="photo" id="editStudentPhoto" accept="image/*">
                            <small class="text-muted">Only JPG and PNG formats allowed.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Photo</label><br>
                            <img id="editStudentPhotoPreview" src="" alt="Student Photo" class="img-thumbnail" width="100">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

// Handle View Button Click
$(document).on("click", ".view-button", function () {
    var student = $(this).data("student");

    $("#viewBatch").text(student.batch_year);
    $("#viewName").text(student.name);
    $("#viewUsername").text(student.username);
    $("#viewAddress").text(student.address);
    $("#viewPhone").text(student.phoneno);
    $("#viewEmail").text(student.email);
    $("#viewDob").text(student.dob);
    $("#viewGender").text(student.gender);
    $("#viewParentsName").text(student.parentsname);
    $("#viewParentsContact").text(student.parentscontact);

    // Display the student photo
    if (student.photo) {
        $("#viewPhoto").attr("src", "../studentpic/" + student.photo);
    } else {
        $("#viewPhoto").attr("src", "");
    }

    $("#viewStudentModal").modal("show");
});


$(document).on("click", ".edit-button", function () {
    var row = $(this).closest("tr");
    
    $("#editStudentId").val(row.find("input[name='sid']").val());
    $("#editStudentName").val(row.find("td:eq(1)").text().trim());
    $("#editStudentUsername").val(row.find("td:eq(2)").text().trim());
    $("#editStudentPassword").val(row.find("td:eq(11)").text().trim());
    $("#editStudentAddress").val(row.find("td:eq(3)").text().trim());
    $("#editStudentEmail").val(row.find("td:eq(10)").text().trim());
    $("#editStudentPhone").val(row.find("td:eq(6)").text().trim());
    $("#editStudentDob").val(row.find("td:eq(12)").text().trim());
    $("#editStudentGender").val(row.find("td:eq(9)").text().trim());
    $("#editParentsName").val(row.find("td:eq(5)").text().trim());
    $("#editParentsContact").val(row.find("td:eq(6)").text().trim());

    // Load the student's photo
    var photoSrc = row.find("td:eq(7)").text().trim();
    if (photoSrc) {
        $("#editStudentPhotoPreview").attr("src", "../studentpic/" + photoSrc);
    } else {
        $("#editStudentPhotoPreview").attr("src", "../studentpic/" + photoSrc);
    }

    $("#editStudentModal").modal("show");
});

// Handle form submission with AJAX
$("#editStudentForm").submit(function (e) {
    e.preventDefault();

    var formData = new FormData(this); // Allows photo upload
    $.ajax({
        url: "connectionpage/edit_students.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                alert(response.message);
                location.reload();
            } else {
                alert("Error: " + response.message);
            }
        }
    });
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


</script>



</body>

</html>


