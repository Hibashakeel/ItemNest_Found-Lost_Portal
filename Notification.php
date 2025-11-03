<?php
require_once "Database.php";

class Notification extends Database {

    // create a new notification
    public function create($user_id, $item_id, $message, $admin_decision = null, $admin_message = null) {
        $conn = $this->connect();
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, item_id, message, admin_decision, admin_message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $item_id, $message, $admin_decision, $admin_message);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    // get notifications for a user
    public function getForUser($user_id) {
        $conn = $this->connect();
        $stmt = $conn->prepare("
            SELECT n.*, i.item_name, i.status as item_status 
            FROM notifications n 
            LEFT JOIN items i ON n.item_id = i.id 
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    // unread count for a user
    public function unreadCount($user_id) {
        $conn = $this->connect();
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res['c'] ?? 0;
    }

    // mark notification as read
    public function markRead($id) {
        $conn = $this->connect();
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=?");
        $stmt->bind_param("i", $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    // notify all admins about new item
    public function notifyAdminsAboutNewItem($item_id, $item_name, $status) {
        $conn = $this->connect();
        
        // Get all admin users
        $admins = $conn->query("SELECT id FROM users WHERE role='admin'");
        
        $count = 0;
        while ($admin = $admins->fetch_assoc()) {
            $message = "New {$status} item reported: '{$item_name}'. Please review and approve.";
            $this->create($admin['id'], $item_id, $message);
            $count++;
        }
        
        return $count;
    }

    // notify about item match
    public function notifyAboutItemMatch($user_id, $item_id, $matched_item_name, $matched_location, $match_type) {
        $message = "Potential match found! A {$match_type} item '{$matched_item_name}' was reported at {$matched_location} that matches your item.";
        return $this->create($user_id, $item_id, $message);
    }
}
?>