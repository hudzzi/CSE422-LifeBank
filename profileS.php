<?php
// Start session and check login status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: loginS.php");  // Redirect if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User'; // If the name isn't set, fallback to 'User'

// Database connection
$conn = new mysqli("localhost", "root", "", "lifebank");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details from the users table
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch student details from the students table
$stmt2 = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$student_result = $stmt2->get_result();
$student = $student_result->fetch_assoc();

// Fetch appointments for the user
$stmt3 = $conn->prepare("SELECT * FROM appointments WHERE student_id = ? ORDER BY appointment_date DESC");
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$appointments_result = $stmt3->get_result();

// Fetch blood requests made by the user
$stmt4 = $conn->prepare("SELECT * FROM blood_requests WHERE requested_by = ? ORDER BY request_time DESC");
$stmt4->bind_param("i", $user_id);
$stmt4->execute();
$blood_requests_result = $stmt4->get_result();

// Fetch donation history for the user
$stmt5 = $conn->prepare("SELECT * FROM donation_history WHERE student_id = ? ORDER BY donation_date DESC");
$stmt5->bind_param("i", $user_id);
$stmt5->execute();
$donation_history_result = $stmt5->get_result();

// Fetch eligibility for blood donation
$stmt6 = $conn->prepare("SELECT * FROM eligibility WHERE student_id = ?");
$stmt6->bind_param("i", $user_id);
$stmt6->execute();
$eligibility_result = $stmt6->get_result();
$eligibility = $eligibility_result->fetch_assoc();

// Fetch the latest donation history
$stmt = $conn->prepare("SELECT * FROM donation_history WHERE student_id = ? ORDER BY donation_date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$donation_result = $stmt->get_result();

// Check if donation history exists for the user
if ($donation_result->num_rows > 0) {
    $donation = $donation_result->fetch_assoc();  // Fetch the latest donation record
} else {
    $donation = null;  // If no donation history, set $donation to null
}



// Handle donation history update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_donation'])) {
    $donation_date = $_POST['donation_date'];
    $location = $_POST['location'];

    // Calculate the next eligible date (56 days after the donation date)
    $next_eligible_date = date('Y-m-d', strtotime($donation_date . ' +56 days'));

    // Update the donation history in the database
    $update_stmt = $conn->prepare("UPDATE donation_history SET donation_date = ?, location = ?, next_eligible_date = ? WHERE donation_id = ?");
    $update_stmt->bind_param("sssi", $donation_date, $location, $next_eligible_date, $donation['donation_id']);
    
    if ($update_stmt->execute()) {
        $success_message = "Donation details updated successfully!";
    } else {
        $error_message = "Failed to update donation details. Please try again.";
    }
}

// Fetch interested donors for each of the user's blood requests
$donors_per_request = [];

