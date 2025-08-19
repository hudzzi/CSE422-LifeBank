<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginS.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "lifebank");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Handle form submission for blood request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood_group_needed = $_POST['blood_group_needed'];
    $units_needed = $_POST['units_needed'];
    $location = $_POST['location'];
    $contact_number = $_POST['contact_number'];
    $urgency_level = $_POST['urgency_level'];

    // Insert the blood request into the database
    $stmt = $conn->prepare("INSERT INTO blood_requests (requested_by, blood_group_needed, units_needed, location, contact_number, urgency_level) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $blood_group_needed, $units_needed, $location, $contact_number, $urgency_level);
    
    if ($stmt->execute()) {
        $success_message = "Blood request posted successfully!";
    } else {
        $error_message = "Failed to post the request. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Post Blood Request</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container-xxl bg-white p-0">
        <!-- Navbar Start -->
        <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
            <a href="index.html" class="navbar-brand d-flex align-items-center text-center py-0 px-4 px-lg-5">
                <h1 class="m-0 text-primary">Lifebank</h1>
            </a>
            <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto p-4 p-lg-0">
                    <a href="dashboard.php" class="nav-item nav-link">Dashboard</a>
                    <a href="profileS.php" class="nav-item nav-link ">Profile</a>
                    <a href="bRequest.php" class="nav-item nav-link active">Request for blood</a>
                </div>
                <a href="logout.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Log Out<i class="fa fa-arrow-right ms-3"></i></a>
            </div>
        </nav>
        <!-- Navbar End -->

        <div class="container py-5">
            <h2 class="mb-4">Post a Blood Request</h2>

            <!-- Display Success or Error Message -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php elseif (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <!-- Blood Request Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="blood_group_needed" class="form-label">Blood Group Needed</label>
                    <input type="text" class="form-control" id="blood_group_needed" name="blood_group_needed" required>
                </div>

                <div class="mb-3">
                    <label for="units_needed" class="form-label">Units Needed</label>
                    <input type="number" class="form-control" id="units_needed" name="units_needed" required min="1">
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" required>
                </div>

                <div class="mb-3">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" required>
                </div>

                <div class="mb-3">
                    <label for="urgency_level" class="form-label">Urgency Level</label>
                    <select class="form-select" id="urgency_level" name="urgency_level" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Submit Request</button>
            </form>
        </div>

        <!-- Footer Start -->
        <footer class="text-center py-4 bg-dark text-white-50">
            <small>&copy; 2025 Lifebank. All Rights Reserved.</small>
        </footer>
        <!-- Footer End -->
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
