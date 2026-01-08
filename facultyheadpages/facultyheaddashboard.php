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
    $logoURL = 'default-logo.png';
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Head Dashhboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        

        /* Center Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex-grow: 1; /* Take remaining space */
        }
    </style>
</head>

<body>

<header class="header">
    <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
    <h2>Faculty Head</h2>
    <a href="../index.php" class="btn btn-danger" id="logout-btn">Log out</a>
</header>   

<div class="d-flex">
    <!-- Sidebar navigation -->
    <nav class="sidebar bg-dark text-white p-3" style="width: 150px;">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex align-items-center" href="facultyheaddashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> 
                    Dashboard
                </a>
            </li>

            <!-- Teachers -->
            <li class="nav-item">
                <a href="facultyheadteachers.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i> Teachers
                </a>
            </li>

            <!-- Students -->
            <li class="nav-item">
                <a href="facultyheadstudents.php" class="nav-link text-white">
                    <i class="fas fa-user-graduate me-2"></i> Students
                </a>
            </li>

            <!-- Exam -->
            <li class="nav-item"> 
                <a href="facultyheadexam.php" class="nav-link text-white">
                    <i class="fas fa-pen me-2"></i> Exam
                </a>
            </li>

            <!-- Marks -->
            <li class="nav-item">
                <a href="facultyheadmarks.php" class="nav-link text-white">
                    <i class="fas fa-pen me-2"></i> Marks
                </a>
            </li>

            <!-- Result -->
            <li class="nav-item">
                <a href="facultyheadresult.php" class="nav-link text-white">
                    <i class="fas fa-chart-line me-2"></i> Result
                </a>
            </li>

        </ul>
    </nav>

    <!-- Main content area -->
    <div class="main-content flex-grow-1 p-2 ms-3">
        <!-- Logo and User Information Section -->
        <div class="d-flex justify-content-center align-items-center p-4 border rounded bg-light mt-4 ms-5">
            <!-- Logo -->
            <div class="me-2">
                <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="College Logo" class="img-fluid rounded-circle" style="width: 300px; height: 300px;">
            </div>
            <!-- User Information -->
            <div>
                <p class="mb-2 text-muted" style="font-size: 1.1rem;"><strong>Name:</strong> <span class="fw-semibold">Welcome <?php echo $_SESSION["user_name"]; ?> !!!</span></p>
                <p class="mb-2 text-muted" style="font-size: 1.1rem;"><strong>Username:</strong> <span class="fw-semibold"><?php echo $_SESSION["user_username"]; ?></span></p>
                <p class="mb-2 text-muted" style="font-size: 1.1rem;"><strong>User Type:</strong> <span class="fw-semibold"><?php echo $_SESSION["user_type"]; ?></span></p>
                <p class="mb-2 text-muted" style="font-size: 1.1rem;"><strong>User Type:</strong> <span class="fw-semibold"><?php echo $_SESSION["faculty_type"]; ?></span></p>
                <p class="mb-0 text-muted" style="font-size: 1.1rem;">
                    <strong>Today's Date:</strong> 
                    <span class="fw-semibold" id="currentDate"></span>
                </p>
            </div>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap JavaScript and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>

  const today = new Date();
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  const formattedDate = today.toLocaleDateString('en-US', options);
  document.getElementById('currentDate').textContent = formattedDate;

</script>
</body>

</html>
