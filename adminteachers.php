<?php
$servername = "localhost"; 
$username = "root";
$password = "";
$dbname = "cims";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";


$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png'; // Fallback logo if database query fails
}



//list available faculties
$facultyOptions = '';
$sql = "SELECT * FROM faculty where status=1";  
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facultyOptions .= '<option value="' . htmlspecialchars($row['fcid']) . '">' . htmlspecialchars($row['name']) . '</option>';
    }
} else {
    $facultyOptions = '<option value="">No faculty available</option>';
}




// Search filter
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch staff data with role and faculty name, including search functionality
$staffList = [];
$sql = "SELECT stafflog.*, role.name AS role_name, faculty.name AS faculty_name 
        FROM stafflog 
        LEFT JOIN role ON stafflog.role = role.rid
        LEFT JOIN faculty ON stafflog.faculty_id = faculty.fcid 
        WHERE faculty.status = 1 
        AND (stafflog.name LIKE ? OR faculty.name LIKE ? OR role.name LIKE ?)"; 

$stmt = $conn->prepare($sql);
$searchTermLike = "%" . $searchTerm . "%";
$stmt->bind_param("sss", $searchTermLike, $searchTermLike, $searchTermLike);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staffList[] = $row;
    }
} else {
    $staffList = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TeacherName'], $_POST['teacherUsername'], $_POST['teacherPassword'], $_POST['facultyName'])) {
    // Capture and sanitize form data
    $teacherName = $conn->real_escape_string(trim($_POST['TeacherName']));
    $teacherUsername = $conn->real_escape_string(trim($_POST['teacherUsername']));
    $teacherPassword = $conn->real_escape_string(trim($_POST['teacherPassword']));
    $facultyId = intval($_POST['facultyName']);

    // Check if the teacher already exists (by name or username)
    $checkSql = "SELECT * FROM stafflog WHERE username = ? OR name = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $teacherUsername, $teacherName);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Teacher already exists
        $errorMessage = "A teacher with this name or username already exists.";
    } else {
        // Insert the teacher into the database
        $sql = "INSERT INTO stafflog (name, username, password, faculty_id, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $roleId = 3;

        $stmt->bind_param("sssii", $teacherName, $teacherUsername, $teacherPassword, $facultyId, $roleId);

        if ($stmt->execute()) {
            // Display success message or redirect
            $successMessage = "Teacher added successfully.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            // Handle error
            $errorMessage = "Error adding teacher: " . $conn->error;
        }

        $stmt->close();
    }

    $checkStmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stid'])) {
    $deleteId = intval($_POST['delete_stid']);

    // Step 1: Delete related records in subject_teacher first (to avoid foreign key constraint issues)
    $sqlDeleteSubjects = "DELETE FROM subject_teacher WHERE teacher_id = ?";
    $stmt1 = $conn->prepare($sqlDeleteSubjects);
    $stmt1->bind_param("i", $deleteId);
    $stmt1->execute();
    $stmt1->close();

    // Step 2: Delete the teacher from stafflog
    $sqlDeleteTeacher = "DELETE FROM stafflog WHERE stid = ?";
    $stmt2 = $conn->prepare($sqlDeleteTeacher);
    $stmt2->bind_param("i", $deleteId);

    if ($stmt2->execute()) {
        // Redirect after successful deletion
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error deleting teacher: " . $conn->error;
    }

    $stmt2->close();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_stid'], $_POST['editTeacherName'], $_POST['editTeacherUsername'], $_POST['editTeacherPassword'], $_POST['editFacultyName'])) {
    $editId = intval($_POST['edit_stid']);
    $editName = $conn->real_escape_string(trim($_POST['editTeacherName']));
    $editUsername = $conn->real_escape_string(trim($_POST['editTeacherUsername']));
    $editPassword = $conn->real_escape_string(trim($_POST['editTeacherPassword']));
    $editFacultyId = intval($_POST['editFacultyName']);

    $sql = "UPDATE stafflog SET name = ?, username = ?, password = ?, faculty_id = ? WHERE stid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $editName, $editUsername, $editPassword, $editFacultyId, $editId);

    if ($stmt->execute()) {
        $successMessage = "Teacher details updated successfully.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $errorMessage = "Error updating teacher: " . $conn->error;
    }

    $stmt->close();
}



