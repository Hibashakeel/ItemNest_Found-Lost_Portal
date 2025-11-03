<?php
require_once "Database.php";

class Item extends Database {

    // ✅ Add Item (with image upload + notifications to admin)
    public function addItem($user_id, $name, $category, $location, $date, $desc, $imageFile, $status) {
        $conn = $this->connect();
        $uploadDir = __DIR__ . '/../assets/images/';

        // Create images directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // --- Image Upload Handling ---
        $imageName = null;
        if (isset($imageFile['name']) && $imageFile['name'] != '') {
            $ext = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed)) {
                $newName = 'item_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target = $uploadDir . $newName;
                if (move_uploaded_file($imageFile['tmp_name'], $target)) {
                    $imageName = $newName;
                }
            }
        }

        // --- Insert item into database ---
        $stmt = $conn->prepare("INSERT INTO items (user_id, item_name, category, location, date_lost, description, image, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $name, $category, $location, $date, $desc, $imageName, $status);
        $res = $stmt->execute();
        if (!$res) {
            $stmt->close();
            return false;
        }
        $new_item_id = $stmt->insert_id;
        $stmt->close();

        // --- Notify Admin about new item report using new method ---
        $notification = new Notification();
        $notification->notifyAdminsAboutNewItem($new_item_id, $name, $status);

        // --- Enhanced Notification matching logic ---
        $opp_status = ($status === 'lost') ? 'found' : 'lost';
        $stmt2 = $conn->prepare("SELECT id, user_id, item_name, location FROM items WHERE LOWER(item_name)=LOWER(?) AND status=? AND id<>?");
        $stmt2->bind_param("ssi", $name, $opp_status, $new_item_id);
        $stmt2->execute();
        $matches = $stmt2->get_result();
        $stmt2->close();

        while ($m = $matches->fetch_assoc()) {
            // Notify the owner of matched item
            $notification->notifyAboutItemMatch($m['user_id'], $new_item_id, $name, $location, $status);
            
            // Notify the current user about the match
            $notification->notifyAboutItemMatch($user_id, $m['id'], $m['item_name'], $m['location'], $opp_status);
        }

        return true;
    }

    // ✅ Fetch all items with user information (SIRF CURRENT USER KE ITEMS)
    public function getUserItems($user_id) {
        $conn = $this->connect();
        $sql = "SELECT i.*, u.name as user_name, u.email as user_email 
                FROM items i 
                LEFT JOIN users u ON i.user_id = u.id 
                WHERE i.user_id = ?
                ORDER BY i.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    // ✅ Fetch all items for ADMIN only
    public function getAllItems() {
        $conn = $this->connect();
        $sql = "SELECT i.*, u.name as user_name, u.email as user_email 
                FROM items i 
                LEFT JOIN users u ON i.user_id = u.id 
                ORDER BY i.id DESC";
        $result = $conn->query($sql);
        return $result;
    }

    // ✅ Fetch a single item by ID with user information
    public function getItem($id) {
        $conn = $this->connect();
        $stmt = $conn->prepare("SELECT i.*, u.name as user_name, u.email as user_email 
                               FROM items i 
                               LEFT JOIN users u ON i.user_id = u.id 
                               WHERE i.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $item = $res->fetch_assoc();
        $stmt->close();
        return $item;
    }

    public function getItemById($id) {
        $conn = $this->connect();
        $sql = "SELECT * FROM items WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function deleteItem($id) {
        $conn = $this->connect();
        
        // Fetch image filename to delete
        $stmt1 = $conn->prepare("SELECT image FROM items WHERE id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $result = $stmt1->get_result();
        $row = $result->fetch_assoc();
        $stmt1->close();

        // Delete image file from folder
        if ($row && !empty($row['image'])) {
            $imgPath = __DIR__ . '/../assets/images/' . $row['image'];
            if (file_exists($imgPath)) {
                unlink($imgPath);
            }
        }

        // Delete item record
        $stmt2 = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        return true;
    }

    // ✅ Get user-specific stats
    public function getUserStats($user_id) {
        $conn = $this->connect();
        
        $stats = [
            'lost' => 0,
            'found' => 0,
            'pending' => 0,
            'returned' => 0
        ];

        // Count lost items
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND status = 'lost'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['lost'] = $result->fetch_assoc()['count'];
        $stmt->close();

        // Count found items
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND status = 'found'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['found'] = $result->fetch_assoc()['count'];
        $stmt->close();

        // Count pending items (status_admin = 'pending')
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND status_admin = 'pending'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending'] = $result->fetch_assoc()['count'];
        $stmt->close();

        // Count returned items (status_admin = 'returned')
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM items WHERE user_id = ? AND status_admin = 'returned'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['returned'] = $result->fetch_assoc()['count'];
        $stmt->close();

        return $stats;
    }
}
?>