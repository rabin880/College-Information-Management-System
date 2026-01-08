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

// Fetch the logo for the header
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' .$row['logo'];
} else {
    $logoURL = 'default-logo.png'; 
}



// Fetch batches
$sql = "SELECT b.batch_id, b.batch_year, b.faculty_id, f.name AS faculty_name 
        FROM batches b
        JOIN faculty f ON b.faculty_id = f.fcid WHERE f.status = 1";

$batches = $conn->query($sql);



// Fetch faculties for dropdown
$faculties = [];
$facultyQuery = "SELECT fcid, name FROM faculty where status=1";
$result = $conn->query($facultyQuery);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $faculties[] = $row;
        }
    } else {
        echo "No faculties found in the database.";
    }
} else {
    echo "Error in faculty query: " . $conn->error;
}



// Add batch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addBatch'])) {
    $batchYear = $_POST['batchYear'];
    $facultyId = $_POST['facultyId'];

    $addSql = "INSERT INTO batches (batch_year, faculty_id) VALUES ('$batchYear', '$facultyId')";
    if ($conn->query($addSql)) {
        header("Location: adminbatch.php");
    } else {
        echo "Error: " . $conn->error;
    }
}

// Edit batch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editBatch'])) {
    $batchId = $_POST['batchId'];
    $batchYear = $_POST['batchYear'];
    $facultyId = $_POST['facultyId'];

    $editSql = "UPDATE batches SET batch_year = '$batchYear', faculty_id = '$facultyId' WHERE batch_id = '$batchId'";
    if ($conn->query($editSql)) {
        header("Location: adminbatch.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Delete batch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteBatch'])) {
    $batchId = $_POST['batchId'];

    // Deleting batch
    $deleteSql = "DELETE FROM batches WHERE batch_id = '$batchId'";
    if ($conn->query($deleteSql)) {
        $successMessage = "Batch deleted successfully.";
        header("Location: adminbatch.php");
        exit();
    } else {
        $errorMessage = "Error deleting batch: " . $conn->error;
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
</head>

<body>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

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
<h4 class="text-center mb-3">Manage Batches </h4>

    <!-- Add Batch Button -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addBatchModal">
        Add Batch
    </button>

    <!-- Batches Table -->
    <table class="table table-bordered">
        <thead>
        <tr>
            <th class="d-none">Batch ID</th>
            <th>Batch Year</th>
            <th>Faculty</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($batches->num_rows > 0): ?>
            <?php while ($row = $batches->fetch_assoc()): ?>
                <tr>
                    <td class="d-none"><?= $row['batch_id'] ?></td>
                    <td><?= $row['batch_year'] ?></td>
                    <td><?= $row['faculty_name'] ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editBatchModal<?= $row['batch_id'] ?>">Edit</button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteBatchModal<?= $row['batch_id'] ?>">Delete</button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editBatchModal<?= $row['batch_id'] ?>" tabindex="-1" aria-labelledby="editBatchModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="post" action="">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Batch</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="batchId" value="<?= $row['batch_id'] ?>">
                                    <div class="mb-3">
                                        <label for="batchYear" class="form-label">Batch Year</label>
                                        <input type="year" class="form-control" name="batchYear" value="<?= htmlspecialchars($row['batch_year']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="facultyId" class="form-label">Faculty</label>
                                        <select class="form-select" name="facultyId" required>
                                            <?php if (!empty($faculties)): ?>
                                                <?php foreach ($faculties as $faculty): ?>
                                                    <option value="<?= htmlspecialchars($faculty['fcid']) ?>" 
                                                        <?= isset($row['faculty_id']) && $row['faculty_id'] == $faculty['fcid'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($faculty['name']) ?>
                                                    </option>

                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option disabled>No faculties available</option>
                                            <?php endif; ?>
                                        </select>




                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="editBatch" class="btn btn-primary">Save Changes</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


                <!-- Delete Modal -->
                <div class="modal fade" id="deleteBatchModal<?= $row['batch_id'] ?>" tabindex="-1" aria-labelledby="deleteBatchModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="post" action="">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Delete Batch</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete the batch for <?= $row['batch_year'] ?>?</p>
                                    <input type="hidden" name="batchId" value="<?= $row['batch_id'] ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="deleteBatch" class="btn btn-danger">Delete</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No data available.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1" aria-labelledby="addBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="batchYear" class="form-label">Batch Year</label>
                        <input type="year" class="form-control" name="batchYear" required>
                    </div>
                    <div class="mb-3">
                        <label for="facultyId" class="form-label">Faculty</label>
                        <select class="form-select" name="facultyId" required>
                            <option value="" selected disabled>-- Select Faculty --</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?= $faculty['fcid'] ?>"><?= $faculty['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addBatch" class="btn btn-primary">Add Batch</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>





<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<!-- Bootstrap JavaScript and dependencies -->


</body>
</html>