<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: loginS.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "autowheels");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$order_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id'])) {
    $user_id = $_SESSION['user_id'];
    $car_id = intval($_POST['car_id']);
    $address = $_POST['delivery_address'];
    $payment_method = $_POST['payment_method'];
    $card_type = ($payment_method === 'card') ? $_POST['card_type'] : NULL;

    $price_q = $conn->prepare("SELECT Price FROM car WHERE Car_ID = ?");
    $price_q->bind_param("i", $car_id);
    $price_q->execute();
    $price_res = $price_q->get_result();
    $price = $price_res->fetch_assoc()['Price'];

    $stmt = $conn->prepare("INSERT INTO purchase (user_id, car_id, payment_method, card_type, amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissd", $user_id, $car_id, $payment_method, $card_type, $price);

    if ($stmt->execute()) {
        $order_success = "✅ Order placed successfully!";
    } else {
        $order_success = "❌ Failed to place order.";
    }
}

if (!isset($_GET['car_id'])) {
    die("Car ID not provided.");
}
$car_id = intval($_GET['car_id']);

$stmt = $conn->prepare("SELECT * FROM car WHERE Car_ID = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();

if (!$car) {
    die("Car not found.");
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
                    <a href="dashboard.php" class="nav-item nav-link">Cars</a>
                    <a href="profileS.php" class="nav-item nav-link">Profile</a>
                </div>
                <a href="logout.php" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Log Out<i class="fa fa-arrow-right ms-3"></i></a>
            </div>
    </nav>
    <!-- Navbar End -->
<div class="container-xxl py-5 bg-dark page-header mb-5">
    <div class="container my-5 pt-5 pb-4">
        <h1 class="display-3 text-white mb-3"><?= htmlspecialchars($car['Model']) ?> Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb text-uppercase">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page"><?= htmlspecialchars($car['Model']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div id="carCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active"><img src="<?= $car['Image1'] ?>" class="d-block w-100 slider-img" alt="Image 1"></div>
                    <div class="carousel-item"><img src="<?= $car['Image2'] ?>" class="d-block w-100 slider-img" alt="Image 2"></div>
                    <div class="carousel-item"><img src="<?= $car['Image3'] ?>" class="d-block w-100 slider-img" alt="Image 3"></div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        </div>

        <div class="col-lg-6">
            <h3><?= htmlspecialchars($car['Model']) ?> - <?= htmlspecialchars($car['Brand']) ?></h3>
            <p><strong>Price:</strong> $<?= number_format($car['Price'], 2) ?></p>
            <p><strong>Color:</strong> <?= htmlspecialchars($car['Color']) ?></p>
            <p><strong>Wheel Type:</strong> <?= htmlspecialchars($car['Wheel_Type']) ?></p>
            <p><strong>Light Option:</strong> <?= htmlspecialchars($car['Light_Option']) ?></p>

            <h5 class="mt-4">Place Your Order</h5>
            <?php if ($order_success): ?>
                <div class="alert alert-info"><?= $order_success ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <input type="hidden" name="car_id" value="<?= $car['Car_ID'] ?>">
                <div class="mb-3">
                    <label class="form-label">Delivery Address</label>
                    <textarea name="delivery_address" class="form-control" rows="2" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="mb-3 d-none" id="card_type_wrapper">
                    <label class="form-label">Card Type</label>
                    <select name="card_type" id="card_type" class="form-control">
                        <option value="Visa">Visa</option>
                        <option value="MasterCard">MasterCard</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Confirm Order</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById("payment_method").addEventListener("change", function () {
        document.getElementById("card_type_wrapper").classList.toggle("d-none", this.value !== "card");
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
