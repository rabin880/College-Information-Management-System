<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cims";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logo for the header
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' .$row['logo'];
} else {
    $logoURL = 'default-logo.png'; 
}

// Fetch faculties for dropdown
$faculties = [];
$facultyQuery = "SELECT fcid, name FROM faculty where status=1";
$result = $conn->query($facultyQuery);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $faculties[] = $row;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['request_type'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'data' => null];

    switch ($_GET['request_type']) {
        case 'get_batches':
            if (isset($_GET['faculty_id'])) {
                $facultyId = (int)$_GET['faculty_id'];
                $sql = "SELECT * FROM batches WHERE faculty_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $facultyId);
                $stmt->execute();
                $result = $stmt->get_result();
                $batches = [];
                while ($row = $result->fetch_assoc()) {
                    $batches[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $batches;
            }
            break;

        case 'get_active_class':
            if (isset($_GET['faculty_id'], $_GET['batch_id'])) {
                $facultyId = (int)$_GET['faculty_id'];
                $batchId = (int)$_GET['batch_id'];
                
                $sql = "SELECT c.cid, c.classname 
                        FROM class c 
                        WHERE c.faculty = ? 
                        AND c.batch = ? 
                        AND c.status = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $facultyId, $batchId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $response['success'] = true;
                    $response['data'] = $result->fetch_assoc();
                }
            }
            break;

        case 'get_available_classes':
            if (isset($_GET['faculty_id'], $_GET['batch_id'])) {
                $facultyId = (int)$_GET['faculty_id'];
                $batchId = (int)$_GET['batch_id'];
                
                $sql = "SELECT cid, classname FROM class WHERE faculty = ? AND batch = ? AND status = 0";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $facultyId, $batchId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $classes = [];
                while ($row = $result->fetch_assoc()) {
                    $classes[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $classes;
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    if (isset($_POST['faculty'], $_POST['batch'], $_POST['active_class_id'])) {
        $faculty = (int)$_POST['faculty'];
        $batch = (int)$_POST['batch'];
        $activeClassId = (int)$_POST['active_class_id'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Step 1: Set all classes for this faculty/batch combination to inactive
            $sql = "UPDATE class SET status = 2 WHERE faculty = ? AND batch = ? AND status = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $faculty, $batch);
            $stmt->execute();

            // Step 2: Set the selected class as active
            $sql = "UPDATE class SET status = 1 WHERE cid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $activeClassId);
            $stmt->execute();

            $sql = "UPDATE studentlog 
                    SET classid = 
                        CASE 
                            WHEN classid = '' OR classid IS NULL THEN ? 
                            WHEN FIND_IN_SET(?, classid) = 0 THEN CONCAT(classid, ',', ?) 
                            ELSE classid 
                        END 
                    WHERE batch = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $activeClassId, $activeClassId, $activeClassId, $batch);
            $stmt->execute();


            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Active class updated, and students' class records appended successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Error updating records: " . $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Active Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="College Logo" style="height: 50px;">
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
                <a href="adminstd.php" class="nav-link text-white">
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

    <!-- Main content -->
    <div class="main-content flex-grow-1 p-3">
        <h4 class="text-center mb-3">Manage Active Class</h4>

        <form id="assignClassForm" class="mx-auto" style="max-width: 600px;">
            <div class="mb-3">
                <label for="facultyId" class="form-label">Faculty</label>
                <select class="form-select" id="facultyId" name="faculty" required>
                    <option value="" disabled selected>Select a faculty</option>
                    <?php foreach ($faculties as $faculty): ?>
                        <option value="<?= htmlspecialchars($faculty['fcid']) ?>"><?= htmlspecialchars($faculty['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="batch" class="form-label">Batch</label>
                <select class="form-select" name="batch" id="batch" required>
                    <option value="">Select a batch</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="currentClass" class="form-label">Current Active Class</label>
                <input type="text" class="form-control" id="currentClass" readonly>
            </div>

            <div class="mb-3">
                <label for="activeClassSelect" class="form-label">Select New Active Class</label>
                <select class="form-select" id="activeClassSelect" name="active_class_id" required>
                    <option value="">Select Class</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Active Class</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('facultyId').addEventListener('change', async function() {
    const facultyId = this.value;
    const batchSelect = document.getElementById('batch');
    const currentClassInput = document.getElementById('currentClass');
    const activeClassSelect = document.getElementById('activeClassSelect');
    
    if (facultyId) {
        try {
            const response = await fetch(`?request_type=get_batches&faculty_id=${facultyId}`);
            const data = await response.json();
            
            if (data.success) {
                batchSelect.innerHTML = '<option value="">Select a batch</option>' +
                    data.data.map(batch => 
                        `<option value="${batch.batch_id}">${batch.batch_year}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    } else {
        batchSelect.innerHTML = '<option value="">Select a batch</option>';
        currentClassInput.value = '';
        activeClassSelect.innerHTML = '<option value="">Select Class</option>';
    }
});

document.getElementById('batch').addEventListener('change', async function() {
    const facultyId = document.getElementById('facultyId').value;
    const batchId = this.value;
    const currentClassInput = document.getElementById('currentClass');
    
    if (facultyId && batchId) {
        try {
            // Get current active class
            const activeClassResponse = await fetch(`?request_type=get_active_class&faculty_id=${facultyId}&batch_id=${batchId}`);
            const activeClassData = await activeClassResponse.json();
            
            if (activeClassData.success) {
                currentClassInput.value = activeClassData.data.classname;
            } else {
                currentClassInput.value = 'No active class assigned';
            }
            
            // Get available classes
            const availableClassesResponse = await fetch(`?request_type=get_available_classes&faculty_id=${facultyId}&batch_id=${batchId}`);
            const availableClassesData = await availableClassesResponse.json();
            
            if (availableClassesData.success) {
                const activeClassSelect = document.getElementById('activeClassSelect');
                activeClassSelect.innerHTML = '<option value="">Select Class</option>' +
                    availableClassesData.data.map(cls => 
                        `<option value="${cls.cid}">${cls.classname}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    } else {
        currentClassInput.value = '';
        document.getElementById('activeClassSelect').innerHTML = '<option value="">Select Class</option>';
    }
});

document.getElementById('assignClassForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        alert(result.message);
        
        if (result.success) {
            // Refresh the current class display
            const facultyId = document.getElementById('facultyId').value;
            const batchId = document.getElementById('batch').value;
            const activeClassResponse = await fetch(`?request_type=get_active_class&faculty_id=${facultyId}&batch_id=${batchId}`);
            const activeClassData = await activeClassResponse.json();
            
            if (activeClassData.success) {
                document.getElementById('currentClass').value = activeClassData.data.classname;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating the active class');
    }
});
</script>

</body>
</html>