if (isset($_GET['faculty_id'])) {
    $faculty_id = intval($_GET['faculty_id']);

    $sql = "SELECT stid, name FROM stafflog WHERE faculty_id = ? AND role = 3"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }

    // Return the teachers as JSON
    echo json_encode($teachers);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facultyId'], $_POST['teacherId'])) {
    // Capture the faculty and teacher ID from the form
    $facultyId = intval($_POST['facultyId']);
    $teacherId = intval($_POST['teacherId']);

    // Start a transaction to ensure data consistency
    $conn->begin_transaction();

    try {
        // Check if the faculty already has a faculty head (role = 2)
        $checkFacultyHeadSql = "SELECT stid FROM stafflog WHERE faculty_id = ? AND role = 2";
        $checkStmt = $conn->prepare($checkFacultyHeadSql);
        $checkStmt->bind_param("i", $facultyId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Faculty already has a faculty head, update the existing one to role 3 (Teacher)
            $existingFacultyHead = $checkResult->fetch_assoc();
            $existingFacultyHeadId = $existingFacultyHead['stid'];

            $updateExistingHeadSql = "UPDATE stafflog SET role = 3 WHERE stid = ?";
            $updateStmt = $conn->prepare($updateExistingHeadSql);
            $updateStmt->bind_param("i", $existingFacultyHeadId);
            $updateStmt->execute();
        }

        // Set the selected teacher's role to 2 (Faculty Head)
        $updateTeacherSql = "UPDATE stafflog SET role = 2 WHERE stid = ?";
        $updateTeacherStmt = $conn->prepare($updateTeacherSql);
        $updateTeacherStmt->bind_param("i", $teacherId);
        $updateTeacherStmt->execute();

        // Commit the transaction
        $conn->commit();

        // Success message
        $successMessage = "Faculty Head assigned successfully.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        // If any error occurs, roll back the transaction
        $conn->rollback();
        $errorMessage = "Error assigning Faculty Head: " . $e->getMessage();
    }
}



$conn->close();
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
        .custom-search {
            height: 50px; /* Increase height */
            font-size: 18px; /* Make text larger */
            border-radius: 10px; /* Smooth edges */
            width: 300px; /* Adjust width */
        }
        .custom-btn {
            height: 50px; /* Match button height */
            font-size: 18px;
            border-radius: 10px;
            padding: 0 20px; /* Improve button padding */
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

<div class="main-content flex-grow-1 p-3">
<h4 class="text-center mb-3">Manage Teachers </h4>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($errorMessage); ?>
    </div>
<?php endif; ?>



<div class="d-flex gap-3">
    <!-- Add Teacher Button -->
    <button type="button" class="btn d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#addTeacherModal"
        style="background-color: #4caf50; color: white; border: none; border-radius: 5px; padding: 10px 15px; font-weight: bold; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
        <i class="fas fa-chalkboard-teacher me-2"></i> Add Teacher
    </button>

    <!-- Assign Faculty Head Button -->
    <button type="button" class="btn d-flex align-items-center me-2" data-bs-toggle="modal" data-bs-target="#assignFacultyHeadModal"
        style="background-color: #007bff; color: white; border: none; border-radius: 5px; padding: 10px 15px; font-weight: bold; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
        <i class="fas fa-user-cog me-2"></i> Assign Faculty Head
    </button>

    <form method="get" action="" class="d-flex align-items-center justify-content-center">
        <div class="d-flex gap-3 w-90"> <!-- Adjust width -->
            <input type="text" name="search" class="form-control custom-search" 
                placeholder="Search by Name, Faculty, or Role" 
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit" class="btn btn-primary custom-btn">Search</button>
        </div>
    </form>
</div>

<!-- Teachers Table -->
<table border="1">
    <thead>
        <tr>
            <th>Teacher Name</th>
            <th>Username</th>
            <th>Password</th>
            <th>Faculty</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($staffList) > 0): ?>
            <?php foreach ($staffList as $staff): ?>
                <tr>
                    <td><?php echo htmlspecialchars($staff['name']); ?></td>
                    <td><?php echo htmlspecialchars($staff['username']); ?></td>
                    <td><?php echo htmlspecialchars($staff['password']); ?></td>
                    <td><?php echo htmlspecialchars($staff['faculty_name']); ?></td>
                    <td><?php echo htmlspecialchars($staff['role_name']); ?></td>
                    <td>
                        <button type="button" class="edit-button btn btn-primary" data-bs-toggle="modal" data-bs-target="#editTeacherModal"
                            data-id="<?php echo htmlspecialchars($staff['stid']); ?>"
                            data-name="<?php echo htmlspecialchars($staff['name']); ?>"
                            data-username="<?php echo htmlspecialchars($staff['username']); ?>"
                            data-password="<?php echo htmlspecialchars($staff['password']); ?>"
                            data-faculty="<?php echo htmlspecialchars($staff['faculty_id']); ?>">
                            Edit
                        </button>

                        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this teacher?');">
                            <input type="hidden" name="delete_stid" value="<?php echo htmlspecialchars($staff['stid']); ?>">
                            <button type="submit" class="delete-button btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No teachers found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>



<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addTeacherModalLabel">Add Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="facultyHeadForm" method="post">
          <div>
            <label for="faculty-name" class="form-label">Select Faculty</label>
            <select class="form-select mb-3" name="facultyName" id="facultyHeadName" required>
              <option value="" selected disabled>Select Faculty</option>
              <?php echo $facultyOptions; ?>
            </select>
          </div>

          <label for="facultyHeadName" class="form-label">Teacher Name</label>
          <input type="text" class="form-control mb-3" id="thName" name="TeacherName" placeholder="Enter Teacher Name" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed." >

          <label for="teacherUsername" class="form-label">Teacher Username</label>
          <input type="text" class="form-control mb-3" id="teacherUsername" name="teacherUsername" required>

          <label for="teacherPassword" class="form-label">Teacher Password</label>
          <input type="text" class="form-control mb-3" id="teacherPassword" name="teacherPassword" required>

          <button type="button" class="btn btn-secondary me-3" id="generateCredentialsBtnFH">Generate Username & Password</button>
          <button type="submit" class="btn btn-primary">Add Teacher</button>
        </form>
      </div>
    </div>
  </div>
