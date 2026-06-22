<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
include '../includes/header.php';
?>

<div class="container mt-5">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h1>
    <p>This is the admin dashboard.</p>

    <ul>
        <li><a href="manage_packages.php">Manage Packages</a></li>
        <li><a href="manage_bookings.php">View Bookings</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="view_messages.php">View Contact Messages</a></li>

        <li><a href="logout.php" class="btn btn-danger mt-3">Logout</a></li>
    </ul>
</div>

<?php include '../includes/footer.php'; ?>
