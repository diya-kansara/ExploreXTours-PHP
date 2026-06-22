<?php
session_start();
include '../includes/db.php';

// Check if admin logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $itinerary = trim($_POST['itinerary'] ?? '');
    $hotels = trim($_POST['hotels'] ?? '');
    $inclusions = trim($_POST['inclusions'] ?? '');

    // Image upload handling
    $image_name = '';
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('pkg_') . '.' . $ext;
            $upload_path = '../images/' . $image_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image format. Allowed: JPEG, PNG, GIF.";
        }
    } else {
        $errors[] = "Please upload an image.";
    }

    // Basic validation
    if (!$title) $errors[] = "Title is required.";
    if (!$location) $errors[] = "Location is required.";
    if (!$price || !is_numeric($price)) $errors[] = "Valid price is required.";
    if (!$description) $errors[] = "Description is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO packages (title, location, price, description, image, itinerary, hotels, inclusions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssss", $title, $location, $price, $description, $image_name, $itinerary, $hotels, $inclusions);

        if ($stmt->execute()) {
            $success = "Package added successfully!";
            // Clear form data after success
            $_POST = [];
        } else {
            $errors[] = "Database error: Failed to add package.";
        }

        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div class="container mt-5" style="max-width: 700px;">
    <h1>Add New Package</h1>

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
            <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" class="form-control" id="location" name="location" required value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price (₹)</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Package Image</label>
            <input type="file" class="form-control" id="image" name="image" required accept="image/*">
        </div>

        <div class="mb-3">
            <label for="itinerary" class="form-label">Itinerary</label>
            <textarea class="form-control" id="itinerary" name="itinerary" rows="3"><?php echo htmlspecialchars($_POST['itinerary'] ?? ''); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="hotels" class="form-label">Hotels</label>
            <textarea class="form-control" id="hotels" name="hotels" rows="2"><?php echo htmlspecialchars($_POST['hotels'] ?? ''); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="inclusions" class="form-label">Inclusions</label>
            <textarea class="form-control" id="inclusions" name="inclusions" rows="2"><?php echo htmlspecialchars($_POST['inclusions'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Add Package</button>
        <a href="manage_packages.php" class="btn btn-secondary">Back to Manage Packages</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
