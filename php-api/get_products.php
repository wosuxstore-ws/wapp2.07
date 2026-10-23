<?php
// GET get_products.php   (header: X-Auth-Token: <token from login.php>)
//
// Optional query string:
//   ?limit=6            only the newest N products (default: all)
//   ?category=Mouse     only products of that category
//   ?status=all         include drafts too (default: published products only)
//
// Returns newest first. "price" is what the customer actually pays:
// the discounted price when there is one, otherwise the original price.
require_once 'db_connect.php';
require_once 'auth.php';

require_admin($conn);

$limit    = max(0, intval($_GET['limit'] ?? 0));
$category = trim((string)($_GET['category'] ?? ''));
$all      = (($_GET['status'] ?? '') === 'all');

$sql = "SELECT id, title, category, brand, sku, original_amount, discounted_amount,
               discount_percent, stock, front_image, status
        FROM products";

$where  = [];
$types  = '';
$params = [];

if (!$all) {
    $where[] = "status = 'published'";
}
if ($category !== '') {
    $where[]  = "category = ?";
    $types   .= 's';
    $params[] = $category;
}
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY created_at DESC, id DESC";

if ($limit > 0) {
    $sql     .= " LIMIT ?";
    $types   .= 'i';
    $params[] = $limit;
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
    $hasDiscount = $row['discounted_amount'] !== null && (float)$row['discounted_amount'] > 0;
    $row['price'] = $hasDiscount ? $row['discounted_amount'] : $row['original_amount'];
    $products[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'count' => count($products), 'products' => $products]);
