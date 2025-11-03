<?php
require_once "../classes/Item.php";
require_once "../config.php";

if (!isset($_SESSION)) session_start();

// Must be logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Validate item ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: dashboard.php");
    exit;
}

$itemId = intval($_GET['id']);
$itemObj = new Item();

// Get item to check ownership
$itemData = $itemObj->getItemById($itemId);

if (!$itemData) {
    $_SESSION['error'] = "Item not found!";
    header("Location: dashboard.php");
    exit;
}

// Role-based delete check - USER CAN ONLY DELETE THEIR OWN ITEMS
if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['id'] != $itemData['user_id']) {
    $_SESSION['error'] = "You are not authorized to delete this item!";
    header("Location: dashboard.php");
    exit;
}

// Delete image if exists
$imagePath = "../assets/images/" . $itemData['image'];
if (!empty($itemData['image']) && file_exists($imagePath)) {
    unlink($imagePath);
}

// Delete item from database
$deleted = $itemObj->deleteItem($itemId);

if ($deleted) {
    $_SESSION['success'] = "Item deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete item.";
}

header("Location: dashboard.php");
exit;
?>