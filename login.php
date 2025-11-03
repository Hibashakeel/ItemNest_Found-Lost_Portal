<?php
require_once "../classes/User.php";
require_once "../config.php";

if ($_POST) {
  $user = new User();
  $data = $user->login($_POST['email'], $_POST['password']);

  if ($data) {
    // 🚫 Handle blocked user
    if (isset($data['error']) && $data['error'] === 'blocked') {
      $error = "Your account has been blocked by admin.";
    } else {
      // ✅ Successful login
      $_SESSION['user'] = $data;
      header("Location: dashboard.php");
      exit();
    }
  } else {
    $error = "Invalid email or password.";
  }
}
?>

<?php include "../includes/header.php"; ?>
<div class="form-container">
  <h2>Login</h2>
  <form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>

  <!-- Login form ke baad yeh line add karein -->
<p style="text-align: center; margin-top: 1rem;">
    <a href="forgot_password.php" style="color: var(--primary); text-decoration: none;">
        Forgot your password?
    </a>
</p>

  <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

  <p>Don’t have an account? <a href="register.php">Register</a></p>
</div>
<?php include "../includes/footer.php"; ?>
