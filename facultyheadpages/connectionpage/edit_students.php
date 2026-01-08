<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cims";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sid = $_POST["sid"];
    $name = trim($_POST["name"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $email = trim($_POST["email"]);
    $dob = trim($_POST["dob"]);
    $phoneno = trim($_POST["phoneno"]);
    $address = trim($_POST["address"]);
    $gender = trim($_POST["gender"]);
    $parentsname = trim($_POST["parentsname"]);
    $parentscontact = trim($_POST["parentscontact"]);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit();
    }

    // Validate phone number (digits only)
    if (!preg_match('/^\d{10}$/', $phoneno)) {
        echo json_encode(["status" => "error", "message" => "Invalid phone number. Must be 10 digits."]);
        exit();
    }

    // Validate date of birth (must be at least 16 years old)
    $dobTimestamp = strtotime($dob);
    $age = (int)((time() - $dobTimestamp) / (365.25 * 24 * 60 * 60));
    if ($age < 16) {
        echo json_encode(["status" => "error", "message" => "Student must be at least 16 years old"]);
        exit();
    }

    // Handle photo update if a new file is uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoName = basename($_FILES['photo']['name']);
        $photoTmp = $_FILES['photo']['tmp_name'];
        $photoFolder = "../../studentpic/";

        // Ensure the directory exists and is writable
        if (!is_dir($photoFolder)) {
            echo json_encode(["status" => "error", "message" => "Photo directory does not exist"]);
            exit();
        }

        // Validate file type (only allow images)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($photoTmp);
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(["status" => "error", "message" => "Invalid photo format. Only JPG and PNG allowed."]);
            exit();
        }

        // Construct the full path for the photo
        $photoPath = $photoFolder . $photoName;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($photoTmp, $photoPath)) {
            // Update query including photo
            $sql = "UPDATE studentlog SET name=?, username=?, password=?, email=?, dob=?, phoneno=?, address=?, gender=?, parentsname=?, parentscontact=?, photo=? WHERE sid=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
                exit();
            }
            $stmt->bind_param("sssssssssssi", $name, $username, $password, $email, $dob, $phoneno, $address, $gender, $parentsname, $parentscontact, $photoName, $sid);
        } else {
            echo json_encode(["status" => "error", "message" => "Error uploading photo"]);
            exit();
        }
    } else {
        // Update query without changing the photo
        $sql = "UPDATE studentlog SET name=?, username=?, password=?, email=?, dob=?, phoneno=?, address=?, gender=?, parentsname=?, parentscontact=? WHERE sid=?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
            exit();
        }
        $stmt->bind_param("ssssssssssi", $name, $username, $password, $email, $dob, $phoneno, $address, $gender, $parentsname, $parentscontact, $sid);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Student updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Update failed: " . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>