<?php
// POST update_order_status.php   (header: X-Auth-Token: <token>)
// Body: { "order_id": 12, "status": "processing" }
//
// Moves an order ONE step forward along:
//   pending / paid  ->  processing  ->  preparing  ->  shipped  ->  delivered
// Anything else (skipping a step, going backwards, cancelled/delivered orders) is refused.
//
// NOTE: 'preparing' must exist in the orders.status ENUM - run sql/add_preparing_status.sql once.
require_once 'db_connect.php';
require_once 'auth.php';

require_admin($conn);

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$order_id = intval($data['order_id'] ?? 0);
$new_status = strtolower(trim((string)($data['status'] ?? '')));

if ($order_id <= 0 || $new_status === '') {
    echo json_encode(['success' => false, 'message' => 'order_id and status are required']);
    exit;
}

$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    $conn->close();
    exit;
}

$current = strtolower((string)$row['status']);
$next_of = [
    'pending'    => 'processing',
    'paid'       => 'processing',
    'processing' => 'preparing',
    'preparing'  => 'shipped',
    'shipped'    => 'delivered',
];

if (($next_of[$current] ?? null) !== $new_status) {
    echo json_encode([
        'success' => false,
        'message' => "Can't move an order from '$current' to '$new_status'",
    ]);
    $conn->close();
    exit;
}

// "AND status = ?" stops two people changing the same order at the same moment.
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND status = ?");
$stmt->bind_param("sis", $new_status, $order_id, $row['status']);
$stmt->execute();
$changed = $stmt->affected_rows > 0;
$stmt->close();
$conn->close();

if (!$changed) {
    echo json_encode(['success' => false, 'message' => 'Order was changed by someone else. Refresh and try again.']);
    exit;
}

echo json_encode(['success' => true, 'order_id' => $order_id, 'status' => $new_status]);
