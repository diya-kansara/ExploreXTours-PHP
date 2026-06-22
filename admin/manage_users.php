<?php
session_start();
include '../includes/db.php';
include '../includes/header.php'; // Use your shared header

// Optional: Redirect if not admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: /Online-Tours-and-Travels-Website/index.php");
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user_id'])) {
    $delete_user_id = (int) $_POST['delete_user_id'];

    // Delete user's bookings first (if you want to cascade manually)
    $stmt1 = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
    $stmt1->bind_param("i", $delete_user_id);
    $stmt1->execute();
    $stmt1->close();

    // Delete the user
    $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt2->bind_param("i", $delete_user_id);
    $stmt2->execute();
    $stmt2->close();

    // Redirect to avoid resubmission on page refresh
    header("Location: manage_users.php");
    exit();
}
?>

<div class="container mt-5">
    <h2 class="mb-4">Manage Users</h2>

    <?php
    $user_query = "SELECT * FROM users ORDER BY id DESC";
    $user_result = mysqli_query($conn, $user_query);

    if (mysqli_num_rows($user_result) > 0):
        while ($user = mysqli_fetch_assoc($user_result)):
            $user_id = (int) $user['id'];
            $user_email = mysqli_real_escape_string($conn, $user['email']);

            // Fetch bookings by user_id OR by email as fallback
            $booking_query = "
                SELECT b.id, p.title AS package_title, b.booking_date, b.status
                FROM bookings b
                JOIN packages p ON b.package_id = p.id
                WHERE b.user_id = $user_id OR b.email = '$user_email'
                ORDER BY b.booking_date DESC
            ";
            $booking_result = mysqli_query($conn, $booking_query);
    ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                    (<?php echo htmlspecialchars($user['email']); ?>)
                    <span class="text-muted ml-3">Registered on: <?php echo $user['created_at']; ?></span>
                </div>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="delete_user_id" value="<?php echo $user_id; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete User</button>
                </form>
            </div>
            <div class="card-body">
                <h5 class="card-title">Bookings:</h5>

                <?php if (mysqli_num_rows($booking_result) > 0): ?>
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>#Booking ID</th>
                                <th>Package</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = mysqli_fetch_assoc($booking_result)): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['package_title']); ?></td>
                                    <td><?php echo $booking['booking_date']; ?></td>
                                    <td><?php echo ucfirst($booking['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No bookings found for this user.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php
        endwhile;
    else:
        echo "<p>No users found.</p>";
    endif;
    ?>
</div>

<?php include '../includes/footer.php'; ?>
