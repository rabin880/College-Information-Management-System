<?php
session_start();
$host = 'localhost';
$user = 'root'; 
$password = ''; 
$database = 'cims';   
$errorMessage = '';

// Establish a connection to the database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logo
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png'; // Fallback logo if database query fails
}


// Handle Notice Insertion
if (isset($_POST['add_notice'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $notice_date = $_POST['notice_date'];

    // File Upload
    $attachment = '';
    if (!empty($_FILES['attachment']['name'])) {
        $targetDir = "../noticepic/";
        $attachment = $targetDir . basename($_FILES['attachment']['name']);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment);
    }

    $stmt = $conn->prepare("INSERT INTO notices (title, description, notice_date, attachment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $description, $notice_date, $attachment);
    $stmt->execute();
    $stmt->close();
    header("Location: noticeSettings.php");
}

// Handle Notice Deletion
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    // Get the file path
    $stmt = $conn->prepare("SELECT attachment FROM notices WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->bind_result($attachment);
    $stmt->fetch();
    $stmt->close();

    // Delete file if exists
    if ($attachment && file_exists($attachment)) {
        unlink($attachment);
    }

    // Delete notice
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();

    header("Location: noticeSettings.php");
}

// Handle Notice Update
if (isset($_POST['edit_notice'])) {
    $id = $_POST['edit_id'];
    $title = $_POST['edit_title'];
    $description = $_POST['edit_description'];
    $notice_date = $_POST['edit_notice_date'];

    // File upload logic
    if (!empty($_FILES['edit_attachment']['name'])) {
        $targetDir = "../noticepic/";
        $attachment = $targetDir . basename($_FILES['edit_attachment']['name']);
        move_uploaded_file($_FILES['edit_attachment']['tmp_name'], $attachment);

        $stmt = $conn->prepare("UPDATE notices SET title=?, description=?, notice_date=?, attachment=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $description, $notice_date, $attachment, $id);
    } else {
        $stmt = $conn->prepare("UPDATE notices SET title=?, description=?, notice_date=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $description, $notice_date, $id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: noticeSettings.php");
}

// Fetch Notices
$notices = $conn->query("SELECT * FROM notices");



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
<div class="main-content flex-grow-1 ">
<div class="container mt-4">
    <h2>Manage Notices</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addNoticeModal">Add Notice</button>

    <!-- Notices Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Date</th>
                <th>Attachment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $notices->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['notice_date']) ?></td>
                <td>
                    <?php if ($row['attachment']): ?>
                        <a href="<?= htmlspecialchars($row['attachment']) ?>" target="_blank">View</a>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm edit-btn"
                        data-bs-toggle="modal" data-bs-target="#editNoticeModal"
                        data-id="<?= $row['id'] ?>"
                        data-title="<?= htmlspecialchars($row['title']) ?>"
                        data-description="<?= htmlspecialchars($row['description']) ?>"
                        data-date="<?= $row['notice_date'] ?>">
                        Edit
                    </button>
                    <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="title" class="form-control mb-2" placeholder="Title" required>
                    <textarea name="description" class="form-control mb-2" placeholder="Description" required></textarea>
                    <input type="date" name="notice_date" class="form-control mb-2" required>
                    <input type="file" name="attachment" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_notice" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Notice Modal -->
<div class="modal fade" id="editNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id">
                    <input type="text" name="edit_title" class="form-control mb-2" required>
                    <textarea name="edit_description" class="form-control mb-2" required></textarea>
                    <input type="date" name="edit_notice_date" class="form-control mb-2" required>
                    <input type="file" name="edit_attachment" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_notice" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
                    </div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelector('[name="edit_id"]').value = btn.dataset.id;
        document.querySelector('[name="edit_title"]').value = btn.dataset.title;
        document.querySelector('[name="edit_description"]').value = btn.dataset.description;
        document.querySelector('[name="edit_notice_date"]').value = btn.dataset.date;
    });
});
</script>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>
