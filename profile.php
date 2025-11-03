<?php
require_once "../config.php";
require_once "../classes/User.php";
require_once "../classes/Item.php";

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$user = new User();
$item = new Item();

// Get user stats
$user_stats = $item->getUserStats($user_id);

// Handle profile update
$message = "";
$message_type = ""; // Add this line
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (!empty($name) && !empty($email)) {
        $conn = $user->connect();
        $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $message = "Profile updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating profile. Please try again.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = "New passwords do not match!";
        $message_type = "error";
    } else {
        // Verify current password
        $conn = $user->connect();
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        
        if (password_verify($current_password, $user_data['password']) || $current_password === $user_data['password']) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = "Password changed successfully!";
                $message_type = "success";
            } else {
                $message = "Error changing password. Please try again.";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Current password is incorrect!";
            $message_type = "error";
        }
    }
}

include "../includes/header.php";
?>

<div class="dashboard">
    <!-- Profile Header -->
    <div class="admin-header">
        <h2>My Profile 👤</h2>
        <p>Manage your account settings and preferences</p>
    </div>

    <?php if ($message && $message_type === 'error'): ?>
        <div class="alert alert-error">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="profile-container">
        <!-- User Stats -->
        <div class="profile-stats">
            <div class="stat-card profile-stat">
                <div class="stat-icon">
                    <i>🔍</i>
                </div>
                <div class="stat-number"><?php echo $user_stats['lost']; ?></div>
                <div class="stat-label">Lost Items</div>
            </div>
            
            <div class="stat-card profile-stat">
                <div class="stat-icon">
                    <i>✅</i>
                </div>
                <div class="stat-number"><?php echo $user_stats['found']; ?></div>
                <div class="stat-label">Found Items</div>
            </div>
            
            <div class="stat-card profile-stat">
                <div class="stat-icon">
                    <i>⏳</i>
                </div>
                <div class="stat-number"><?php echo $user_stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            
            <div class="stat-card profile-stat">
                <div class="stat-icon">
                    <i>🎉</i>
                </div>
                <div class="stat-number"><?php echo $user_stats['returned']; ?></div>
                <div class="stat-label">Returned</div>
            </div>
        </div>

        <div class="profile-content">
            <!-- Profile Information -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">👤</div>
                    <h3>Profile Information</h3>
                </div>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>Full Name</span>
                        </label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($_SESSION['user']['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>Email Address</span>
                        </label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>User Role</span>
                        </label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($_SESSION['user']['role']); ?>" readonly disabled>
                        <div class="form-hint">Role cannot be changed</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i>💾</i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">🔒</div>
                    <h3>Change Password</h3>
                </div>
                
                <form method="POST" class="profile-form" id="passwordForm">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span>Current Password</span>
                        </label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>New Password</span>
                        </label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <div class="form-hint">Minimum 6 characters</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span>Confirm New Password</span>
                        </label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i>🔑</i> Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Information -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">ℹ️</div>
                    <h3>Account Information</h3>
                </div>
                
                <div class="account-info">
                    <div class="info-item">
                        <label>User ID:</label>
                        <span><?php echo $_SESSION['user']['id']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Registration Date:</label>
                        <span><?php echo date('F j, Y', strtotime($_SESSION['user']['created_at'] ?? 'now')); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Account Status:</label>
                        <span class="status-active">Active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Popup Modal -->
<?php if ($message && $message_type === 'success'): ?>
<div class="modal-overlay" id="successModal">
    <div class="modal-content">
        <div class="modal-icon success">
            <i>✅</i>
        </div>
        <div class="modal-body">
            <h3>Success!</h3>
            <p><?php echo $message; ?></p>
        </div>
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="closeModal()">Continue</button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.profile-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-top: 2rem;
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.profile-stat {
    text-align: center;
    transition: var(--transition);
}

.profile-stat:hover {
    transform: translateY(-5px);
}

.profile-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.profile-section {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.account-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--surface-alt);
    border-radius: var(--radius);
}

.info-item label {
    font-weight: 600;
    color: var(--text-light);
}

.info-item span {
    color: var(--text-dark);
    font-weight: 500;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: var(--shadow-lg);
    animation: slideUp 0.3s ease;
}

.modal-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
}

.modal-icon.success {
    background: var(--success-light);
    color: var(--success);
}

.modal-body h3 {
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.modal-body p {
    color: var(--text-light);
    margin-bottom: 1.5rem;
}

.modal-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 968px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
    
    .profile-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .profile-stats {
        grid-template-columns: 1fr;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .modal-content {
        margin: 1rem;
        padding: 1.5rem;
    }
}
</style>

<script>
function closeModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('successModal');
    if (modal && event.target === modal) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('successModal');
    if (modal && event.key === 'Escape') {
        closeModal();
    }
});

// Auto close after 5 seconds
setTimeout(() => {
    closeModal();
}, 5000);

// Add fadeOut animation to CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);

// Clear password form after successful submission
<?php if ($message && $message_type === 'success' && isset($_POST['change_password'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.reset();
    }
});
<?php endif; ?>
</script>

<?php include "../includes/footer.php"; ?>