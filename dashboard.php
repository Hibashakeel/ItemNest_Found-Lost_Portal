<?php
require_once "../classes/Item.php";
require_once "../classes/Notification.php";
require_once "../config.php";

if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$item = new Item();
$notification = new Notification();

// Get user-specific data
$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];

// Admin can see all items, regular users only see their own items
if ($user_role === 'admin') {
    $items = $item->getAllItems();
} else {
    $items = $item->getUserItems($user_id);
}

$user_notifications = $notification->getForUser($user_id);
$unread_count = $notification->unreadCount($user_id);

// Get user-specific stats
$user_stats = $item->getUserStats($user_id);

include "../includes/header.php";
?>

<div class="dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>! 👋</h2>
        <p>
            <?php if ($user_role === 'admin'): ?>
                Manage all lost and found items in the system
            <?php else: ?>
                Manage your lost and found items in one place
            <?php endif; ?>
        </p>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="action-card">
            <div class="action-icon">🔍</div>
            <h3>Report Lost Item</h3>
            <p>Can't find something? Report it as lost</p>
            <a href="report_lost.php" class="btn">Report Lost</a>
        </div>
        
        <div class="action-card">
            <div class="action-icon">✅</div>
            <h3>Report Found Item</h3>
            <p>Found someone's item? Report it here</p>
            <a href="report_found.php" class="btn">Report Found</a>
        </div>
        
        <div class="action-card">
            <div class="action-icon">🔔</div>
            <h3>Notifications</h3>
            <p>You have <?php echo $unread_count; ?> unread notifications</p>
            <a href="notifications.php" class="btn">View Notifications</a>
        </div>
    </div>

    <!-- User Stats -->
    <div class="user-stats">
        <div class="user-stat-card lost">
            <div class="user-stat-icon">🔍</div>
            <div class="user-stat-number"><?php echo $user_stats['lost']; ?></div>
            <div class="user-stat-label">Lost Items</div>
        </div>
        
        <div class="user-stat-card found">
            <div class="user-stat-icon">✅</div>
            <div class="user-stat-number"><?php echo $user_stats['found']; ?></div>
            <div class="user-stat-label">Found Items</div>
        </div>
        
        <div class="user-stat-card pending">
            <div class="user-stat-icon">⏳</div>
            <div class="user-stat-number"><?php echo $user_stats['pending']; ?></div>
            <div class="user-stat-label">Pending</div>
        </div>
        
        <div class="user-stat-card returned">
            <div class="user-stat-icon">🎉</div>
            <div class="user-stat-number"><?php echo $user_stats['returned']; ?></div>
            <div class="user-stat-label">Returned</div>
        </div>
    </div>

    <!-- Dashboard Tabs -->
    <div class="dashboard-tabs">
        <?php if ($user_role === 'admin'): ?>
            <button class="tab-btn active" onclick="switchTab('all-items')">All Items</button>
        <?php else: ?>
            <button class="tab-btn active" onclick="switchTab('my-items')">My Items</button>
        <?php endif; ?>
        <button class="tab-btn" onclick="switchTab('recent-activity')">Recent Activity</button>
    </div>

    <!-- Items Tab -->
    <?php if ($user_role === 'admin'): ?>
        <!-- Admin View - All Items -->
        <div id="all-items" class="tab-content active">
            <div class="search-filter-bar">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" placeholder="Search items..." id="searchInput">
                </div>
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="lost">Lost</option>
                    <option value="found">Found</option>
                    <option value="returned">Returned</option>
                </select>
                <select class="filter-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="electronics">Electronics</option>
                    <option value="bags">Bags</option>
                    <option value="documents">Documents</option>
                </select>
            </div>

            <?php if ($items->num_rows > 0): ?>
                <div class="dashboard-item-grid">
                    <?php while($row = $items->fetch_assoc()): ?>
                        <div class="dashboard-item-card" data-status="<?php echo $row['status']; ?>" data-category="<?php echo strtolower($row['category']); ?>">
                            <!-- Show image if available -->
                            <?php if (!empty($row['image'])): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>" 
                                     alt="Item Image" 
                                     class="dashboard-item-img">
                            <?php else: ?>
                                <img src="../assets/images/no-image.png" 
                                     alt="No Image" 
                                     class="dashboard-item-img">
                            <?php endif; ?>

                            <div class="dashboard-item-content">
                                <div class="dashboard-item-header">
                                    <div class="dashboard-item-title">
                                        <h4><?php echo htmlspecialchars($row['item_name']); ?></h4>
                                        <span class="dashboard-item-category"><?php echo htmlspecialchars($row['category']); ?></span>
                                    </div>
                                    <div class="dashboard-item-status status <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </div>
                                </div>

                                <div class="dashboard-item-details">
                                    <div class="dashboard-item-detail">
                                        <span>📍</span>
                                        <span><?php echo htmlspecialchars($row['location']); ?></span>
                                    </div>
                                    <div class="dashboard-item-detail">
                                        <span>📅</span>
                                        <span><?php echo htmlspecialchars($row['date_lost']); ?></span>
                                    </div>
                                    <div class="dashboard-item-detail">
                                        <span>👤</span>
                                        <span><?php echo htmlspecialchars($row['user_name']); ?></span>
                                    </div>
                                </div>

                                <p style="color: var(--text-light); font-size: 0.9rem; margin: 1rem 0;">
                                    <?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>...
                                </p>

                                <!-- Delete option: Admin can delete all, user can delete own -->
                                <?php if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['id'] == $row['user_id']): ?>
                                    <div class="dashboard-item-actions">
                                        <a href="delete_item.php?id=<?php echo intval($row['id']); ?>" 
                                          onclick="return confirm('Are you sure you want to delete this item?');" 
                                          class="btn btn-danger btn-sm">
                                          🗑️ Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-illustration">📦</div>
                    <h4>No Items Found</h4>
                    <p>There are no items reported yet. Be the first to report a lost or found item!</p>
                    <a href="report_lost.php" class="btn">Report Lost Item</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Regular User View - My Items -->
        <div id="my-items" class="tab-content active">
            <?php if ($items->num_rows > 0): ?>
                <div class="dashboard-item-grid">
                    <?php while($row = $items->fetch_assoc()): ?>
                        <div class="dashboard-item-card" data-status="<?php echo $row['status']; ?>" data-category="<?php echo strtolower($row['category']); ?>">
                            <!-- Show image if available -->
                            <?php if (!empty($row['image'])): ?>
                                <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>" 
                                     alt="Item Image" 
                                     class="dashboard-item-img">
                            <?php else: ?>
                                <img src="../assets/images/no-image.png" 
                                     alt="No Image" 
                                     class="dashboard-item-img">
                            <?php endif; ?>

                            <div class="dashboard-item-content">
                                <div class="dashboard-item-header">
                                    <div class="dashboard-item-title">
                                        <h4><?php echo htmlspecialchars($row['item_name']); ?></h4>
                                        <span class="dashboard-item-category"><?php echo htmlspecialchars($row['category']); ?></span>
                                    </div>
                                    <div class="dashboard-item-status status <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </div>
                                </div>

                                <div class="dashboard-item-details">
                                    <div class="dashboard-item-detail">
                                        <span>📍</span>
                                        <span><?php echo htmlspecialchars($row['location']); ?></span>
                                    </div>
                                    <div class="dashboard-item-detail">
                                        <span>📅</span>
                                        <span><?php echo htmlspecialchars($row['date_lost']); ?></span>
                                    </div>
                                    <div class="dashboard-item-detail">
                                        <span>🏷️</span>
                                        <span>Admin Status: <strong><?php echo ucfirst($row['status_admin']); ?></strong></span>
                                    </div>
                                </div>

                                <p style="color: var(--text-light); font-size: 0.9rem; margin: 1rem 0;">
                                    <?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>...
                                </p>

                                <div class="dashboard-item-actions">
                                    <a href="delete_item.php?id=<?php echo intval($row['id']); ?>" 
                                      onclick="return confirm('Are you sure you want to delete this item?');" 
                                      class="btn btn-danger btn-sm">
                                      🗑️ Delete
                                    </a>
                                    
                                    <?php if ($row['status_admin'] === 'returned' && !empty($row['return_location'])): ?>
                                        <span class="btn btn-success btn-sm">
                                            ✅ Ready for Pickup: <?php echo htmlspecialchars($row['return_location']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-illustration">📦</div>
                    <h4>No Items Reported Yet</h4>
                    <p>You haven't reported any lost or found items yet. Start by reporting your first item!</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                        <a href="report_lost.php" class="btn">Report Lost Item</a>
                        <a href="report_found.php" class="btn btn-secondary">Report Found Item</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Recent Activity Tab -->
    <div id="recent-activity" class="tab-content">
        <div class="recent-activity">
            <div class="activity-header">
                <h3>Recent Activity</h3>
                <a href="notifications.php" class="btn btn-outline">View All Notifications</a>
            </div>
            <div class="activity-list">
                <?php if ($user_notifications->num_rows > 0): ?>
                    <?php 
                    $count = 0;
                    // Reset pointer to beginning
                    $user_notifications->data_seek(0);
                    while($note = $user_notifications->fetch_assoc()): 
                        if ($count >= 5) break; // Show only 5 recent activities
                    ?>
                        <div class="activity-item">
                            <div class="activity-icon">🔔</div>
                            <div class="activity-content">
                                <p><?php echo htmlspecialchars($note['message']); ?></p>
                                <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php 
                        $count++;
                    endwhile; 
                    ?>
                <?php else: ?>
                    <div class="empty-state" style="padding: 2rem;">
                        <div class="empty-illustration">📝</div>
                        <h4>No Recent Activity</h4>
                        <p>Your recent notifications and activities will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked tab button
    event.currentTarget.classList.add('active');
}

// Search and filter functionality (only for admin view)
<?php if ($user_role === 'admin'): ?>
document.getElementById('searchInput').addEventListener('input', filterItems);
document.getElementById('statusFilter').addEventListener('change', filterItems);
document.getElementById('categoryFilter').addEventListener('change', filterItems);

function filterItems() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    
    document.querySelectorAll('.dashboard-item-card').forEach(card => {
        const itemName = card.querySelector('h4').textContent.toLowerCase();
        const itemStatus = card.getAttribute('data-status');
        const itemCategory = card.getAttribute('data-category');
        
        const matchesSearch = itemName.includes(searchTerm);
        const matchesStatus = !statusFilter || itemStatus === statusFilter;
        const matchesCategory = !categoryFilter || itemCategory === categoryFilter.toLowerCase();
        
        if (matchesSearch && matchesStatus && matchesCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
<?php endif; ?>
</script>

<?php include "../includes/footer.php"; ?>