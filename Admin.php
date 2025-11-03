<?php
require_once "Database.php";

class Admin extends Database {

    public function getAllUsers() {
        $conn = $this->connect();
        return $conn->query("SELECT id, name, email, role, is_blocked FROM users ORDER BY id DESC");
    }

    public function toggleUserBlock($id, $block) {
        $conn = $this->connect();
        $stmt = $conn->prepare("UPDATE users SET is_blocked=? WHERE id=?");
        $stmt->bind_param("ii", $block, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function getAllItemsAdmin() {
        $conn = $this->connect();
        return $conn->query("SELECT i.*, u.name as owner_name, u.email as owner_email 
                             FROM items i 
                             LEFT JOIN users u ON i.user_id = u.id 
                             ORDER BY i.created_at DESC");
    }

    public function updateItemStatus($id, $status, $admin_message = null) {
        $conn = $this->connect();
        
        // First get item details for notification
        $item_stmt = $conn->prepare("SELECT user_id, item_name, status FROM items WHERE id=?");
        $item_stmt->bind_param("i", $id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $item = $item_result->fetch_assoc();
        $item_stmt->close();

        // Update item status
        $stmt = $conn->prepare("UPDATE items SET status_admin=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $res = $stmt->execute();
        $stmt->close();

        // Send notification to user about admin decision
        if ($res && $item) {
            $notification = new Notification();
            $message = "Your item '{$item['item_name']}' has been {$status} by admin.";
            if ($admin_message) {
                $message .= " Message: {$admin_message}";
            }
            $notification->create($item['user_id'], $id, $message, $status, $admin_message);
        }

        return $res;
    }

    public function markItemAsReturned($item_id, $return_location, $contact_info) {
        $conn = $this->connect();
        
        // Get item details
        $item_stmt = $conn->prepare("SELECT user_id, item_name FROM items WHERE id=?");
        $item_stmt->bind_param("i", $item_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $item = $item_result->fetch_assoc();
        $item_stmt->close();

        // Update item with return information
        $stmt = $conn->prepare("UPDATE items SET return_location=?, contact_info=?, status_admin='returned' WHERE id=?");
        $stmt->bind_param("ssi", $return_location, $contact_info, $item_id);
        $res = $stmt->execute();
        $stmt->close();

        // Send notification to user
        if ($res && $item) {
            $notification = new Notification();
            $message = "Great news! Your item '{$item['item_name']}' has been found and is ready for pickup at {$return_location}. Contact: {$contact_info}";
            $notification->create($item['user_id'], $item_id, $message, 'returned', "Item ready for pickup at {$return_location}");
        }

        return $res;
    }
}
?>