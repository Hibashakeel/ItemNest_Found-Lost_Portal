<?php
require_once "../config.php";
require_once "../classes/User.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (!empty($email)) {
        $user = new User();
        $conn = $user->connect();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            // Generate reset token (simple version for demo)
            $reset_token = bin2hex(random_bytes(32));
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
            $stmt2->bind_param("ssi", $reset_token, $reset_expires, $user_data['id']);
            
            if ($stmt2->execute()) {
                // In a real application, you would send an email here
                // For demo purposes, we'll show the reset link
                $reset_link = BASE_URL . "pages/reset_password.php?token=" . $reset_token;
                $message = "Password reset link generated!<br><br>
                           <strong>Demo Reset Link:</strong><br>
                           <a href='{$reset_link}' style='word-break: break-all;'>{$reset_link}</a><br><br>
                           <small>In a real application, this would be sent to your email.</small>";
            } else {
                $message = "Error generating reset link. Please try again.";
            }
            $stmt2->close();
        } else {
            $message = "No account found with that email address.";
        }
        $stmt->close();
    } else {
        $message = "Please enter your email address.";
    }
}

include "../includes/header.php";
?>

<div class="form-container">
    <div class="form-header">
        <h2>🔐 Forgot Password</h2>
        <p>Enter your email address to reset your password</p>
    </div>

    <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'generated') !== false ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <div class="form-group">
            <label class="form-label">
                <span>Email Address</span>
            </label>
            <input type="email" name="email" class="form-control" 
                   placeholder="Enter your registered email" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">
                <i>🔑</i> Send Reset Link
            </button>
        </div>
        
        <div class="form-footer">
            <p>Remember your password? <a href="login.php">Back to Login</a></p>
        </div>
    </form>
</div>

<style>
.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.form-header p {
    color: var(--text-light);
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

.form-footer a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.form-footer a:hover {
    text-decoration: underline;
}
</style>

<?php include "../includes/footer.php"; ?>