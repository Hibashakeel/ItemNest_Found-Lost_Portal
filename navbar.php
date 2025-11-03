<?php
// Check if config is already loaded
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="navbar">
  <div class="nav-logo"><a href="<?php echo BASE_URL; ?>" style="color:inherit; text-decoration:none;">ItemNest</a></div>
  <div class="nav-links">
    <a href="<?php echo BASE_URL; ?>">Home</a>
    <a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a>
    <a href="<?php echo BASE_URL; ?>pages/report_lost.php">Report Lost</a>
    <a href="<?php echo BASE_URL; ?>pages/report_found.php">Report Found</a>

    <?php if(isset($_SESSION['user'])): 
        $notif = new Notification();
        $count = $notif->unreadCount($_SESSION['user']['id']);
    ?>
      <a href="<?php echo BASE_URL; ?>pages/notifications.php">Notifications <?php if($count>0) echo "($count)"; ?></a>
      <a href="<?php echo BASE_URL; ?>pages/profile.php">Profile</a> <!-- YEH LINE ADD KAREIN -->
      <?php if($_SESSION['user']['role'] === 'admin'): ?>
        <a href="<?php echo BASE_URL; ?>pages/admin_panel.php">Admin</a>
      <?php endif; ?>
      <a href="<?php echo BASE_URL; ?>pages/logout.php">Logout</a>
    <?php else: ?>
      <a href="<?php echo BASE_URL; ?>pages/login.php">Login</a>
      <a href="<?php echo BASE_URL; ?>pages/register.php">Register</a>
    <?php endif; ?>
  </div>
</nav>