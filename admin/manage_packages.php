<?php
session_start();
include '../includes/db.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle delete request (using GET for simplicity, you can improve by using POST)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Get image filename before deleting package
    $stmt = $conn->prepare("SELECT image FROM packages WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($image_file);
    $stmt->fetch();
    $stmt->close();
    
    // Delete package record
    $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Delete image file if exists
    if ($image_file && file_exists('../images/' . $image_file)) {
        unlink('../images/' . $image_file);
    }

    // Redirect to avoid resubmission
    header('Location: manage_packages.php');
    exit();
}

// Fetch all packages
$result = $conn->query("SELECT * FROM packages ORDER BY id DESC");

include '../includes/header.php';
?>

<div class="container mt-5">
    <h1>Manage Packages</h1>
    <a href="add_package.php" class="btn btn-success mb-3">Add New Package</a>

    <?php if ($result->num_rows > 0): ?>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Location</th>
                <th>Price (₹)</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($pkg = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($pkg['id']); ?></td>
                    <td><?php echo htmlspecialchars($pkg['title']); ?></td>
                    <td><?php echo htmlspecialchars($pkg['location']); ?></td>
                    <td><?php echo number_format($pkg['price'], 2); ?></td>
                    <td>
                        <?php if ($pkg['image'] && file_exists('../images/' . $pkg['image'])): ?>
                            <img src="../images/<?php echo htmlspecialchars($pkg['image']); ?>" alt="Image" style="width: 80px; height: auto;">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_package.php?id=<?php echo $pkg['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="manage_packages.php?delete=<?php echo $pkg['id']; ?>" onclick="return confirm('Are you sure you want to delete this package?');" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No packages found. <a href="add_package.php">Add a package</a>.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
