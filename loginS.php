<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "lifebank");  // Ensure you're using the correct database name
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Feedback messages
$error = "";
$success = "";

// Handle Sign Up (Student Registration)
if (isset($_POST['signup'])) {
    // Get all form inputs
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $university_id = $_POST['university_id'] ?? '';
    $dob = $_POST['dob'] ?? '';  // Date of birth
    $blood_group = $_POST['blood_group'] ?? '';
    $gender = $_POST['gender'] ?? 'Other';  // Default to 'Other'
    $profile_privacy = isset($_POST['profile_privacy']) ? 1 : 0;  // 1 if checked, 0 if unchecked

    // Check if email already exists in the users table
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $password);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;  // Get the user_id after inserting into users table

            // Insert student data into the students table
            $student_stmt = $conn->prepare("INSERT INTO students (user_id, name, university_id, phone_number, date_of_birth, blood_group, gender, address, profile_privacy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $student_stmt->bind_param("isssssssi", $user_id, $name, $university_id, $phone, $dob, $blood_group, $gender, $address, $profile_privacy);
            if ($student_stmt->execute()) {
                $success = "Registration successful. Please log in.";
            } else {
                $error = "Failed to add student data. Try again.";
            }
        } else {
            $error = "Registration failed. Try again.";
        }
    }
}

// Handle Sign In (User Login)
if (isset($_POST['signin'])) {
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        // User found and password verified
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];

        // Optionally, you can fetch additional student info if needed
        $student_stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
        $student_stmt->bind_param("i", $user['user_id']);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        $student = $student_result->fetch_assoc();

        // Store additional student information in the session
        $_SESSION['student_name'] = $student['name'];
        $_SESSION['student_phone'] = $student['phone_number'];
        $_SESSION['student_address'] = $student['address'];

        header("Location: profileS.php");  // Redirect to the dashboard after login
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In / Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" id="container">
        <!-- Sign Up Form -->
        <div class="form-container sign-up-container">
            <form method="POST" action="">
                <h1>User Sign Up</h1>
                <span>Use your email to register</span>

                <input type="text" name="name" placeholder="Full Name" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <input type="text" name="university_id" placeholder="University ID" required />
                <input type="date" name="dob" placeholder="Date of Birth" required />
                <input type="text" name="blood_group" placeholder="Blood Group" required />
                
                <select name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>

                <input type="text" name="address" placeholder="Address" required />
                <input type="tel" name="phone" placeholder="Phone Number" required />
                
                <label>
                    <input type="checkbox" name="profile_privacy" checked />
                    Keep profile private
                </label>

                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in-container">
            <form method="POST" action="">
                <h1>User Sign In</h1>
                <span>or use your account</span>

                <input type="email" name="login_email" placeholder="Email" required />
                <input type="password" name="login_password" placeholder="Password" required />
                <a href="#">Forgot your password?</a>
                <button type="submit" name="signin">Sign In</button>
            </form>
        </div>

        <!-- Overlay for switching between Sign-In and Sign-Up forms -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });
    </script>
</body>
</html>

