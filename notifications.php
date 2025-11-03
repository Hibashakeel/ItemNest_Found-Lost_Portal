<?php
require_once __DIR__ . "/../config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php"); 
    exit;
}

$notif = new Notification();

if (isset($_GET['mark']) && is_numeric($_GET['mark'])) {
    $notif->markRead((int)$_GET['mark']);
    header("Location: notifications.php"); 
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $conn = $notif->connect();
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit;
}

// Clear all notifications
if (isset($_GET['clear_all'])) {
    $conn = $notif->connect();
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id=?");
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit;
}

$notes = $notif->getForUser($_SESSION['user']['id']);
$unread_count = $notif->unreadCount($_SESSION['user']['id']);
$total_count = $notes->num_rows;

include __DIR__ . "/../includes/header.php";
?>

<div class="notifications-page">
    <!-- Notifications Header -->
    <div class="notifications-header">
        <h2>Notifications 🔔</h2>
        <p>Stay updated with your item status and matches</p>
    </div>

    <!-- Notification Stats -->
    <div class="notification-stats">
        <div class="notification-stat-card unread">
            <div class="notification-stat-icon">📥</div>
            <div class="notification-stat-number"><?php echo $unread_count; ?></div>
            <div class="notification-stat-label">Unread Notifications</div>
        </div>
        
        <div class="notification-stat-card total">
            <div class="notification-stat-icon">📋</div>
            <div class="notification-stat-number"><?php echo $total_count; ?></div>
            <div class="notification-stat-label">Total Notifications</div>
        </div>
        
        <div class="notification-stat-card cleared">
            <div class="notification-stat-icon">✅</div>
            <div class="notification-stat-number"><?php echo $total_count - $unread_count; ?></div>
            <div class="notification-stat-label">Read Notifications</div>
        </div>
    </div>

    <!-- Notification Actions -->
    <div class="notification-actions">
        <div class="action-group">
            <div class="filter-buttons">
                <button class="filter-btn active" onclick="filterNotifications('all')">All</button>
                <button class="filter-btn" onclick="filterNotifications('unread')">Unread</button>
                <button class="filter-btn" onclick="filterNotifications('read')">Read</button>
            </div>
        </div>
        
        <div class="action-group">
            <a href="?mark_all_read=1" class="btn btn-success btn-sm" onclick="return confirm('Mark all notifications as read?')">
                <i>✅</i> Mark All Read
            </a>
            <a href="?clear_all=1" class="btn btn-danger btn-sm" onclick="return confirm('Clear all notifications? This action cannot be undone.')">
                <i>🗑️</i> Clear All
            </a>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notifications-list">
        <?php if ($notes->num_rows == 0): ?>
            <div class="empty-notifications">
                <div class="empty-notifications-illustration">📭</div>
                <h4>No Notifications Yet</h4>
                <p>You're all caught up! New notifications about your items will appear here.</p>
                <a href="dashboard.php" class="btn">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <?php while($n = $notes->fetch_assoc()): 
                $status_class = $n['is_read'] == 0 ? 'unread' : 'read';
                $type_class = '';
                $icon = '🔔';
                
                // Determine notification type based on admin decision or content
                if ($n['admin_decision'] === 'approved') {
                    $type_class = 'approved';
                    $icon = '✅';
                } elseif ($n['admin_decision'] === 'rejected') {
                    $type_class = 'rejected';
                    $icon = '❌';
                } elseif ($n['admin_decision'] === 'returned') {
                    $type_class = 'returned';
                    $icon = '🎉';
                } elseif (strpos($n['message'], 'match') !== false) {
                    $type_class = 'match';
                    $icon = '🔍';
                }
            ?>
                <div class="notification-card <?php echo $status_class; ?> <?php echo $type_class; ?>" data-status="<?php echo $status_class; ?>">
                    <div class="notification-header">
                        <div class="notification-icon">
                            <?php echo $icon; ?>
                        </div>
                        
                        <div class="notification-content">
                            <div class="notification-title">
                                <h4>
                                    <?php 
                                        if ($type_class === 'approved') echo 'Item Approved';
                                        elseif ($type_class === 'rejected') echo 'Item Rejected';
                                        elseif ($type_class === 'returned') echo 'Item Returned';
                                        elseif ($type_class === 'match') echo 'Potential Match';
                                        else echo 'Notification';
                                    ?>
                                </h4>
                                <?php if ($n['is_read'] == 0): ?>
                                    <span class="notification-badge unread">New</span>
                                <?php endif; ?>
                                <?php if ($type_class): ?>
                                    <span class="notification-badge <?php echo $type_class; ?>">
                                        <?php echo ucfirst($type_class); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="notification-message">
                                <?php echo htmlspecialchars($n['message']); ?>
                            </div>
                            
                            <?php if($n['admin_message']): ?>
                                <div class="admin-message">
                                    <div class="admin-message-header">
                                        <i>💬</i>
                                        <span>Admin Message</span>
                                    </div>
                                    <div class="admin-message-content">
                                        <?php echo htmlspecialchars($n['admin_message']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="notification-meta">
                                <div class="notification-time">
                                    <i>🕒</i>
                                    <span><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></span>
                                </div>
                                
                                <?php if($n['item_name']): ?>
                                    <div class="notification-item">
                                        <i>📦</i>
                                        <span><?php echo htmlspecialchars($n['item_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($n['is_read'] == 0): ?>
                        <div class="notification-actions-single">
                            <a href="notifications.php?mark=<?php echo $n['id']; ?>" class="btn btn-success btn-sm">
                                <i>✅</i> Mark as Read
                            </a>
                            <?php if($n['item_id']): ?>
                                <a href="view_item.php?id=<?php echo $n['item_id']; ?>" class="btn btn-outline btn-sm">
                                    <i>👁️</i> View Item
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterNotifications(status) {
    const notifications = document.querySelectorAll('.notification-card');
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    filterButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase().includes(status)) {
            btn.classList.add('active');
        }
    });
    
    // Filter notifications
    notifications.forEach(notification => {
        if (status === 'all') {
            notification.style.display = 'block';
        } else {
            const notificationStatus = notification.getAttribute('data-status');
            if (notificationStatus === status) {
                notification.style.display = 'block';
            } else {
                notification.style.display = 'none';
            }
        }
    });
}

// Auto-mark as read when notification is viewed for 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const unreadNotifications = document.querySelectorAll('.notification-card.unread');
    
    unreadNotifications.forEach(notification => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        const markLink = notification.querySelector('a[href*="mark="]');
                        if (markLink) {
                            // You could automatically mark as read here
                            // window.location.href = markLink.href;
                        }
                    }, 3000);
                }
            });
        });
        
        observer.observe(notification);
    });
});
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>