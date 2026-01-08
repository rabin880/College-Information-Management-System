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
    $logoURL = 'logoimages/' . $row['logo'];
} else {
    $logoURL = 'default-logo.png'; // Fallback logo if database query fails
}


// Fetch login types
$roles = [];
$roleQuery = "SELECT rid, name FROM role";
$roleResult = $conn->query($roleQuery);
if ($roleResult && $roleResult->num_rows > 0) {
    while ($roleRow = $roleResult->fetch_assoc()) {
        $roles[] = $roleRow;
    }
}


// Code for login modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);
    $user_type = (int)$conn->real_escape_string($_POST['user_type']);

    // Get role name from the role table
    $roleQuery = "SELECT name FROM role WHERE rid = ?";
    $stmt = $conn->prepare($roleQuery);
    $stmt->bind_param("i", $user_type);
    $stmt->execute();
    $roleResult = $stmt->get_result();
    
    if ($roleResult && $roleResult->num_rows === 1) {
        $roleRow = $roleResult->fetch_assoc();
        $roleName = strtolower($roleRow['name']);

        $response = ['success' => false, 'message' => ''];

        // Function to check if faculty is active
        function isFacultyActive($faculty_id, $conn) {
            $facultyQuery = "SELECT status FROM faculty WHERE fcid = ?";
            $stmt = $conn->prepare($facultyQuery);
            $stmt->bind_param("i", $faculty_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $faculty = $result->fetch_assoc();
                return $faculty['status'] == 1; // Returns true if active, false otherwise
            }
            return false; // Faculty not found
        }

        if ($roleName === 'admin') {
            // Validate Admin Login
            $query = "SELECT * FROM adminlog WHERE username = ? AND password = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                $_SESSION['user_id'] = $admin['aid'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['user_username'] = $admin['username'];
                $_SESSION['user_type'] = 'Admin';
                $response = ['success' => true, 'redirect' => 'adminpages/admindashboard.php'];
            } else {
                $response['message'] = 'Invalid username or password for Admin.';
            }
        } elseif ($roleName === 'faculty head' || $roleName === 'teacher') {
            // Validate Faculty Head or Teacher Login
            $query = "SELECT * FROM stafflog WHERE username = ? AND password = ? AND role = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $username, $password, $user_type);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $staff = $result->fetch_assoc();
                
                // Check if faculty is active
                if (isFacultyActive($staff['faculty_id'], $conn)) {
                    $_SESSION['user_id'] = $staff['stid'];
                    $_SESSION['user_name'] = $staff['name'];
                    $_SESSION['user_username'] = $staff['username'];
                    $_SESSION['user_type'] = ucfirst($roleName);
                    $_SESSION['faculty_type'] = $staff['faculty_id'];
                    $redirectPage = ($roleName === 'faculty head') ? 'facultyheadpages/facultyheaddashboard.php' : 'teacherpages/teacherdashboard.php';
                    $response = ['success' => true, 'redirect' => $redirectPage];
                } else {
                    $response['message'] = 'Your faculty is inactive. Please contact the administrator.';
                }
            } else {
                $response['message'] = "Invalid username or password for $roleName.";
            }
        } elseif ($roleName === 'student') {
            // Validate Student Login
            $query = "SELECT * FROM studentlog WHERE username = ? AND password = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $student = $result->fetch_assoc();

                // Check if faculty is active
                if (isFacultyActive($student['faculty'], $conn)) {
                    $_SESSION['user_id'] = $student['sid'];
                    $_SESSION['user_name'] = $student['name'];
                    $_SESSION['user_username'] = $student['username'];
                    $_SESSION['user_type'] = 'Student';
                    $_SESSION['faculty_type'] = $student['faculty'];
                    $_SESSION['user_batch'] = $student['batch'];
                    $response = ['success' => true, 'redirect' => 'studentpages/studentdashboard.php'];
                } else {
                    $response['message'] = 'Your faculty is inactive. Please contact the administrator.';
                }
            } else {
                $response['message'] = 'Invalid username or password for Student.';
            }
        } else {
            $response['message'] = 'Invalid role type.';
        }
    } else {
        $response['message'] = 'Role not found.';
    }

    echo json_encode($response);
    exit;
}




