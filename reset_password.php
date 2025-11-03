<?php
require_once "../config.php";
require_once "../classes/User.php";

$message = "";
$valid_token = false;

// Check if token is provided and valid
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $user = new User();
    $conn = $user->connect();
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user_data = $result->fetch_assoc();
        
        // Handle password reset
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $message = "Passwords do not match!";
            } else {
                // Update password and clear reset token
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
                $stmt2->bind_param("si", $hashed_password, $user_data['id']);
                
                if ($stmt2->execute()) {
                    $message = "Password reset successfully! You can now <a href='login.php'>login</a> with your new password.";
                    $valid_token = false; // Token used
                } else {
                    $message = "Error resetting password. Please try again.";
                }
                $stmt2->close();
            }
        }
    } else {
        $message = "Invalid or expired reset token.";
    }
    $stmt->close();
} else {
    $message = "No reset token provided.";
}

include "../includes/header.php";
?>

<div class="form-container">
    <div class="form-header">
        <h2>🔄 Reset Password</h2>
        <p>Enter your new password</p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($valid_token): ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label class="form-label">
                    <span>New Password</span>
                </label>
                <input type="password" name="new_password" class="form-control" 
                       placeholder="Enter new password" required minlength="6">
                <div class="form-hint">Minimum 6 characters</div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <span>Confirm New Password</span>
                </label>
                <input type="password" name="confirm_password" class="form-control" 
                       placeholder="Confirm new password" required minlength="6">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">
                    <i>🔑</i> Reset Password
                </button>
            </div>
        </form>
    <?php elseif (!strpos($message, 'successfully')): ?>
        <div class="form-footer">
            <p><a href="forgot_password.php">Request a new reset link</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>

<!-- reset_password.php ke end mein bhi yeh add kar sakte hain -->
<?php if ($message && strpos($message, 'successfully') !== false): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    alert('Password reset successfully! You will be redirected to login page.');
    setTimeout(() => {
        window.location.href = 'login.php';
    }, 2000);
});
</script>
<?php endif; ?>