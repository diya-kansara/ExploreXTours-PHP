<?php
include 'includes/db.php';  // DB connection
include 'includes/header.php'; // Header and navbar

// Check if package ID is valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $package_id = intval($_GET['id']);

    // Fetch package data
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
    echo "<div class='alert alert-danger'>Invalid Package ID.</div>";
    include 'includes/footer.php';
    exit();
}
?>

<!-- Main Container -->
<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-6">
            <img src="images/<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="img-fluid rounded">
        </div>
        <div class="col-md-6">
            <h2><?php echo htmlspecialchars($package['title']); ?></h2>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($package['location']); ?></p>
            <p><strong>Duration:</strong> <?php echo htmlspecialchars($package['duration'] ?? 'Not specified'); ?></p>
            <p><strong>Price:</strong> ₹<?php echo number_format($package['price'], 2); ?></p>

           <a href="book.php?package_id=<?php echo $package['id']; ?>" class="btn btn-success">Book Now</a>

        </div>
    </div>

    <hr>

    <!-- Full Description -->
    <div class="mb-4">
        <h4>About This Package</h4>
        <p><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
    </div>

    <!-- Itinerary -->
    <div class="mb-4">
        <h4>Itinerary</h4>
        <pre style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($package['itinerary'])); ?></pre>
    </div>

    <!-- Hotel Info -->
    <div class="mb-4">
        <h4>Hotel Details</h4>
        <p><?php echo nl2br(htmlspecialchars($package['hotel_details'])); ?></p>
    </div>

    <!-- Inclusions -->
    <div class="mb-4">
        <h4>What's Included</h4>
        <p><?php echo nl2br(htmlspecialchars($package['inclusions_detail'])); ?></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
