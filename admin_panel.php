<?php
require_once "../config.php";
require_once "../classes/Admin.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); 
    exit;
}

$admin = new Admin();

// Handle Actions
if (isset($_GET['block'])) $admin->toggleUserBlock($_GET['block'], 1);
if (isset($_GET['unblock'])) $admin->toggleUserBlock($_GET['unblock'], 0);
if (isset($_GET['approve'])) $admin->updateItemStatus($_GET['approve'], 'approved');
if (isset($_GET['reject'])) $admin->updateItemStatus($_GET['reject'], 'rejected');

// Handle item return
if (isset($_POST['mark_returned'])) {
    $admin->markItemAsReturned($_POST['item_id'], $_POST['return_location'], $_POST['contact_info']);
}

$users = $admin->getAllUsers();
$items = $admin->getAllItemsAdmin();

// Get statistics
$user_count = $users->num_rows;
$item_count = $items->num_rows;
$lost_count = 0;
$found_count = 0;

// Reset pointer to count items properly
$items->data_seek(0);
while($it = $items->fetch_assoc()) {
    if ($it['status'] === 'lost') $lost_count++;
    if ($it['status'] === 'found') $found_count++;
}
// Reset pointer again for main display
$items->data_seek(0);

include "../includes/header.php";
?>

<div class="dashboard">
    <!-- Admin Header -->
    <div class="admin-header">
        <h2>Admin Control Panel</h2>
        <p>Manage users, items, and system operations</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon users">
                <i>👥</i>
            </div>
            <div class="stat-number"><?php echo $user_count; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon items">
                <i>📦</i>
            </div>
            <div class="stat-number"><?php echo $item_count; ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon lost">
                <i>🔍</i>
            </div>
            <div class="stat-number"><?php echo $lost_count; ?></div>
            <div class="stat-label">Lost Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon found">
                <i>✅</i>
            </div>
            <div class="stat-number"><?php echo $found_count; ?></div>
            <div class="stat-label">Found Items</div>
        </div>
    </div>

    <!-- User Management Section -->
    <div class="section-header">
        <div class="section-icon">👤</div>
        <h3>Manage Users</h3>
    </div>

    <?php if ($users->num_rows > 0): ?>
        <div class="user-management-grid">
            <?php while($u = $users->fetch_assoc()): ?>
                <div class="user-card <?php echo $u['role'] === 'admin' ? 'admin-user' : ''; ?> <?php echo $u['is_blocked'] ? 'blocked-user' : ''; ?>">
                    <div class="user-header">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($u['name'], 0, 2)); ?>
                        </div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($u['name']); ?></h4>
                            <p><?php echo htmlspecialchars($u['email']); ?></p>
                            <span class="user-role <?php echo $u['role']; ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </div>
                        <div class="user-status <?php echo $u['is_blocked'] ? 'status-blocked' : 'status-active'; ?>">
                            <?php echo $u['is_blocked'] ? 'Blocked' : 'Active'; ?>
                        </div>
                    </div>
                    
                    <?php if($u['role'] !== 'admin'): ?>
                        <div class="user-actions">
                            <?php if($u['is_blocked']): ?>
                                <a href="?unblock=<?php echo $u['id']; ?>" class="btn btn-success btn-sm">
                                    <i>🔓</i> Unblock
                                </a>
                            <?php else: ?>
                                <a href="?block=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm">
                                    <i>🚫</i> Block
                                </a>
                            <?php endif; ?>
                            <a href="user_details.php?id=<?php echo $u['id']; ?>" class="btn btn-outline btn-sm">
                                <i>👁️</i> View Details
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="user-actions">
                            <span class="btn btn-outline btn-sm" style="cursor: default;">
                                <i>🛡️</i> Admin Account
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i>👥</i>
            <h4>No Users Found</h4>
            <p>There are no users registered in the system yet.</p>
        </div>
    <?php endif; ?>

    <!-- Item Management Section -->
    <div class="section-header">
        <div class="section-icon">📦</div>
        <h3>Manage Items</h3>
    </div>

    <?php if ($items->num_rows > 0): ?>
        <div class="item-management-grid">
            <?php while($it = $items->fetch_assoc()): ?>
                <div class="item-card-admin <?php echo $it['status']; ?>-item">
                    <div class="item-header">
                        <div class="item-title">
                            <h4><?php echo htmlspecialchars($it['item_name']); ?></h4>
                            <span class="item-category"><?php echo htmlspecialchars($it['category']); ?></span>
                        </div>
                        <div class="item-status-badge status-<?php echo $it['status_admin']; ?>">
                            <?php echo ucfirst($it['status_admin']); ?>
                        </div>
                    </div>

                    <div class="item-details">
                        <div class="item-detail">
                            <i>📍</i>
                            <span><strong>Location:</strong> <?php echo htmlspecialchars($it['location']); ?></span>
                        </div>
                        <div class="item-detail">
                            <i>📅</i>
                            <span><strong>Date:</strong> <?php echo $it['date_lost']; ?></span>
                        </div>
                        <div class="item-detail">
                            <i>🏷️</i>
                            <span><strong>Type:</strong> <?php echo ucfirst($it['status']); ?> Item</span>
                        </div>
                    </div>

                    <div class="item-owner">
                        <div class="owner-label">Reported By</div>
                        <div class="owner-info">
                            <?php echo htmlspecialchars($it['owner_name']); ?> — <?php echo htmlspecialchars($it['owner_email']); ?>
                        </div>
                    </div>

                    <div class="item-actions">
                        <a href="view_item.php?id=<?php echo $it['id']; ?>" class="btn btn-outline btn-sm">
                            <i>👁️</i> View Item Details
                        </a>
                    </div>

                    <?php if ($it['status_admin'] === 'pending'): ?>
                        <div class="action-buttons">
                            <a href="?approve=<?php echo $it['id']; ?>" class="btn btn-success btn-sm">
                                <i>✅</i> Approve
                            </a>
                            <a href="?reject=<?php echo $it['id']; ?>" class="btn btn-danger btn-sm">
                                <i>❌</i> Reject
                            </a>
                            
                            <!-- Mark as Returned Form -->
                            <form method="POST" class="return-form">
                                <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
                                <h5><i>📦</i> Mark as Returned</h5>
                                <input type="text" name="return_location" placeholder="Pickup Location" required>
                                <input type="text" name="contact_info" placeholder="Contact Information" required>
                                <button type="submit" name="mark_returned" class="btn btn-info btn-sm">
                                    <i>🚚</i> Mark as Returned
                                </button>
                            </form>
                        </div>
                    <?php elseif ($it['status_admin'] === 'approved'): ?>
                        <div class="action-buttons">
                            <span class="btn btn-success btn-sm" style="cursor: default;">
                                <i>✅</i> Approved
                            </span>
                            <form method="POST" class="return-form">
                                <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
                                <h5><i>📦</i> Mark as Returned</h5>
                                <input type="text" name="return_location" placeholder="Pickup Location" required>
                                <input type="text" name="contact_info" placeholder="Contact Information" required>
                                <button type="submit" name="mark_returned" class="btn btn-info btn-sm">
                                    <i>🚚</i> Mark as Returned
                                </button>
                            </form>
                        </div>
                    <?php elseif ($it['status_admin'] === 'returned'): ?>
                        <div class="action-buttons">
                            <span class="btn btn-info btn-sm" style="cursor: default;">
                                <i>✅</i> Returned to Owner
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i>📦</i>
            <h4>No Items Found</h4>
            <p>There are no items reported in the system yet.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.item-actions {
    margin: 1rem 0;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.item-actions .btn {
    width: 100%;
    text-align: center;
    justify-content: center;
}
</style>

<?php include "../includes/footer.php"; ?>

