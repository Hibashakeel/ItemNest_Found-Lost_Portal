<?php
require_once "../config.php";
require_once "../classes/Item.php";
require_once "../classes/Notification.php";

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Validate item ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid item ID.";
    header("Location: dashboard.php");
    exit;
}

$item_id = intval($_GET['id']);
$item = new Item();
$notification = new Notification();

// Get item details
$item_data = $item->getItem($item_id);

if (!$item_data) {
    $_SESSION['error'] = "Item not found!";
    header("Location: dashboard.php");
    exit;
}

// Check if user has permission to view this item
$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Only admin or item owner can view the item
if ($user_role !== 'admin' && $item_data['user_id'] != $user_id) {
    $_SESSION['error'] = "You are not authorized to view this item!";
    header("Location: dashboard.php");
    exit;
}

include "../includes/header.php";
?>

<div class="dashboard">
    <!-- Item Header -->
    <div class="admin-header">
        <h2>Item Details</h2>
        <p>Complete information about the reported item</p>
    </div>

    <div class="item-detail-view">
        <!-- Item Images -->
        <div class="item-image-section">
            <?php if (!empty($item_data['image'])): ?>
                <img src="../assets/images/<?php echo htmlspecialchars($item_data['image']); ?>" 
                     alt="Item Image" 
                     class="item-detail-image">
            <?php else: ?>
                <img src="../assets/images/no-image.png" 
                     alt="No Image" 
                     class="item-detail-image">
            <?php endif; ?>
        </div>

        <!-- Item Information -->
        <div class="item-info-section">
            <div class="item-header-detail">
                <h1><?php echo htmlspecialchars($item_data['item_name']); ?></h1>
                <div class="item-status-badge-large status-<?php echo $item_data['status_admin']; ?>">
                    <?php echo ucfirst($item_data['status_admin']); ?>
                </div>
            </div>

            <div class="item-meta">
                <span class="item-category-badge"><?php echo htmlspecialchars($item_data['category']); ?></span>
                <span class="item-type-badge status <?php echo $item_data['status']; ?>">
                    <?php echo ucfirst($item_data['status']); ?> Item
                </span>
            </div>

            <!-- Basic Information -->
            <div class="info-card">
                <h3>📝 Basic Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Item Name:</label>
                        <span><?php echo htmlspecialchars($item_data['item_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Category:</label>
                        <span><?php echo htmlspecialchars($item_data['category']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <span class="status <?php echo $item_data['status']; ?>">
                            <?php echo ucfirst($item_data['status']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Admin Status:</label>
                        <span class="status-<?php echo $item_data['status_admin']; ?>">
                            <?php echo ucfirst($item_data['status_admin']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Location & Time -->
            <div class="info-card">
                <h3>📍 Location & Time</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Location:</label>
                        <span><?php echo htmlspecialchars($item_data['location']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Date:</label>
                        <span><?php echo htmlspecialchars($item_data['date_lost']); ?></span>
                    </div>
                    <?php if (!empty($item_data['return_location'])): ?>
                    <div class="info-item">
                        <label>Return Location:</label>
                        <span class="return-info"><?php echo htmlspecialchars($item_data['return_location']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($item_data['contact_info'])): ?>
                    <div class="info-item">
                        <label>Contact Info:</label>
                        <span class="contact-info"><?php echo htmlspecialchars($item_data['contact_info']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="info-card">
                <h3>📋 Description</h3>
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($item_data['description'])); ?>
                </div>
            </div>

            <!-- Owner Information -->
            <div class="info-card">
                <h3>👤 Reported By</h3>
                <div class="owner-info-detail">
                    <div class="owner-avatar">
                        <?php echo strtoupper(substr($item_data['user_name'], 0, 2)); ?>
                    </div>
                    <div class="owner-details">
                        <h4><?php echo htmlspecialchars($item_data['user_name']); ?></h4>
                        <p><?php echo htmlspecialchars($item_data['user_email']); ?></p>
                        <small>User ID: <?php echo $item_data['user_id']; ?></small>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons-detail">
                <?php if ($user_role === 'admin'): ?>
                    <!-- Admin Actions -->
                    <div class="admin-actions">
                        <h4>Admin Actions</h4>
                        <div class="action-buttons">
                            <?php if ($item_data['status_admin'] === 'pending'): ?>
                                <a href="admin_panel.php?approve=<?php echo $item_id; ?>" class="btn btn-success">
                                    <i>✅</i> Approve Item
                                </a>
                                <a href="admin_panel.php?reject=<?php echo $item_id; ?>" class="btn btn-danger">
                                    <i>❌</i> Reject Item
                                </a>
                            <?php elseif ($item_data['status_admin'] === 'approved'): ?>
                                <span class="btn btn-success" style="cursor: default;">
                                    <i>✅</i> Approved
                                </span>
                            <?php elseif ($item_data['status_admin'] === 'returned'): ?>
                                <span class="btn btn-info" style="cursor: default;">
                                    <i>✅</i> Returned to Owner
                                </span>
                            <?php endif; ?>

                            <!-- Mark as Returned Form -->
                            <?php if ($item_data['status_admin'] !== 'returned'): ?>
                                <form method="POST" action="admin_panel.php" class="return-form-inline">
                                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                    <div class="form-row">
                                        <input type="text" name="return_location" placeholder="Pickup Location" required>
                                        <input type="text" name="contact_info" placeholder="Contact Information" required>
                                        <button type="submit" name="mark_returned" class="btn btn-info btn-sm">
                                            <i>🚚</i> Mark as Returned
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Common Actions -->
                <div class="common-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i>←</i> Back to Dashboard
                    </a>
                    
                    <?php if ($user_role === 'admin' || $item_data['user_id'] == $user_id): ?>
                        <a href="delete_item.php?id=<?php echo $item_id; ?>" 
                           onclick="return confirm('Are you sure you want to delete this item?');" 
                           class="btn btn-danger">
                            <i>🗑️</i> Delete Item
                        </a>
                    <?php endif; ?>

                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin_panel.php" class="btn btn-outline">
                            <i>⚙️</i> Admin Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.item-detail-view {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.item-image-section {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    height: fit-content;
}

.item-detail-image {
    width: 100%;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.item-info-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.item-header-detail {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
}

.item-header-detail h1 {
    font-size: 2rem;
    color: var(--text-dark);
    margin: 0;
    flex: 1;
}

.item-status-badge-large {
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background: #fef3c7; color: var(--warning); }
.status-approved { background: #dcfce7; color: var(--success); }
.status-rejected { background: #fee2e2; color: var(--danger); }
.status-returned { background: #e0f2fe; color: var(--info); }

.item-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.item-category-badge {
    background: var(--primary-light);
    color: var(--primary);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.item-type-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.info-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
}

.info-card h3 {
    margin: 0 0 1rem 0;
    color: var(--text-dark);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-weight: 600;
    color: var(--text-light);
    font-size: 0.9rem;
}

.info-item span {
    color: var(--text-dark);
    font-weight: 500;
}

.description-content {
    line-height: 1.6;
    color: var(--text-dark);
    background: var(--surface-alt);
    padding: 1rem;
    border-radius: var(--radius);
    border-left: 4px solid var(--primary);
}

.owner-info-detail {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--surface-alt);
    border-radius: var(--radius);
}

.owner-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1.2rem;
}

.owner-details h4 {
    margin: 0 0 0.25rem 0;
    color: var(--text-dark);
}

.owner-details p {
    margin: 0 0 0.25rem 0;
    color: var(--text-light);
}

.owner-details small {
    color: var(--text-lighter);
}

.action-buttons-detail {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.admin-actions {
    background: var(--surface-alt);
    padding: 1.5rem;
    border-radius: var(--radius);
    border-left: 4px solid var(--primary);
}

.admin-actions h4 {
    margin: 0 0 1rem 0;
    color: var(--text-dark);
}

.common-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.return-form-inline {
    margin-top: 1rem;
}

.form-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.form-row input {
    flex: 1;
    min-width: 150px;
    padding: 0.5rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.return-info, .contact-info {
    background: var(--success-light);
    color: var(--success);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
}

@media (max-width: 968px) {
    .item-detail-view {
        grid-template-columns: 1fr;
    }
    
    .item-image-section {
        text-align: center;
    }
    
    .item-detail-image {
        max-width: 400px;
    }
}

@media (max-width: 768px) {
    .item-header-detail {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .common-actions {
        flex-direction: column;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .form-row input {
        min-width: auto;
    }
}
</style>

<?php include "../includes/footer.php"; ?>