<?php
include 'includes/db.php';
include 'includes/header.php';
?>

<div class="container mt-5">
  <h1 class="text-center mb-4">All Tour Packages</h1>

  <div class="row">
    <?php
    $sql = "SELECT * FROM packages";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_assoc($result)) {
    ?>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <img src="images/<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>...</p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
            <p><strong>Price:</strong> ₹<?php echo number_format($row['price']); ?></p>
            <a href="package_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary mt-auto">View Details</a>
          </div>
        </div>
      </div>
    <?php
      }
    } else {
      echo "<p>No packages available right now.</p>";
    }
    ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
