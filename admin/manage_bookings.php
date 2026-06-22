<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/db.php';
include '../includes/header.php';

// Handle status update
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];

    $status = ($action == 'approve') ? 'Approved' : (($action == 'cancel') ? 'Cancelled' : '');
    if ($status) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        $stmt->execute();
    }
}

// Simplified query — no JOIN with users table
$sql = "SELECT 
            b.id AS booking_id, 
            b.name, 
            b.email, 
            b.phone, 
            b.status, 
            b.created_at,
            p.title AS package_title
        FROM bookings b
        LEFT JOIN packages p ON b.package_id = p.id
        ORDER BY b.created_at DESC";

$result = mysqli_query($conn, $sql);
?>

<div class="container mt-5">
    <h2 class="mb-4">Manage Bookings</h2>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Package</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Booking Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): 
                $i = 1;
                while ($row = mysqli_fetch_assoc($result)):
            ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['package_title']); ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['phone']); ?></td>
                    <td>
                        <span class="badge 
                            <?= $row['status'] == 'Pending' ? 'bg-warning' : ($row['status'] == 'Approved' ? 'bg-success' : 'bg-danger') ?>">
                            <?= $row['status']; ?>
                        </span>
                    </td>
                    <td><?= date("d M Y", strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php if ($row['status'] == 'Pending'): ?>
                            <a href="?action=approve&id=<?= $row['booking_id']; ?>" class="btn btn-success btn-sm">Approve</a>
                            <a href="?action=cancel&id=<?= $row['booking_id']; ?>" class="btn btn-danger btn-sm">Cancel</a>
                        <?php else: ?>
                            <em>No actions</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr>
                    <td colspan="8" class="text-center">No bookings found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
