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


$sql = "SELECT logo FROM basicinfo LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logoURL = '../logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png'; // Fallback logo if database query fails
}


// Handle Form Submission for Add/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addEvent'])) {
        $name = $_POST['eventName'];
        $description = $_POST['eventDescription'];
        $date = $_POST['eventDate'];
        $time = $_POST['eventTime'];
        $location = $_POST['eventLocation'];
        $poster = $_FILES['eventPoster']['name'];
        $posterPath = '';

        // Handle File Upload
        if ($poster) {
            $posterPath = 'uploads/' . basename($poster);
            move_uploaded_file($_FILES['eventPoster']['tmp_name'], $posterPath);
        }

        $sql = "INSERT INTO events (name, description, date, time, location, poster) 
                VALUES ('$name', '$description', '$date', '$time', '$location', '$posterPath')";
        $conn->query($sql);
    }

    if (isset($_POST['updateEvent'])) {
        $id = $_POST['eventId'];
        $name = $_POST['eventName'];
        $description = $_POST['eventDescription'];
        $date = $_POST['eventDate'];
        $time = $_POST['eventTime'];
        $location = $_POST['eventLocation'];
        $poster = $_FILES['eventPoster']['name'];
        $posterPath = '';

        if ($poster) {
            
            $sql = "SELECT poster FROM events WHERE id = $id";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $oldPoster = $row['poster'];
                if (!empty($oldPoster) && file_exists($oldPoster)) {
                    unlink($oldPoster); 
                }
            }
            $posterPath = 'uploads/' . basename($poster);
            move_uploaded_file($_FILES['eventPoster']['tmp_name'], $posterPath);
        }

        $sql = "UPDATE events 
                SET name = '$name', description = '$description', date = '$date', time = '$time', location = '$location'";
        if ($posterPath) {
            $sql .= ", poster = '$posterPath'";
        }
        $sql .= " WHERE id = $id";
        $conn->query($sql);
    }

    if (isset($_POST['deleteEvent'])) {
        $id = $_POST['eventId'];
        
        // Fetch the poster path for the event
        $sql = "SELECT poster FROM events WHERE id = $id";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $posterPath = $row['poster'];
            
            // Check if the poster file exists and delete it
            if (!empty($posterPath) && file_exists($posterPath)) {
                unlink($posterPath);  // Delete the poster file from the server
            }
        }
    
        // Now delete the event record from the database
        $sql = "DELETE FROM events WHERE id = $id";
        $conn->query($sql);
    }
}

// Fetch Event Data
$sql = "SELECT * FROM events ORDER BY date DESC";
$events = $conn->query($sql);

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
<div class="main-content flex-grow-1 ">
    <div class="container mt-0 p-2 bg-light rounded shadow-sm" style="max-width: 800px;">
    <h4 class="text-center mb-3">Manage Events</h4>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEventModal">Add Event</button>

    <!-- Event Table -->
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Date</th>
                <th>Time</th>
                <th>Location</th>
                <th>Picture</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($event = $events->fetch_assoc()): ?>
            <tr>
                <td><?php echo $event['name']; ?></td>
                <td><?php echo $event['description']; ?></td>
                <td><?php echo $event['date']; ?></td>
                <td><?php echo $event['time']; ?></td>
                <td><?php echo $event['location']; ?></td>
                <td>
                    <?php if (!empty($event['poster'])): ?>
                        <img src="<?php echo htmlspecialchars($event['poster']); ?>" alt="Event Poster" style="width: 100px; height: auto;">
                    <?php else: ?>
                        No Poster
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $event['id']; ?>">Edit</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
                        <button type="submit" name="deleteEvent" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>

            <!-- Edit Event Modal -->
            <div class="modal fade" id="editEventModal<?php echo $event['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="eventId" value="<?php echo $event['id']; ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Event</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Event Name</label>
                                    <input type="text" name="eventName" class="form-control" value="<?php echo $event['name']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="eventDescription" class="form-control" required><?php echo $event['description']; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="eventDate" class="form-control" value="<?php echo $event['date']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Time</label>
                                    <input type="time" name="eventTime" class="form-control" value="<?php echo $event['time']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="eventLocation" class="form-control" value="<?php echo $event['location']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Poster</label>
                                    <input type="file" name="eventPoster" class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="updateEvent" class="btn btn-success">Save Changes</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    
</div>
</div>


<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input type="text" name="eventName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="eventDescription" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="eventDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time</label>
                        <input type="time" name="eventTime" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="eventLocation" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poster</label>
                        <input type="file" name="eventPoster" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addEvent" class="btn btn-primary">Add Event</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

</body>

</html>
