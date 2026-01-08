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

// Ensure directories exist
if (!file_exists('../logoimages')) {
    mkdir('../logoimages', 0777, true);
}
if (!file_exists('../gallerypics')) {
    mkdir('../gallerypics', 0777, true);
}

// Fetch logo
$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png';
}

// Handle multiple image uploads
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_images'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $uploadedFiles = [];
    
    // Process each uploaded file
    foreach ($_FILES['images']['name'] as $key => $name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $tempFile = $_FILES['images']['tmp_name'][$key];
            $imageName = time() . '_' . basename($name);
            $targetFile = '../gallerypics/' . $imageName;
            
            // Move the uploaded file
            if (move_uploaded_file($tempFile, $targetFile)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO gallery (title, image) VALUES (?, ?)");
                $stmt->bind_param("ss", $title, $imageName);
                if ($stmt->execute()) {
                    $uploadedFiles[] = $name;
                }
                $stmt->close();
            }
        }
    }
    
    if (!empty($uploadedFiles)) {
        $successMessage = count($uploadedFiles) . " images uploaded successfully!";
    } else {
        $errorMessage = "Error uploading images.";
    }
}

// Handle Image Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_image'])) {
    $id = intval($_POST['id']);
    $title = htmlspecialchars($_POST['title']);

    // Fetch current image
    $stmt = $conn->prepare("SELECT image FROM gallery WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($currentImage);
    $stmt->fetch();
    $stmt->close();

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "../gallerypics/";
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . $imageName;

        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                if ($currentImage && file_exists("../gallerypics/" . $currentImage)) {
                    unlink("../gallerypics/" . $currentImage);
                }
                $stmt = $conn->prepare("UPDATE gallery SET title = ?, image = ? WHERE id = ?");
                $stmt->bind_param('ssi', $title, $imageName, $id);
            } else {
                $errorMessage = "Failed to upload new image";
            }
        } else {
            $errorMessage = "Invalid file type";
        }
    } else {
        $stmt = $conn->prepare("UPDATE gallery SET title = ? WHERE id = ?");
        $stmt->bind_param('si', $title, $id);
    }

    if (!isset($errorMessage)) {
        $stmt->execute();
        $successMessage = "Image updated successfully";
    }
    $stmt->close();
}

// Handle Image Delete
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    $stmt = $conn->prepare("SELECT image FROM gallery WHERE id = ?");
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $stmt->bind_result($image);
    $stmt->fetch();
    $stmt->close();

    if ($image && file_exists("../gallerypics/" . $image)) {
        unlink("../gallerypics/" . $image);
    }

    $stmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $stmt->close();

    $successMessage = "Image deleted successfully";
}

// Fetch All Images with pagination
$perPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$totalResult = $conn->query("SELECT COUNT(*) FROM gallery");
$totalRow = $totalResult->fetch_row();
$totalImages = $totalRow[0];
$totalPages = ceil($totalImages / $perPage);

$gallery = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gallery-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .card {
            margin-bottom: 20px;
            height: 100%;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
    </style>
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
<div class="main-content flex-grow-1">
        <div class="container mt-4">
            <h2>Gallery Management</h2>
            
            <!-- Display messages -->
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addImageModal">
                <i class="fas fa-plus"></i> Add Images
            </button>

            <!-- Gallery Grid -->
            <div class="row">
                <?php if ($gallery->num_rows > 0): ?>
                    <?php while ($row = $gallery->fetch_assoc()): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card">
                                <img src="../gallerypics/<?php echo htmlspecialchars($row['image']); ?>" 
                                     class="card-img-top gallery-img" 
                                     alt="<?php echo htmlspecialchars($row['title']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                    <div class="d-flex justify-content-between">
                                        <button class="btn btn-warning btn-sm edit-btn" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editImageModal">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?delete_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this image?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No images found in the gallery.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Multiple Images Modal -->
<div class="modal fade" id="addImageModal" tabindex="-1" aria-labelledby="addImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addImageModalLabel">Add Images to Gallery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="galleryTitle" class="form-label">Gallery Title</label>
                        <input type="text" class="form-control" id="galleryTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="galleryImages" class="form-label">Select Images</label>
                        <input class="form-control" type="file" id="galleryImages" name="images[]" multiple required accept="image/*">
                        <small class="text-muted">You can select multiple images (JPEG, PNG, GIF)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_images" class="btn btn-primary">Upload Images</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Image Modal -->
<div class="modal fade" id="editImageModal" tabindex="-1" aria-labelledby="editImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editImageModalLabel">Edit Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editImageId">
                    <div class="mb-3">
                        <label for="editImageTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="editImageTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editImageFile" class="form-label">Change Image (Optional)</label>
                        <input class="form-control" type="file" id="editImageFile" name="image" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_image" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
    // Initialize edit modal with data
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            
            document.getElementById('editImageId').value = id;
            document.getElementById('editImageTitle').value = title;
        });
    });
</script>
</body>
</html>