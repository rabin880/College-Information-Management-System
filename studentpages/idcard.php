<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['user_id'])) {
    die("No student is logged in.");
}

$student_id = $_SESSION['user_id'];

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

try {
    // Prepare SQL statement with parameterized query to fetch student details
    $sql = "SELECT 
                sid, 
                name, 
                email, 
                dob, 
                address, 
                gender, 
                photo,
                CAST(s.batch AS CHAR) as batch,
                c.classname AS classname
            FROM studentlog s
            LEFT JOIN class c ON s.classid = c.cid
            WHERE sid = ?";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    // Bind the current student's ID to the query
    $stmt->bind_param("s", $student_id);
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch student data
    if ($student = $result->fetch_assoc()) {
        // Data retrieved successfully
        $student_id = htmlspecialchars($student['sid']);
        $full_name = htmlspecialchars($student['name']);
        $email = htmlspecialchars($student['email']);
        $date_of_birth = htmlspecialchars($student['dob']);
        $address = htmlspecialchars($student['address']);
        $gender = htmlspecialchars($student['gender']);
        $profile_image = htmlspecialchars($student['photo'] ?? 'default_profile.png');
        $batch = htmlspecialchars($student['batch']);
        $classname = htmlspecialchars($student['classname']);
        
        // Construct the full path to the profile picture
        $profile_image_path = '../studentpic/' . $profile_image;
    } else {
        // No student data found
        throw new Exception("Student data not found.");
    }
} catch (Exception $e) {
    // Handle any errors
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="common.css">
    
    <style>
        .main-content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 70px);
            padding: 20px 30px;
        }
        
        .id-card {
            width: 380px;
            border: 2px solid #007bff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            background-color: #fff;
            background: linear-gradient(to bottom right, #f0f7ff, #d1e7ff);
        }
        
        .id-card-header {
            background-color: #0056b3;
            color: white;
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .student-photo {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #0056b3;
            display: block;
            margin: 0 auto 15px;
        }

        .id-card-details {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .id-card-details .row {
            margin-bottom: 8px;
        }

        .id-card-details .col-5 {
            font-weight: bold;
            color: #333;
            text-align: right;
            padding-right: 10px;
        }

        .id-card-details .col-7 {
            color: #555;
        }

        .id-card-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }


@media print {
    @page {
        size: A4;
        margin: 0;
    }

    body * {
        visibility: hidden;
    }

    .id-card, .id-card * {
        visibility: visible;
    }

    .id-card {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 380px;
        border: 2px solid #007bff !important;
        border-radius: 15px !important;
        padding: 20px;
        background: #fff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
    }
    
    .id-card-header {
        background-color: #0056b3 !important;
        color: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .student-photo {
        border: 4px solid #0056b3 !important;
        background-color: #0056b3 !important;
        color: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .id-card-details .row {
        page-break-inside: avoid;
        margin-bottom: 8px !important;
    }

    .id-card-details .col-5 {
        color: #333 !important;
        font-weight: bold !important;
    }

    .id-card-details .col-7 {
        color: #555 !important;
    }

    .id-card-footer {
        page-break-inside: avoid;
        margin-top: 20px !important;
        color: #777 !important;
        font-family: Arial, sans-serif !important;
    }

    /* Hide unnecessary elements */
    .header, 
    .sidebar, 
    #logout-btn, 
    .btn,
    .main-content > *:not(.id-card) {
        display: none !important;
    }
    
    /* Ensure colors and backgrounds print properly */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Improve text clarity */
    .id-card-details {
        font-family: Arial, sans-serif !important;
        font-size: 12pt !important;
        line-height: 1.4 !important;
    }
    
    /* Add subtle watermark effect */
    .id-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 40%, rgba(0,86,179,0.05) 50%, transparent 60%);
        pointer-events: none;
        z-index: -1;
    }
    
    /* Ensure proper margins around content */
    .id-card > * {
        margin-bottom: 10px !important;
    }
        .student-photo {
        width: 130px;
        height: 130px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #0056b3;
        display: block;
        margin: 0 auto 15px;
    }
}



    </style>
</head>

<body>
    <header class="header">
        <img src="<?php echo htmlspecialchars($logoURL); ?>" alt="My College Logo" style="height: 50px;">
        <h2 class="m-0">Student Dashboard</h2>
        <a href="logout.php" class="btn btn-danger" id="logout-btn">Log out</a>
    </header>   

    <div class="d-flex">
      <!-- Sidebar navigation -->
    <nav class="sidebar bg-dark text-white p-3" style="width: 150px;">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link text-white d-flex align-items-center" href="studentdashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> 
                    Dashboard
                </a>
            </li>

            
         

            <!-- Result -->
            <li class="nav-item">
                <a href="studentresult.php" class="nav-link text-white">
                    <i class="fas fa-chart-line me-2"></i> Result
                </a>
            </li>

            

            <!-- ID Card -->
            <li class="nav-item">
                <a href="idcard.php" class="nav-link text-white">
                    <i class="fas fa-id-card me-2"></i> ID Card
                </a>
            </li>
        </ul>
    </nav>

        <!-- Main Content Area -->
        <div class="main-content flex-grow-1">
        <div class="id-card">
            <div class="id-card-header">
                <h4 class="m-0">Student ID Card</h4>
            </div>
            
            <!-- Student Photo -->
            <img src="<?php echo $profile_image_path; ?>" alt="Student Photo" class="student-photo">
            
            <div class="id-card-details">
                <div class="row">
                    <div class="col-5">Name:</div>
                    <div class="col-7"><?php echo $full_name; ?></div>
                </div>

                <div class="row">
                    <div class="col-5"> ID:</div>
                    <div class="col-7"><?php echo $student_id; ?></div>
                </div>

                <div class="row">
                    <div class="col-5"> Class:</div>
                    <div class="col-7"><?php echo $classname; ?></div>
                </div>

                <div class="row">
                    <div class="col-5"> Batch:</div>
                    <div class="col-7"><?php echo $batch; ?></div>
                </div>
                
                <div class="row">
                    <div class="col-5">Email:</div>
                    <div class="col-7"><?php echo $email; ?></div>
                </div>
                <div class="row">
                    <div class="col-5">Date of Birth:</div>
                    <div class="col-7"><?php echo $date_of_birth; ?></div>
                </div>
                <div class="row">
                    <div class="col-5">Gender:</div>
                    <div class="col-7"><?php echo $gender; ?></div>
                </div>
                <div class="row">
                    <div class="col-5">Address:</div>
                    <div class="col-7"><?php echo $address; ?></div>
                </div>
            </div>

            <div class="id-card-footer">
                <p>Issued by My College</p>
            </div>
            
            <div class="text-center mt-3">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Print ID Card
                </button>
            </div>
        </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
