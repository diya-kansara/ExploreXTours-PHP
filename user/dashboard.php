<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';

$user_email = $_SESSION['user_email'] ?? '';

// Prepare query to get bookings of this user by matching email
$query = "
    SELECT b.id, p.title, b.booking_date, b.status
    FROM bookings b
    LEFT JOIN packages p ON b.package_id = p.id
    WHERE b.email = ?
    ORDER BY b.booking_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container mt-5">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
    <h3>Your Bookings</h3>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Booking ID</th>
                    <th>Package</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td><?php echo htmlspecialchars($booking['title'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                        <td><?php echo htmlspecialchars($booking['status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have no bookings yet.</p>
    <?php endif; ?>

    <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
</div>

<?php include '../includes/footer.php'; ?>
