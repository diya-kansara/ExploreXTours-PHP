<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Tours & Travels</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <!-- Bootstrap CSS CDN -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="/Online-Tours-and-Travels-Website/css/style.css" />
  <!-- Add to your <head> -->
<link href="https://fonts.googleapis.com/css2?family=Courgette&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">


</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="/Online-Tours-and-Travels-Website/index.php">ExploreX Tours ✈</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ml-auto">
  <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/index.php">Home</a></li>

  <?php if (isset($_SESSION['admin_id'])): ?>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/admin/dashboard.php">Admin Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/admin/logout.php">Logout</a></li>

  <?php elseif (isset($_SESSION['user_id'])): ?>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/about.php">About</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/packages.php">Tours</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/contact.php">Contact</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/user/dashboard.php">Dashboard</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/user/logout.php">Logout</a></li>

  <?php else: ?>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/about.php">About</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/packages.php">Tours</a></li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/contact.php">Contact</a></li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        Login
      </a>
      <div class="dropdown-menu dropdown-menu-right" aria-labelledby="loginDropdown">
        <a class="dropdown-item" href="/Online-Tours-and-Travels-Website/user/login.php">Login as User</a>
        <a class="dropdown-item" href="/Online-Tours-and-Travels-Website/admin/login.php">Login as Admin</a>
      </div>
    </li>
    <li class="nav-item"><a class="nav-link" href="/Online-Tours-and-Travels-Website/user/register.php">Register</a></li>
  <?php endif; ?>
</ul>

  </div>
</nav>

<!-- Use full jQuery, not slim -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
  $(document).ready(function() {
    $('.dropdown-toggle').dropdown();
  });
</script>

</body>
</html>
