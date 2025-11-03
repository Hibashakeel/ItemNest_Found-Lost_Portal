<?php
require_once "../classes/User.php";
require_once "../config.php";

if ($_POST) {
  $user = new User();
  if ($user->register($_POST['name'], $_POST['email'], $_POST['password'])) {
    echo "<script>alert('Registered Successfully! Login now.'); window.location='login.php';</script>";
  } else {
    echo "<script>alert('Error: Email may already exist.');</script>";
  }
}
?>

<?php include "../includes/header.php"; ?>
<div class="form-container">
  <h2>Create Account</h2>
  <form method="POST">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Register</button>
  </form>
  <p>Already have an account? <a href="login.php">Login</a></p>
</div>
<?php include "../includes/footer.php"; ?>
