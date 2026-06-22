<?php include 'includes/db.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
  <h2 class="mb-4 text-center">Contact Us</h2>

  <?php
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $name = mysqli_real_escape_string($conn, $_POST['name']);
      $email = mysqli_real_escape_string($conn, $_POST['email']);
      $subject = mysqli_real_escape_string($conn, $_POST['subject']);
      $message = mysqli_real_escape_string($conn, $_POST['message']);

      $sql = "INSERT INTO contacts (name, email, subject, message)
              VALUES ('$name', '$email', '$subject', '$message')";

      if (mysqli_query($conn, $sql)) {
          echo '<div class="alert alert-success">Message sent successfully!</div>';
      } else {
          echo '<div class="alert alert-danger">Something went wrong. Try again.</div>';
      }
  }
  ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="name" class="form-label">Your Name</label>
      <input type="text" class="form-control" id="name" name="name" required />
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email address</label>
      <input type="email" class="form-control" id="email" name="email" required />
    </div>
    <div class="mb-3">
      <label for="subject" class="form-label">Subject</label>
      <input type="text" class="form-control" id="subject" name="subject" required />
    </div>
    <div class="mb-3">
      <label for="message" class="form-label">Your Message</label>
      <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Send Message</button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
