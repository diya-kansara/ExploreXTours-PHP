<?php
session_start();
include '../includes/db.php';

// Check if admin logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get package ID from URL
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header('Location: manage_packages.php');
    exit();
}

$errors = [];
$success = '';

// Fetch current package data
$stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header('Location: manage_packages.php');
    exit();
}

$package = $result->fetch_assoc();
$stmt->close();

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $itinerary = trim($_POST['itinerary'] ?? '');
    $hotels = trim($_POST['hotels'] ?? '');
    $inclusions = trim($_POST['inclusions'] ?? '');

    // Image upload handling (optional)
    $image_name = $package['image']; // Keep old image by default
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('pkg_') . '.' . $ext;
            $upload_path = '../images/' . $image_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload new image.";
            } else {
                // Optionally delete old image file
                if ($package['image'] && file_exists('../images/' . $package['image'])) {
                    unlink('../images/' . $package['image']);
                }
            }
        } else {
            $errors[] = "Invalid image format. Allowed: JPEG, PNG, GIF.";
        }
    }

    // Basic validation
    if (!$title) $errors[] = "Title is required.";
    if (!$location) $errors[] = "Location is required.";
    if (!$price || !is_numeric($price)) $errors[] = "Valid price is required.";
    if (!$description) $errors[] = "Description is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE packages SET title=?, location=?, price=?, description=?, image=?, itinerary=?, hotels=?, inclusions=? WHERE id=?");
        $stmt->bind_param("ssdsssssi", $title, $location, $price, $description, $image_name, $itinerary, $hotels, $inclusions, $id);

        if ($stmt->execute()) {
            $success = "Package updated successfully!";
            // Refresh package data
            $package = [
                'title' => $title,
                'location' => $location,
                'price' => $price,
                'description' => $description,
                'image' => $image_name,
                'itinerary' => $itinerary,
                'hotels' => $hotels,
                'inclusions' => $inclusions,
            ];
        } else {
            $errors[] = "Database error: Could not update package.";
        }

        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div class="container mt-5" style="max-width: 700px;">
    <h1>Edit Package</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error) echo '<li>' . htmlspecialchars($error) . '</li>'; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="">
        <div class="mb-3">
            <label for="title" class="form-label">Package Title</label>
            <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($package['title']); ?>">
        </div>

        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" required value="<?php echo htmlspecialchars($package['location']); ?>">
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price (₹)</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required value="<?php echo htmlspecialchars($package['price']); ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($package['description']); ?></textarea>
        </div>

        <div class="mb-3">
            <label>Current Image</label><br>
            <?php if ($package['image'] && file_exists('../images/' . $package['image'])): ?>
                <img src="../images/<?php echo htmlspecialchars($package['image']); ?>" alt="Package Image" style="max-width: 200px;">
            <?php else: ?>
                <p>No image uploaded</p>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Replace Image (optional)</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="itinerary" class="form-label">Itinerary</label>
            <textarea class="form-control" id="itinerary" name="itinerary" rows="3"><?php echo htmlspecialchars($package['itinerary']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="hotels" class="form-label">Hotels</label>
            <textarea class="form-control" id="hotels" name="hotels" rows="2"><?php echo htmlspecialchars($package['hotels']); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="inclusions" class="form-label">Inclusions</label>
            <textarea class="form-control" id="inclusions" name="inclusions" rows="2"><?php echo htmlspecialchars($package['inclusions']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Package</button>
        <a href="manage_packages.php" class="btn btn-secondary">Back to Manage Packages</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
