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
    $logoURL = 'default-logo.png'; 
}


// Handle faculty status toggle
if (isset($_POST['fcid']) && isset($_POST['new_status'])) {
    $fcid = $_POST['fcid'];
    $newStatus = $_POST['new_status']; // 1 for Active, 0 for Inactive

    // Prepare SQL query to update faculty status
    $sqlUpdate = "UPDATE faculty SET status = ? WHERE fcid = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("ii", $newStatus, $fcid); // "ii" for integer parameters

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh page
        exit();
    } else {
        echo "Error updating faculty status: " . $conn->error;
    }
    $stmt->close();
}


//retrieve data from the database in the table (view Faculty)
$facultyList = [];
$sql = "SELECT * FROM faculty";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $facultyList[] = $row; // Store each faculty row in an array
    }
} else {
    // Error retrieving data
    echo "Error retrieving faculty data: " . $conn->error;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $facultyName = trim($_POST['facultyName']);
    $facultyType = $_POST['facultyType'];

    // Validate inputs
    if (empty($facultyName) || empty($facultyType)) {
        $errorMessage = "All fields are required!";
    } else {
        // Check if the faculty already exists
        $sqlCheck = "SELECT * FROM faculty WHERE name = ?";
        $stmt = $conn->prepare($sqlCheck);
        $stmt->bind_param("s", $facultyName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "Faculty already exists!";
        } else {
            // Insert faculty into the database
            $sqlInsert = "INSERT INTO faculty (name, type, status) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($sqlInsert);
            $stmt->bind_param("ss", $facultyName, $facultyType);

            if ($stmt->execute()) {
                $successMessage = "Faculty added successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $errorMessage = "Error adding faculty: " . $conn->error;
            }
        }
        $stmt->close();
    }
}


$editMessage = "";

// Handle faculty update
if (isset($_POST['edit_fcid']) && !empty($_POST['edit_fcid'])) {
    $editFcid = $_POST['edit_fcid'];
    $editFacultyName = $_POST['editFacultyName'];
    $editFacultyType = $_POST['editFacultyType'];

    // Prepare SQL query to update the faculty record
    $sqlUpdate = "UPDATE faculty SET name = ?, type = ? WHERE fcid = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("ssi", $editFacultyName, $editFacultyType, $editFcid);

    if ($stmt->execute()) {
        $editMessage = "Faculty updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $editMessage = "Error updating faculty: " . $conn->error;
    }
    $stmt->close();
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
<h4 class="text-center mb-3">Manage Faculty</h4>

<!-- Button with Icon -->
<button type="button" class="btn btn-success me-2 mb-0 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
  <i class="fas fa-user-plus me-2"></i> Add Faculty
</button>
        
<!-- Faculty Table -->
<table border="1">
    <thead>
        <tr>
            <th>Faculty Name</th>
            <th>Faculty Type</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($facultyList) > 0): ?>
            <?php foreach ($facultyList as $faculty): ?>
                <tr>
                    <td><?php echo htmlspecialchars($faculty['name']); ?></td>
                    <td><?php echo htmlspecialchars($faculty['type']); ?></td>
                    <td>
                        <?php echo ($faculty['status'] == 1) ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>'; ?>
                    </td>
                    <td>
                        <!-- Edit Button -->
                        <button type="button" class="btn btn-warning edit-button" data-bs-toggle="modal" data-bs-target="#editFacultyModal" 
                                data-fcid="<?php echo htmlspecialchars($faculty['fcid']); ?>"
                                data-facultyname="<?php echo htmlspecialchars($faculty['name']); ?>"
                                data-facultytype="<?php echo htmlspecialchars($faculty['type']); ?>"
                                data-status="<?php echo htmlspecialchars($faculty['status']); ?>">
                            Edit
                        </button>
                        
                        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display:inline;">
                            <input type="hidden" name="fcid" value="<?php echo htmlspecialchars($faculty['fcid']); ?>">
                            <input type="hidden" name="new_status" value="<?php echo ($faculty['status'] == 1) ? 0 : 1; ?>">
                            <button type="submit" class="toggle-button" style="background-color: <?php echo ($faculty['status'] == 1) ? '#f44336' : '#4CAF50'; ?>; color: white; padding: 5px 10px; border: none; cursor: pointer;">
                                <?php echo ($faculty['status'] == 1) ? 'Phase Out' : 'Continue '; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">No faculty found</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</div>

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFacultyModalLabel">Add Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="facultyForm" method="POST" action="" onsubmit="return validate1()">
                    <div class="mb-3">
                        <label for="facultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="facultyName" name="facultyName" placeholder="Enter Faculty Name" required >
                    </div>
                    <div class="mb-3">
                        <label for="facultyType" class="form-label">Type</label>
                        <select class="form-select" name="facultyType" required>
                            <option value="" selected disabled>Select Type</option>
                            <option value="Year">Year</option>
                            <option value="Semester">Semester</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 

<!-- Add the Edit Faculty Modal -->
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFacultyModalLabel">Edit Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editFacultyForm" method="POST" action="" onsubmit="return validate1()">
                    <input type="hidden" id="edit_fcid" name="edit_fcid">
                    <div class="mb-3">
                        <label for="editFacultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="editFacultyName" name="editFacultyName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFacultyType" class="form-label">Type</label>
                        <select class="form-select" name="editFacultyType" required>
                            <option value="" selected disabled>Select Type</option>
                            <option value="Year">Year</option>
                            <option value="Semester">Semester</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 

<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
    // Triggered when an edit button is clicked
    const editButtons = document.querySelectorAll('.edit-button');
    editButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const fcid = button.getAttribute('data-fcid');
            const facultyName = button.getAttribute('data-facultyname');
            const facultyType = button.getAttribute('data-facultytype');

            // Fill modal fields
            document.getElementById('edit_fcid').value = fcid;
            document.getElementById('editFacultyName').value = facultyName;
            document.querySelector('select[name="editFacultyType"]').value = facultyType;
        });
    });
</script>


</body>

</html>


