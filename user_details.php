<?php
require_once "../config.php";
require_once "../classes/Admin.php";
require_once "../classes/Item.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php"); 
    exit;
}

// Validate user ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: admin_panel.php");
    exit;
}

$user_id = intval($_GET['id']);
$admin = new Admin();
$item = new Item();

// Get user details
$users = $admin->getAllUsers();
$user_data = null;
$users->data_seek(0);
while($u = $users->fetch_assoc()) {
    if ($u['id'] == $user_id) {
        $user_data = $u;
        break;
    }
}

if (!$user_data) {
    $_SESSION['error'] = "User not found!";
    header("Location: admin_panel.php");
    exit;
}

// Get user's items
$user_items = $item->getUserItems($user_id);
$items_count = $user_items->num_rows;

include "../includes/header.php";
?>

<div class="dashboard">
    <!-- User Header -->
    <div class="admin-header">
        <h2>User Details</h2>
        <p>Complete information about user and their activities</p>
    </div>

    <div class="user-detail-view">
        <!-- User Information -->
        <div class="user-info-card">
            <div class="user-header-detail">
                <div class="user-avatar-large">
                    <?php echo strtoupper(substr($user_data['name'], 0, 2)); ?>
                </div>
                <div class="user-info-main">
                    <h1><?php echo htmlspecialchars($user_data['name']); ?></h1>
                    <p class="user-email"><?php echo htmlspecialchars($user_data['email']); ?></p>
                    <div class="user-meta">
                        <span class="user-role <?php echo $user_data['role']; ?>">
                            <?php echo ucfirst($user_data['role']); ?>
                        </span>
                        <span class="user-status <?php echo $user_data['is_blocked'] ? 'status-blocked' : 'status-active'; ?>">
                            <?php echo $user_data['is_blocked'] ? 'Blocked' : 'Active'; ?>
                        </span>
                        <span class="user-id">ID: <?php echo $user_data['id']; ?></span>
                    </div>
                </div>
            </div>

            <!-- User Stats -->
            <div class="user-stats-grid">
                <div class="user-stat-item">
                    <div class="stat-number"><?php echo $items_count; ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="user-stat-item">
                    <div class="stat-number"><?php echo date('M j, Y', strtotime($user_data['created_at'] ?? 'now')); ?></div>
                    <div class="stat-label">Joined Date</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="user-actions-detail">
                <?php if($user_data['role'] !== 'admin'): ?>
                    <?php if($user_data['is_blocked']): ?>
                        <a href="admin_panel.php?unblock=<?php echo $user_data['id']; ?>" class="btn btn-success">
                            <i>🔓</i> Unblock User
                        </a>
                    <?php else: ?>
                        <a href="admin_panel.php?block=<?php echo $user_data['id']; ?>" class="btn btn-danger">
                            <i>🚫</i> Block User
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="btn btn-outline" style="cursor: default;">
                        <i>🛡️</i> Admin Account
                    </span>
                <?php endif; ?>
                
                <a href="admin_panel.php" class="btn btn-secondary">
                    <i>←</i> Back to Admin Panel
                </a>
            </div>
        </div>

        <!-- User's Items -->
        <div class="user-items-section">
            <h3>User's Reported Items (<?php echo $items_count; ?>)</h3>
            
            <?php if ($user_items->num_rows > 0): ?>
                <div class="items-grid-compact">
                    <?php while($item_row = $user_items->fetch_assoc()): ?>
                        <div class="item-card-compact">
                            <div class="item-header-compact">
                                <h4><?php echo htmlspecialchars($item_row['item_name']); ?></h4>
                                <span class="item-status status <?php echo $item_row['status']; ?>">
                                    <?php echo ucfirst($item_row['status']); ?>
                                </span>
                            </div>
                            
                            <div class="item-details-compact">
                                <div class="item-detail">
                                    <span>📍</span>
                                    <span><?php echo htmlspecialchars($item_row['location']); ?></span>
                                </div>
                                <div class="item-detail">
                                    <span>📅</span>
                                    <span><?php echo htmlspecialchars($item_row['date_lost']); ?></span>
                                </div>
                                <div class="item-detail">
                                    <span>🏷️</span>
                                    <span>Admin: <?php echo ucfirst($item_row['status_admin']); ?></span>
                                </div>
                            </div>
                            
                            <div class="item-actions-compact">
                                <a href="view_item.php?id=<?php echo $item_row['id']; ?>" class="btn btn-outline btn-sm">
                                    <i>👁️</i> View
                                </a>
                                <?php if ($item_row['status_admin'] === 'pending'): ?>
                                    <a href="admin_panel.php?approve=<?php echo $item_row['id']; ?>" class="btn btn-success btn-sm">
                                        <i>✅</i> Approve
                                    </a>
                                    <a href="admin_panel.php?reject=<?php echo $item_row['id']; ?>" class="btn btn-danger btn-sm">
                                        <i>❌</i> Reject
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-illustration">📦</div>
                    <h4>No Items Reported</h4>
                    <p>This user hasn't reported any items yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.user-detail-view {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.user-info-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    height: fit-content;
}

.user-header-detail {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.user-info-main h1 {
    margin: 0 0 0.5rem 0;
    color: var(--text-dark);
    font-size: 1.5rem;
}

.user-email {
    margin: 0 0 1rem 0;
    color: var(--text-light);
    font-size: 1rem;
}

.user-meta {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.user-role, .user-status, .user-id {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.user-role.admin {
    background: #e0f2fe;
    color: var(--secondary);
}

.user-role.user {
    background: var(--primary-light);
    color: var(--primary);
}

.status-active {
    background: #dcfce7;
    color: var(--success);
}

.status-blocked {
    background: #fee2e2;
    color: var(--danger);
}

.user-id {
    background: var(--surface-alt);
    color: var(--text-light);
}

.user-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--surface-alt);
    border-radius: var(--radius);
}

.user-stat-item {
    text-align: center;
}

.user-stat-item .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.user-stat-item .stat-label {
    color: var(--text-light);
    font-size: 0.85rem;
}

.user-actions-detail {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.user-items-section {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
}

.user-items-section h3 {
    margin: 0 0 1.5rem 0;
    color: var(--text-dark);
    font-size: 1.3rem;
}

.items-grid-compact {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.item-card-compact {
    background: var(--surface-alt);
    border-radius: var(--radius);
    padding: 1.25rem;
    border-left: 4px solid var(--primary);
    transition: var(--transition);
}

.item-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.item-header-compact {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.item-header-compact h4 {
    margin: 0;
    color: var(--text-dark);
    font-size: 1rem;
    flex: 1;
}

.item-details-compact {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.item-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-light);
    font-size: 0.85rem;
}

.item-actions-compact {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 968px) {
    .user-detail-view {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .user-header-detail {
        flex-direction: column;
        text-align: center;
    }
    
    .user-meta {
        justify-content: center;
    }
    
    .items-grid-compact {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include "../includes/footer.php"; ?>