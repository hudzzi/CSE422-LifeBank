<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginR.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "autowheels");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $model = $_POST['model'];
    $brand = $_POST['brand'];
    $price = floatval($_POST['price']);
    $color = $_POST['color'];
    $wheel = $_POST['wheel_type'];
    $light = $_POST['light_option'];

    // File upload handling
    $uploadDir = "uploads/";
    $img1 = $uploadDir . basename($_FILES["image1"]["name"]);
    $img2 = $uploadDir . basename($_FILES["image2"]["name"]);
    $img3 = $uploadDir . basename($_FILES["image3"]["name"]);

    if (
        move_uploaded_file($_FILES["image1"]["tmp_name"], $img1) &&
        move_uploaded_file($_FILES["image2"]["tmp_name"], $img2) &&
        move_uploaded_file($_FILES["image3"]["tmp_name"], $img3)
    ) {
        $stmt = $conn->prepare("INSERT INTO car (Model, Brand, Price, Color, Wheel_Type, Light_Option, Image1, Image2, Image3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssssss", $model, $brand, $price, $color, $wheel, $light, $img1, $img2, $img3);

        if ($stmt->execute()) {
            $success = "✅ Car added successfully!";
        } else {
            $error = "❌ Failed to add car.";
        }
    } else {
        $error = "❌ Failed to upload one or more images.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>JobEntry - Job Portal Website Template</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container-xxl bg-white p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Navbar Start -->
        <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
            <a href="index.html" class="navbar-brand d-flex align-items-center text-center py-0 px-4 px-lg-5">
                <h1 class="m-0 text-primary">JobEntry</h1>
            </a>
            <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto p-4 p-lg-0">
                    <a href="admin.php" class="nav-item nav-link active">Add Car</a>
                    <a href="status.php" class="nav-item nav-link">Check Status</a>
                </div>
                <a href="logout.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Log Out<i class="fa fa-arrow-right ms-3"></i></a>
            </div>
        </nav>
        <!-- Navbar End -->

        <!-- Header Start -->
        <div class="container-xxl py-5 bg-dark page-header mb-5">
            <div class="container my-5 pt-5 pb-4">
                <h1 class="display-3 text-white mb-3 animated slideInDown">Add a New Car</h1>
            </div>
        </div>
        <!-- Header End -->

        <!-- Form Section -->
        <div class="container mb-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="bg-light p-5 rounded shadow">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" class="form-control" name="model" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" class="form-control" name="brand" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price (USD)</label>
                                <input type="number" step="0.01" class="form-control" name="price" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Wheel Type</label>
                                <input type="text" class="form-control" name="wheel_type" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Light Option</label>
                                <input type="text" class="form-control" name="light_option" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image 1</label>
                                <input type="file" class="form-control" name="image1" accept="image/*" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image 2</label>
                                <input type="file" class="form-control" name="image2" accept="image/*" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image 3</label>
                                <input type="file" class="form-control" name="image3" accept="image/*" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Add Car</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center py-4 bg-dark text-white-50">
            <small>&copy; 2025 JobEntry. All Rights Reserved.</small>
        </footer>
    </div>

    <script>
        // Hide spinner after load
        window.addEventListener('load', function () {
            document.getElementById('spinner').classList.remove('show');
        });
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