// Loop through the blood requests made by this user
$blood_requests_result->data_seek(0); // Reset pointer in case it's used
while ($request = $blood_requests_result->fetch_assoc()) {
    $request_id = $request['request_id'];

    $stmt_donors = $conn->prepare("
        SELECT s.student_id, s.name, s.blood_group, s.phone_number, i.interest_date
        FROM interested_donors i
        JOIN students s ON i.student_id = s.student_id
        WHERE i.request_id = ?
        ORDER BY i.interest_date DESC
    ");

    $stmt_donors->bind_param("i", $request_id);
    $stmt_donors->execute();
    $donors_result = $stmt_donors->get_result();

    $donors_per_request[$request_id] = [];
    while ($donor = $donors_result->fetch_assoc()) {
        $donors_per_request[$request_id][] = $donor;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $donor_id = (int)$_POST['donor_id'];
    $owner_id = (int)$_POST['request_owner_id'];
    $appointment_date = $_POST['appointment_date'];
    $location = $_POST['location'];

    $stmt = $conn->prepare("INSERT INTO appointments (student_id, request_owner_id, appointment_date, location) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $donor_id, $owner_id, $appointment_date, $location);
    
    if ($stmt->execute()) {
        $success_message = "Appointment booked successfully!";
    } else {
        $error_message = "Failed to book appointment. Try again.";
    }

    // Redirect to refresh page
    header("Location: profileS.php?message=" . urlencode($success_message ?? $error_message));
    exit();
}

// Fetch all existing appointments between interested donors and request owners
$appointments_map = [];

$app_sql = "SELECT student_id, request_owner_id, appointment_date, location 
            FROM appointments";
$app_result = $conn->query($app_sql);

while ($app = $app_result->fetch_assoc()) {
    // Use combination of donor_id + owner_id as key
    $key = $app['student_id'] . '_' . $app['request_owner_id'];
    $appointments_map[$key] = $app;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>User Profile - Blood Donation</title>
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
                    <a href="profileS.php" class="nav-item nav-link active">Profile</a>
                    <a href="bRequest.php" class="nav-item nav-link">Request for blood</a>
                </div>
                <a href="logout.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Log Out<i class="fa fa-arrow-right ms-3"></i></a>
            </div>
        </nav>
        <!-- Navbar End -->

        <!-- Profile Section Start -->
        <div class="container py-5">
            <h2 class="mb-4">User Profile</h2>
            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($student['phone_number']) ?></p>
                    <p><strong>University ID:</strong> <?= htmlspecialchars($student['university_id']) ?></p>
                    <p><strong>Date of Birth:</strong> <?= htmlspecialchars($student['date_of_birth']) ?></p>
                    <p><strong>Blood Group:</strong> <?= htmlspecialchars($student['blood_group']) ?></p>
                    <p><strong>Gender:</strong> <?= htmlspecialchars($student['gender']) ?></p>
                    <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($student['address'])) ?></p>
                    <p><strong>Profile Privacy:</strong> <?= $student['profile_privacy'] == 1 ? 'Public' : 'Private' ?></p>
                </div>
            </div>

            <h4 class="mb-3">My Appointments</h4>
            <?php if ($appointments_result->num_rows > 0): ?>
                <?php while ($row = $appointments_result->fetch_assoc()): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-primary">Appointment on <?= date("d M, Y h:i A", strtotime($row['appointment_date'])) ?></h5>
                            <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                            <p class="mb-1"><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">You have no upcoming appointments.</div>
            <?php endif; ?>

            <h4 class="mb-3">My Blood Requests</h4>
            <?php if ($blood_requests_result->num_rows > 0): ?>
                <?php $blood_requests_result->data_seek(0); ?>
                <?php while ($row = $blood_requests_result->fetch_assoc()): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-primary">Blood Request for <?= htmlspecialchars($row['blood_group_needed']) ?> Blood</h5>
                            <p class="mb-1"><strong>Units Needed:</strong> <?= htmlspecialchars($row['units_needed']) ?></p>
                            <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                            <p class="mb-1"><strong>Status:</strong> <?= htmlspecialchars($row['urgency_level']) ?></p>

                            <!-- Interested Donors -->
                            <h6 class="mt-3">Students Interested to Donate:</h6>
                            <?php if (!empty($donors_per_request[$row['request_id']])): ?>
                                <ul class="list-group">
                                    <?php foreach ($donors_per_request[$row['request_id']] as $donor): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($donor['name']) ?></strong> 
                                            (<?= htmlspecialchars($donor['blood_group']) ?>) - Phone: <?= htmlspecialchars($donor['phone_number']) ?>
                                            <br><small class="text-muted">Interested on <?= date("d M, Y h:i A", strtotime($donor['interest_date'])) ?></small>

                                            <?php
                                                // Key to check if appointment exists between this donor and the request owner
                                                $key = $donor['student_id'] . '_' . $row['requested_by'];
                                                if (isset($appointments_map[$key])):
                                                    $appointment = $appointments_map[$key];
                                            ?>
                                                <!-- Show appointment details if exists -->
                                                <div class="mt-2 p-2 border rounded bg-light">
                                                    <strong>Appointment Booked!</strong><br>
                                                    Date & Time: <?= date("d M, Y h:i A", strtotime($appointment['appointment_date'])) ?><br>
                                                    Location: <?= htmlspecialchars($appointment['location']) ?>
                                                </div>
                                            <?php else: ?>
                                                <!-- Show inline booking form if no appointment exists -->
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="donor_id" value="<?= $donor['student_id'] ?>">
                                                    <input type="hidden" name="request_owner_id" value="<?= $row['requested_by'] ?>">

                                                    <div class="mb-2">
                                                        <label>Date & Time:</label>
                                                        <input type="datetime-local" name="appointment_date" required class="form-control form-control-sm">
                                                    </div>

                                                    <div class="mb-2">
                                                        <label>Location:</label>
                                                        <input type="text" name="location" required class="form-control form-control-sm">
                                                    </div>

                                                    <button type="submit" name="book_appointment" class="btn btn-success btn-sm">Book Appointment</button>
                                                </form>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No students have expressed interest yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">You haven't made any blood requests yet.</div>
            <?php endif; ?>


            <h4 class="mb-3">My Donation History</h4>
            <?php if ($donation_history_result->num_rows > 0): ?>
                <?php while ($row = $donation_history_result->fetch_assoc()): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-primary">Donation on <?= date("d M, Y", strtotime($row['donation_date'])) ?></h5>
                            <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                            <p class="mb-1"><strong>Next Eligible Date:</strong> <?= htmlspecialchars($row['next_eligible_date']) ?></p>
                            <p class="mb-1"><strong>Notes:</strong> <?= htmlspecialchars($row['notes']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">You haven't donated blood yet.</div>
            <?php endif; ?>

            <h4 class="mb-3">Eligibility for Donation</h4>
            <?php if ($eligibility): ?>
                <p><strong>Last Donation Date:</strong> <?= htmlspecialchars($eligibility['last_donation_date']) ?></p>
                <p><strong>Status:</strong> <?= $eligibility['is_eligible'] == 1 ? 'Eligible' : 'Not Eligible' ?></p>
            <?php else: ?>
                <div class="alert alert-info">You have not yet been assessed for eligibility.</div>
            <?php endif; ?>
        <!-- Profile Section End -->
        <h4 class="mb-3">Update My Donation History</h4>
            <?php if ($donation): ?>
                <form method="POST" action="">
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-primary">Current Donation on <?= date("d M, Y", strtotime($donation['donation_date'])) ?></h5>
                            <p><strong>Location:</strong> <?= htmlspecialchars($donation['location']) ?></p>

                            <!-- Donation Date Field (can be updated) -->
                            <div class="mb-3">
                                <label for="donation_date" class="form-label">Update Donation Date</label>
                                <input type="date" class="form-control" id="donation_date" name="donation_date" value="<?= $donation ? $donation['donation_date'] : '' ?>" required>
                            </div>

                            <!-- Location Field (can be updated) -->
                            <div class="mb-3">
                                <label for="location" class="form-label">Update Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?= $donation ? htmlspecialchars($donation['location']) : '' ?>" required>
                            </div>

                            <button type="submit" name="update_donation" class="btn btn-primary">Update Donation</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info">You haven't donated blood yet.</div>
            <?php endif; ?>
            <!-- Display Success or Error Message -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php elseif (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            </div>

            <!-- Your other profile content ends here -->

        <!-- Footer Start -->
        <footer class="text-center py-4 bg-dark text-white-50">
            <small>&copy; 2025 Lifebank. All Rights Reserved.</small>
        </footer>
        <!-- Footer End -->
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
