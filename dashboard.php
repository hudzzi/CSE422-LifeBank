<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginS.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Logged-in student ID

// Database connection
$conn = new mysqli("localhost", "root", "", "lifebank");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -------------------------------
// Handle "Interested" form submission
// -------------------------------
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interested_request_id'])) {
    $request_id = (int)$_POST['interested_request_id'];

    // Check ownership: you cannot be interested in your own request
    $owner_check = $conn->prepare("SELECT requested_by FROM blood_requests WHERE request_id = ?");
    $owner_check->bind_param("i", $request_id);
    $owner_check->execute();
    $owner_result = $owner_check->get_result();
    $owner_row = $owner_result->fetch_assoc();

    if ($owner_row && $owner_row['requested_by'] != $user_id) {
        // Insert interest, ignore duplicates
        $insert_stmt = $conn->prepare("INSERT IGNORE INTO interested_donors (request_id, student_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $request_id, $user_id);
        if ($insert_stmt->execute()) {
            $message = "You have expressed interest in donating!";
        } else {
            $message = "Failed to register your interest. Try again.";
        }
    } else {
        $message = "You cannot express interest in your own request.";
    }

    // Reload page to avoid resubmission
    header("Location: dashboard.php?message=" . urlencode($message));
    exit();
}

// -------------------------------
// Fetch all blood requests except those made by the logged-in student
// -------------------------------
$sql = "
    SELECT br.request_id, br.blood_group_needed, br.units_needed, br.location, br.contact_number, br.urgency_level, br.fulfilled,
           s.name AS requester_name, s.blood_group AS requester_blood_group
    FROM blood_requests br
    JOIN students s ON br.requested_by = s.student_id
    WHERE br.requested_by != ?
    ORDER BY FIELD(br.urgency_level, 'High','Medium','Low'), br.request_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// -------------------------------
// Fetch requests the logged-in student has already expressed interest in
// -------------------------------
$interest_sql = "SELECT request_id FROM interested_donors WHERE student_id = ?";
$interest_stmt = $conn->prepare($interest_sql);
$interest_stmt->bind_param("i", $user_id);
$interest_stmt->execute();
$interest_result = $interest_stmt->get_result();

$interested_requests = [];
while ($row = $interest_result->fetch_assoc()) {
    $interested_requests[] = $row['request_id'];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Blood Requests Dashboard</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container-xxl bg-white p-0">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="index.html" class="navbar-brand d-flex align-items-center text-center py-0 px-4 px-lg-5">
            <h1 class="m-0 text-primary">Lifebank</h1>
        </a>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="dashboard.php" class="nav-item nav-link active">Dashboard</a>
                <a href="profileS.php" class="nav-item nav-link">Profile</a>
                <a href="bRequest.php" class="nav-item nav-link">Request for Blood</a>
            </div>
            <a href="logout.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Log Out</a>
        </div>
    </nav>

    <!-- Header -->
    <div class="container-xxl py-5 bg-dark page-header mb-5">
        <div class="container my-5 pt-5 pb-4">
            <h1 class="display-3 text-white mb-3">All Blood Requests</h1>
        </div>
    </div>

    <!-- Display Success Message -->
    <?php if (!empty($_GET['message'])): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>

    <!-- Blood Requests Table -->
    <div class="container py-5">
        <?php if ($result->num_rows > 0): ?>
            <div class="row g-4">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body">
                                <h5 class="text-primary">Request by: <?= htmlspecialchars($row['requester_name']) ?> (<?= htmlspecialchars($row['requester_blood_group']) ?>)</h5>
                                <p><strong>Blood Group Needed:</strong> <?= htmlspecialchars($row['blood_group_needed']) ?></p>
                                <p><strong>Units Needed:</strong> <?= htmlspecialchars($row['units_needed']) ?></p>
                                <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                                <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact_number']) ?></p>
                                <p><strong>Urgency:</strong> <?= htmlspecialchars($row['urgency_level']) ?></p>
                                <p><strong>Fulfilled:</strong> <?= $row['fulfilled'] == 1 ? 'Yes' : 'No' ?></p>

                                <!-- Interested Button -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="interested_request_id" value="<?= $row['request_id'] ?>">
                                    <button type="submit" class="btn btn-success mt-2"
                                        <?= in_array($row['request_id'], $interested_requests) ? "disabled" : "" ?>>
                                        <?= in_array($row['request_id'], $interested_requests) ? "Already Interested" : "Interested" ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No blood requests available at the moment.</div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 bg-dark text-white-50">
        <small>&copy; 2025 Lifebank. All Rights Reserved.</small>
    </footer>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
