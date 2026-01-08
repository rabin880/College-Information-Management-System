<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'cims');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png'; // Fallback logo if database query fails
}


// Initialize variables
$collegeName = '';
$aboutUs = '';
$logo = '';
$logoHistory = [];
$currentLogoPath = ''; // To store the current logo path

// Fetch data from the database
$sql = "SELECT * FROM basicinfo LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $collegeName = $row['name'];
    $aboutUs = $row['aboutus'];
    $logo = $row['logo'];
    $currentLogoPath = $logo; // Store the current logo

    // Fetch logo history if any
    if ($logo) {
        $logoHistory = array_filter(explode(',', $logo)); // Use a comma-separated list for history
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collegeName = $_POST['collegeName'];
    $aboutUs = $_POST['aboutUs'];
    $newLogo = '';

    // Handle logo upload
    if (!empty($_FILES['collegeLogo']['name'])) {
        $targetDir = "../logoimages/";
        $newLogo = basename($_FILES['collegeLogo']['name']);
        $targetFilePath = $targetDir . $newLogo;
        move_uploaded_file($_FILES['collegeLogo']['tmp_name'], $targetFilePath);

        // Add the new logo to the history
        if ($currentLogoPath) {
            // If there's an existing logo, add it to the history
            $logoHistory[] = $currentLogoPath;
        }

        // Update the current logo
        $currentLogoPath = $newLogo;
    }

    // Update the database with the new data
    $logoString = implode(',', $logoHistory);
    if ($result->num_rows > 0) {
        $sql = "UPDATE basicinfo SET name=?, aboutus=?, logo=? WHERE bid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $collegeName, $aboutUs, $currentLogoPath, $row['bid']);
    } else {
        $sql = "INSERT INTO basicinfo (name, aboutus, logo) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $collegeName, $aboutUs, $currentLogoPath);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Data saved successfully!');</script>";
    } else {
        echo "<script>alert('Error saving data.');</script>";
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
    <div class="main-content flex-grow-1">
        <div class="container mt-0 p-2 bg-light rounded shadow-sm" style="max-width: 800px;">
            <h4 class="text-center mb-3">Basic Settings</h4>
            <form method="POST" enctype="multipart/form-data">
                <!-- College Name -->
                <div class="mb-4">
                    <label for="collegeName" class="form-label fw-bold">College Name</label>
                    <input type="text" class="form-control form-control-lg shadow-sm" id="collegeName" name="collegeName" value="<?php echo htmlspecialchars($collegeName); ?>" placeholder="Enter the college name">
                </div>

                <!-- Logo Upload -->
                <div class="mb-4">
                    <label for="collegeLogo" class="form-label fw-bold">College Logo</label>
                    <div class="input-group">
                        <input type="file" class="form-control shadow-sm" id="collegeLogo" name="collegeLogo" aria-describedby="uploadHelp">
                        <label class="input-group-text bg-primary text-white" for="collegeLogo">Upload</label>
                    </div>
                    <small id="uploadHelp" class="form-text text-muted">Supported formats: JPG, PNG (Max size: 2MB)</small>
                </div>

                <!-- Previous Logos -->
                <?php
                // Define the path to the folder containing the logos
                $logoFolder = "../logoimages/";

                // Scan the directory and get all files
                $logoFiles = array_diff(scandir($logoFolder), array('..', '.'));

                // Filter only image files (JPG, PNG)
                $imageFiles = [];
                foreach ($logoFiles as $file) {
                    $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                        $imageFiles[] = $file;
                    }
                }
                ?>

                <?php if (!empty($imageFiles)): ?>
                <div class="mb-4">
                    <label class="form-label fw-bold">Previous Logos</label>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($imageFiles as $logoPath): ?>
                        <div class="p-2">
                            <img src="../logoimages/<?php echo $logoPath; ?>" alt="Logo" class="img-thumbnail" style="height: 100px; width: 100px;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <p>No previous logos found.</p>
                <?php endif; ?>

                <!-- Current Logo -->
                <?php if ($currentLogoPath): ?>
                <div class="mb-4">
                    <label class="form-label fw-bold">Current Logo</label>
                    <img src="../logoimages/<?php echo $currentLogoPath; ?>" alt="Current Logo" class="img-thumbnail" style="height: 100px; width: 100px;">
                </div>
                <?php endif; ?>

                <!-- About Us -->
                <div class="mb-4">
                    <label for="aboutUs" class="form-label fw-bold">About Us</label>
                    <textarea class="form-control form-control-lg shadow-sm" id="aboutUs" name="aboutUs" rows="5" placeholder="Enter the about us details"><?php echo htmlspecialchars($aboutUs); ?></textarea>
                </div>

                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>




<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>
