<?php
// GET get_orders.php   (header: X-Auth-Token: <token from login.php>)
// Returns EVERY order from every customer, newest first, with each order's
// line items included - so the app can show everything in one request.
require_once 'db_connect.php';
require_once 'auth.php';

require_admin($conn);

// SELECT * so an `email` column (if your orders table has one) reaches the app's Home cards.
$res = $conn->query("SELECT * FROM orders ORDER BY created_at DESC, id DESC");

$orders = [];
$index = []; // order id => position in $orders
while ($row = $res->fetch_assoc()) {
    $row['items'] = [];
    $index[$row['id']] = count($orders);
    $orders[] = $row;
}
$res->free();

$res = $conn->query(
    "SELECT order_id, product_name, unit_price, quantity
     FROM order_items
     ORDER BY id"
);
while ($item = $res->fetch_assoc()) {
    if (isset($index[$item['order_id']])) {
        $orders[$index[$item['order_id']]]['items'][] = [
            'product_name' => $item['product_name'],
            'unit_price'   => $item['unit_price'],
            'quantity'     => $item['quantity'],
        ];
    }
}
$res->free();

echo json_encode(['success' => true, 'count' => count($orders), 'orders' => $orders]);

$conn->close();
