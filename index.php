<?php include 'includes/header.php'; ?> <?php include 'includes/db.php'; ?> <!-- Hero Section -->
<div class="hero text-white text-center py-5"
    style="background: url('images/hero.jpg') no-repeat center center; background-size: cover; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; min-height: 400px; display: flex; align-items: center; justify-content: center; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);">
    <div class="container">
        <h1 class="display-4 fw-bold">Adventure Awaits</h1>
        <p class="lead">Explore amazing destinations with ExploreX Tours</p> <a href="packages.php"
            class="btn btn-light btn-lg">Browse Packages</a>
    </div>
</div> <!-- Why Choose Us -->
<div class="container my-5">
  <h2 class="text-center text-dark-custom mb-4">Why Choose Us?</h2>
  <div class="row text-center">
    <div class="col-md-4 mb-4">
      <div class="p-4 shadow-sm rounded bg-white h-100 border">
        <h4 class="fw-bold">🌍 Handpicked Tours</h4>
        <p>All our packages are carefully selected for the best travel experience.</p>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="p-4 shadow-sm rounded bg-white h-100 border">
        <h4 class="fw-bold">💰 Affordable Prices</h4>
        <p>We offer competitive pricing with top-quality service.</p>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="p-4 shadow-sm rounded bg-white h-100 border">
        <h4 class="fw-bold">📞 24/7 Support</h4>
        <p>We’re here to help you at every step of your journey.</p>
      </div>
    </div>
  </div>
</div>

</div> <!-- Featured Packages -->
<div class="container">
    <h2 class="text-center mb-4">Featured Packages</h2>
    <div class="row">
        <?php $sql = "SELECT * FROM packages LIMIT 3";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100"> <img src="images/<?php echo $row['image']; ?>" class="card-img-top"
                        alt="<?php echo $row['title']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $row['title']; ?></h5>
                        <p class="card-text"><?php echo substr($row['description'], 0, 100); ?>...</p> <a
                            href="package_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div> <?php } ?>
    </div>
</div> <!-- Explore More Section -->
<div class="text-center my-5">
    <h4>Didn't find your perfect trip?</h4>
    <p>We have many more packages waiting for you. Explore by destination, theme, or experience.</p> <a
        href="packages.php" class="btn btn-outline-primary btn-lg mt-3">Explore All Packages</a>
</div> <?php include 'includes/footer.php'; ?>