</div>



<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTeacherModalLabel">Edit Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editTeacherForm" method="post">
          <input type="hidden" name="edit_stid" id="editTeacherId">

          <div>
            <label for="editFacultyName" class="form-label">Select Faculty</label>
            <select class="form-select mb-3" name="editFacultyName" id="editFacultyName" required>
              <option value="" selected disabled>Select Faculty</option>
              <?php echo $facultyOptions; ?>
            </select>
          </div>

          <label for="editTeacherName" class="form-label">Teacher Name</label>
          <input type="text" class="form-control mb-3" id="editTeacherName" name="editTeacherName" placeholder="Enter Teacher Name" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed.">

          <label for="editTeacherUsername" class="form-label">Teacher Username</label>
          <input type="text" class="form-control mb-3" id="editTeacherUsername" name="editTeacherUsername">

          <label for="editTeacherPassword" class="form-label">Teacher Password</label>
          <input type="text" class="form-control mb-3" id="editTeacherPassword" name="editTeacherPassword">

          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Assign Faculty Head Modal -->
<div class="modal fade" id="assignFacultyHeadModal" tabindex="-1" aria-labelledby="assignFacultyHeadModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="assignFacultyHeadModalLabel">Assign Faculty Head</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <form id="assignFacultyHeadForm" method="post" action="">
          <!-- Faculty Selection -->
          <div>
            <label for="faculty-select" class="form-label">Select Faculty</label>
            <select class="form-select mb-3" name="facultyId" id="facultySelect" required onchange="fetchTeachers(this.value)">
                <option value="" selected disabled>Select Faculty</option>
                <?php echo $facultyOptions; ?>
            </select>
          </div>

          <!-- Teachers List -->
          <div>
            <label for="teacher-select" class="form-label">Select Teacher</label>
            <select class="form-select mb-3" name="teacherId" id="teacherSelect" required>
              <option value="" selected disabled>Select Teacher</option>
              
            </select>
          </div>

          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" class="btn btn-primary">Assign Faculty Head</button>
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
    document.getElementById("generateCredentialsBtnFH").addEventListener("click", function () {
        const nameField = document.getElementById("thName"); // Corrected the ID to match the form
        const usernameField = document.getElementById("teacherUsername");
        const passwordField = document.getElementById("teacherPassword");

        const name = nameField.value.trim();

        if (name.length < 2) {
            alert("Please enter a valid Teacher name with at least two characters.");
            return;
        }

        // Generate Username
        const firstLetter = name.charAt(0).toUpperCase();
        const lastLetter = name.charAt(name.length - 1).toUpperCase();
        const randomNumber = Math.floor(1000 + Math.random() * 9000); // 4-digit random number
        const username = `CIMS${firstLetter}${lastLetter}${randomNumber}`;

        // Generate Password
        const password = `CIMS${firstLetter}${lastLetter}`;

        // Set the generated values to the input fields
        usernameField.value = username;
        passwordField.value = password;
    });

    document.addEventListener('DOMContentLoaded', () => {
        const editButtons = document.querySelectorAll('.edit-button');
        const editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));

        editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const teacherId = button.getAttribute('data-id');
            const teacherName = button.getAttribute('data-name');
            const teacherUsername = button.getAttribute('data-username');
            const teacherPassword = button.getAttribute('data-password');
            const teacherFaculty = button.getAttribute('data-faculty');

            document.getElementById('editTeacherId').value = teacherId;
            document.getElementById('editTeacherName').value = teacherName;
            document.getElementById('editTeacherUsername').value = teacherUsername;
            document.getElementById('editTeacherPassword').value = teacherPassword;
            document.getElementById('editFacultyName').value = teacherFaculty;

            editModal.show();
        });
        });
    });


    // Function to fetch teachers based on selected faculty
    function fetchTeachers(facultyId) {
        if (facultyId) {
            // Make AJAX request to get teachers for the selected faculty
            $.ajax({
                url: "", // The current PHP file will handle the request
                method: "GET",
                data: { faculty_id: facultyId },
                success: function(response) {
                    // Parse the response and update the teacher select options
                    const teachers = JSON.parse(response);
                    const teacherSelect = document.getElementById("teacherSelect");

                    // Clear previous options
                    teacherSelect.innerHTML = '<option value="" selected disabled>Select Teacher</option>';

                    // Add new options for teachers
                    teachers.forEach(teacher => {
                        const option = document.createElement("option");
                        option.value = teacher.stid;
                        option.textContent = teacher.name;
                        teacherSelect.appendChild(option);
                    });
                },
                error: function() {
                    alert("Failed to fetch teachers. Please try again.");
                }
            });
        } else {
            // If no faculty is selected, clear the teacher select options
            document.getElementById("teacherSelect").innerHTML = '<option value="" selected disabled>Select Teacher</option>';
        }
    }

    


    
</script>


</body>
</html>


