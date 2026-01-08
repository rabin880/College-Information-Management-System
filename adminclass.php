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

// Handle AJAX requests first
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    $response = array();

    if (isset($_POST['classname'], $_POST['faculty'], $_POST['batch'], $_POST['status'],$_POST['rank'])) {
        $classname = $conn->real_escape_string($_POST['classname']);
        $faculty = $conn->real_escape_string($_POST['faculty']);
        $batch = $conn->real_escape_string($_POST['batch']);
        $status = $conn->real_escape_string($_POST['status']);
        $srank = $conn->real_escape_string($_POST['rank']);

        $sql = "INSERT INTO `class` (`classname`, `faculty`, `batch`, `status`,`rank`) 
                VALUES ('$classname', '$faculty', '$batch', '$status','$srank')";

        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
            $response['message'] = "New class added successfully!";
            // Fetch the newly added row's data
            $newId = $conn->insert_id;
            $sql = "SELECT c.*, f.name AS faculty_name, b.batch_year 
                    FROM `class` c
                    LEFT JOIN `faculty` f ON c.faculty = f.fcid
                    LEFT JOIN `batches` b ON c.batch = b.batch_id
                    WHERE c.cid = $newId";
            $result = $conn->query($sql);
            if ($row = $result->fetch_assoc()) {
                $response['newRow'] = $row;
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Error: " . $conn->error;
        }
        echo json_encode($response);
        exit;
    }
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

//fetch class for deletion
if (isset($_GET['id'])) {
    $classID = $_GET['id'];
    $sql = "SELECT * FROM `class` WHERE `cid` = $classID ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row); 
    }
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
    } else {
        echo "No faculties found in the database.";
    }
} else {
    echo "Error in faculty query: " . $conn->error;
}

// Check if the faculty ID is passed for batch fetching
if (isset($_GET['faculty_id'])) {
    $facultyId = $_GET['faculty_id'];
    $sql = "SELECT * FROM batches WHERE faculty_id = $facultyId";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['batch_id'] . '">' . $row['batch_year'] . '</option>';
        }
    } else {
        echo '<option value="">No batches available</option>';
    }
    exit; 
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
<h4 class="text-center mb-3">Manage class </h4>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addClassModal">Add New Class</button>

    <?php
        $statusFilter = isset($_GET['status']) && $_GET['status'] == 'inactive' ? [0, 2] : [1];
        $newStatus = isset($_GET['status']) && $_GET['status'] == 'inactive' ? 'active' : 'inactive';

        $sql = "SELECT c.*, f.name AS faculty_name, b.batch_year 
                FROM `class` c
                LEFT JOIN `faculty` f ON c.faculty = f.fcid
                LEFT JOIN `batches` b ON c.batch = b.batch_id
                WHERE c.status IN (" . implode(',', array_fill(0, count($statusFilter), '?')) . ")
                ORDER BY f.name, c.classname"; 

        $stmt = $conn->prepare($sql);
        
        $types = str_repeat('i', count($statusFilter)); // Generate the type string dynamically
        $stmt->bind_param($types, ...$statusFilter);

        $stmt->execute();
        $result = $stmt->get_result();
    ?>


    <!-- Toggle Button -->
    <a href="?status=<?= $newStatus ?>" class="btn btn-primary mb-3">
        Show <?= $newStatus == 'inactive' ? 'Inactive' : 'Active' ?> Classes
    </a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Faculty</th>
                <th>Batch</th>
                <th>Session Rank</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $prevFaculty = ''; // Store the last faculty name to avoid repeating it

            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['classname'] ?></td>
                    <td>
                        <?php 
                        // Display faculty name only if it changes
                        if ($prevFaculty != $row['faculty_name']) {
                            echo $row['faculty_name'];
                            $prevFaculty = $row['faculty_name'];
                        } else {
                            echo ""; // Keep the column visually empty for grouping effect
                        }
                        ?>
                    </td>
                    <td><?= $row['batch_year'] ?></td>
                    <td><?= $row['rank'] ?></td>
                    <td><?= $row['status'] == 1 ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?= $row['cid'] ?>" data-bs-toggle="modal" data-bs-target="#editClassModal">Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['cid'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>



