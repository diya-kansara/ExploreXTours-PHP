<?php
include 'includes/db.php';  // Your DB connection
include 'includes/header.php';  // Your header/navbar

// 🔐 REDIRECT IF USER NOT LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: /Online-Tours-and-Travels-Website/user/login.php?redirect=book&package_id=" . ($_GET['package_id'] ?? ''));
    exit();
}

// Get package ID from URL
if (isset($_GET['package_id']) && is_numeric($_GET['package_id'])) {
    $package_id = intval($_GET['package_id']);

    // Fetch package details for display
    $sql = "SELECT * FROM packages WHERE id = $package_id";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $package = mysqli_fetch_assoc($result);
    } else {
        echo "<div class='alert alert-danger'>Package not found.</div>";
        include 'includes/footer.php';
        exit();
    }
} else {
    echo "<div class='alert alert-danger'>Invalid package ID.</div>";
    include 'includes/footer.php';
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $guests = intval($_POST['guests']);
    $travel_dates = trim($_POST['travel_dates']);
    $special_requests = trim($_POST['special_requests']);

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || $guests < 1 || empty($travel_dates)) {
        $error = "Please fill all required fields correctly.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        // ✨ DATE VALIDATION (NO PAST START DATE ALLOWED)
        $date_parts = explode("to", $travel_dates);
        $start_date = trim($date_parts[0]);

        $start_timestamp = strtotime($start_date);
        $today_timestamp = strtotime(date("Y-m-d"));

        if ($start_timestamp < $today_timestamp) {
            $error = "Travel date cannot be in the past. Please choose a valid date.";
        } else {

            // Insert booking into DB using prepared statement
            $stmt = $conn->prepare("INSERT INTO bookings (package_id, name, email, phone, guests, travel_dates, special_requests, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssiss", $package_id, $name, $email, $phone, $guests, $travel_dates, $special_requests);

            if ($stmt->execute()) {
                $success = "Booking successful! We will contact you soon.";
                // Clear form fields after successful submission
                $name = $email = $phone = $travel_dates = $special_requests = '';
                $guests = 1;
            } else {
                $error = "Error saving booking. Please try again.";
            }

            $stmt->close();
        }
    }
}
?>

<div class="container mt-5">
    <h2>Book Package: <?php echo htmlspecialchars($package['title']); ?></h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="package_id" value="<?php echo $package_id; ?>" />

        <div class="mb-3">
            <label for="name" class="form-label">Name*</label>
            <input type="text" class="form-control" id="name" name="name" required
                value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email*</label>
            <input type="email" class="form-control" id="email" name="email" required
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Phone*</label>
            <input type="tel" class="form-control" id="phone" name="phone" required
                value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
        </div>

        <div class="mb-3">
            <label for="guests" class="form-label">Number of Guests*</label>
            <input type="number" class="form-control" id="guests" name="guests" min="1" required
                value="<?php echo isset($guests) ? htmlspecialchars($guests) : '1'; ?>">
        </div>

        <div class="mb-3">
            <label for="travel_dates" class="form-label">Travel Dates*</label>
            <input type="text" class="form-control" id="travel_dates" name="travel_dates" required
                value="<?php echo isset($travel_dates) ? htmlspecialchars($travel_dates) : ''; ?>" placeholder="e.g., 2025-12-01 to 2025-12-10">
        </div>

        <div class="mb-3">
            <label for="special_requests" class="form-label">Special Requests</label>
            <textarea class="form-control" id="special_requests" name="special_requests" rows="4"><?php echo isset($special_requests) ? htmlspecialchars($special_requests) : ''; ?></textarea>
        </div>

        <button type="submit" name="book_submit" class="btn btn-primary">Book Now</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
