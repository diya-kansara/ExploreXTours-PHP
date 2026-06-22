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
    <h2 class="mb-4">Contact Messages</h2>

    <?php
    $sql = "SELECT * FROM contacts ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo "
        <table class='table table-bordered table-striped'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
        ";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "
            <tr>
                <td>{$row['id']}</td>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . htmlspecialchars($row['email']) . "</td>
                <td>" . htmlspecialchars($row['subject']) . "</td>
                <td>" . nl2br(htmlspecialchars($row['message'])) . "</td>
                <td>{$row['created_at']}</td>
            </tr>";
        }

        echo "
            </tbody>
        </table>";
    } else {
        echo "<div class='alert alert-info'>No messages found.</div>";
    }
    ?>
</div>

<?php include '../includes/footer.php'; ?>