</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addClassForm">
                        
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
                            <label for="classname" class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="classname" name="classname" required pattern="^[A-Za-z0-9\s]+$" title="Only letters and spaces are allowed.">
                        </div>

                        <div class="mb-3">
                            <label for="rank" class="form-label">Session Rank</label>
                            <input type="number" class="form-control" id="rank" name="rank" required min="1" max="9">
                                </div>
                        

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Class</button>
                    </form>
                </div>
            </div>
        </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editClassForm">
                    <input type="hidden" id="editClassId" name="id">
                    <div class="mb-3">
                        <label for="editClassName" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="editClassName" name="classname" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSessionRank" class="form-label">Session Rank</label>
                        <input type="number" class="form-control" id="editSessionRank" name="rank" required min="1" max="9">
    <div class="invalid-feedback">Session Rank must be between 1 and 9.</div>
</div>

<script>
    document.getElementById('editSessionRank').addEventListener('input', function () {
        if (this.value > 9) {
            this.value = 9;
        }
    });
</script>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.getElementById('facultyId').addEventListener('change', function() {
        var facultyId = this.value;
        if (facultyId) {
            // Send AJAX request to fetch batches for the selected faculty
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '?faculty_id=' + facultyId, true);
            xhr.onload = function() {
                if (xhr.status == 200) {
                    // Populate the batches dropdown with the response
                    document.getElementById('batch').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        } else {
            // Clear the batch dropdown if no faculty is selected
            document.getElementById('batch').innerHTML = '<option value="">Select a batch</option>';
        }
    });
    
    document.getElementById('facultyId').addEventListener('change', function() {
    var facultyId = this.value;
    if (facultyId) {
        fetch('?faculty_id=' + facultyId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('batch').innerHTML = html;
            });
    } else {
        document.getElementById('batch').innerHTML = '<option value="">Select a batch</option>';
    }
});


document.getElementById('addClassForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add the new row to the table
            const tbody = document.querySelector('table tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="d-none">${data.newRow.cid}</td>
                <td>${data.newRow.classname}</td>
                <td>${data.newRow.faculty_name}</td>
                <td>${data.newRow.batch_year}</td>
                <td>${data.newRow.rank}</td>
                <td>${data.newRow.status == 1 ? 'Active' : 'Inactive'}</td>
                <td>
                    <button class="btn btn-warning btn-sm edit-btn" data-id="${data.newRow.cid}" data-bs-toggle="modal" data-bs-target="#editClassModal">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="' . $row['cid'] . '">Delete</button>
                </td>
            `;
            tbody.appendChild(newRow);
            
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addClassModal'));
            modal.hide();
            
            // Reset the form
            this.reset();
            
            // Show success message
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the form');
    });
});

$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        var classId = $(this).data('id');
        if (confirm('Are you sure you want to delete this class?')) {
            $.ajax({
                url: 'delete_class.php', // The PHP file that handles the deletion
                type: 'POST',
                data: { id: classId },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        // Remove the row from the table
                        $(this).closest('tr').remove();
                        // Optionally, you can reload the page or update the table dynamically
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        }
    });
});

$(document).ready(function() {
    // Handle edit button click
    $('.edit-btn').on('click', function() {
        var classId = $(this).data('id');

        // Fetch class data using AJAX
        $.ajax({
            url: 'fetch_class.php', // PHP file to fetch class data
            type: 'GET',
            data: { id: classId },
            success: function(response) {
                if (response.success) {
                    // Populate the edit modal form
                    $('#editClassId').val(response.data.cid);
                    $('#editClassName').val(response.data.classname);
                    $('#editSessionRank').val(response.data.rank);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Handle form submission for editing
    $('#editClassForm').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            url: 'update_class.php', // PHP file to update class data
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#editClassModal').modal('hide');
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });
});


</script>
</body>
</html>