?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My College - Your Guide to Future</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .hero {
            background-image: url('images/view.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 150px 0; /* Increased padding for more vertical space */
            text-align: center;
            height: 100vh; /* Set the hero section to fill the full viewport height */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hero h1 {
            font-size: 3rem; /* Adjusted font size for the heading */
            font-weight: bold;
        }

        .hero p {
            font-size: 1.5rem; /* Adjusted font size for the subheading */
            margin-top: 10px;
        }

        .hero .btn {
            font-size: 1.2rem;
            padding: 10px 20px;
            margin-top: 20px;
        }

        .notice-image {
            width: 100%; /* Make image responsive */
            height: 300px; /* Set fixed height */
            object-fit: cover; /* Ensure it covers the area without stretching */
            border-radius: 8px; /* Optional: Add rounded corners */
        }
    </style>
</head>
<body>
    <!-- NAVIGATION BAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white px-lg-3 py-lg-2 fixed-top">
        <div class="container-fluid">
            <!-- Logo Section -->
            <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link me-2" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="gallery.php">Gallery & Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="notices.php">Notices</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" class="border shadow p-3 rounded" method="POST">
                        <h1 class="text-center p-3">User Login</h1>
                        <div id="errorMessage" class="alert alert-danger d-none"></div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" required placeholder="Enter Username">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password" required placeholder="Enter Password">
                                <span class="input-group-text" id="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Select User Type</label>
                        </div>
                        <select class="form-select mb-3" name="user_type" required>
                            <option selected disabled>Select User Type</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['rid']); ?>">
                                    <?php echo htmlspecialchars($role['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="d-flex align-items-center justify-content-between mb-2"> 
                            <button type="submit" class="btn btn-primary">LOGIN</button>
                            <?php if (isset($errorMessage)) { echo "<p>$errorMessage</p>"; } ?>
                            <a href="javascript: void(0)" class="text-secondary text-decoration-none ms-auto">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- HERO SECTION -->
    <section class="hero d-flex align-items-center justify-content-center">
        
        <div class="container">
            <h1>Your Guide to Future</h1>
            <p>Turning passion into a future through innovation and academic success.</p>
            <a href="#about" class="btn btn-primary mt-3">Learn More</a>
        </div>
    </section>

    <!-- ABOUT SECTION -->
    <section id="about" class="container mt-5">
        <h2 class="section-title">My College</h2>
        <p class="text-center">A college experience that transcends beyond classrooms and lecture theatres.</p>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <img src="images/play1.jpg" class="card-img-top" alt="Event Image">
                    <div class="card-body">
                        <h5 class="card-title">Fun-Filled Events</h5>
                        <p class="card-text">Our college offers exciting events year-round that enrich your experience.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <img src="images/play2.jpg" class="card-img-top" alt="Campus Image">
                    <div class="card-body">
                        <h5 class="card-title">Beautiful Campus</h5>
                        <p class="card-text">Study in an inspiring environment with state-of-the-art facilities.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <img src="images/school3.jpg" class="card-img-top" alt="Faculty Image">
                    <div class="card-body">
                        <h5 class="card-title">Experienced Faculty</h5>
                        <p class="card-text">Learn from the best educators who are committed to your success.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

                        
    <!-- RECENT NOTICES SECTION -->
    <section id="recent-notices" class="container mt-5">
        <h2 class="section-title text-center">Recent Notices</h2>
        <div class="row mt-4">
            <?php
            // Fetch the latest 4 notices with images
            $notices = $conn->query("SELECT title, attachment FROM notices ORDER BY notice_date DESC LIMIT 4");
            
            // Check if notices exist
            if ($notices->num_rows > 0):
                while ($row = $notices->fetch_assoc()):
                    $imagePath = !empty($row['attachment']) ? "noticepic/" . htmlspecialchars($row['attachment']) : "images/default.jpg";
            ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <img src="<?= $imagePath ?>" class="card-img-top notice-image" alt="Notice Image">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <p class="text-center">No recent notices available.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="mt-5">
        <div class="container">
            <p>&copy; 2024 My College. All rights reserved.</p>
            <p>
                <a href="#">Contact Us</a> | <a href="#">Privacy Policy</a>
            </p>
        </div>
    </footer>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        
        document.getElementById("toggle-password").addEventListener("click", function () {
        const passwordInput = document.getElementById("password");
        const icon = this.querySelector("i");

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    });

        $(document).ready(function() {
            $('#loginForm').on('submit', function(event) {
                event.preventDefault(); // Prevent normal form submission
                var formData = $(this).serialize() + '&ajax=1'; // Serialize form data and add ajax parameter
                
                $.ajax({
                    type: 'POST',
                    url: '', // Submit to the same page
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect; // Redirect to the dashboard
                        } else {
                            $('#errorMessage').removeClass('d-none').text(response.message); // Show error message
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
