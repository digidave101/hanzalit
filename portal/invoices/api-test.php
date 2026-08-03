<?php
error_reporting(0);
$db_php = realpath(__DIR__ . '/../quotation/db.php');
if ($db_php) require_once $db_php;
header('Content-Type: application/json');
try {
    $pdo = getDB();
    $row = $pdo->query("SELECT id, inv_number, line_items FROM ti_invoices WHERE id=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $raw = $row['line_items'];
    $parsed = json_decode($raw, true);
    echo json_encode([
        'inv_number' => $row['inv_number'],
        'line_items_type' => gettype($raw),
        'line_items_length' => strlen($raw ?? ''),
        'line_items_first100' => substr($raw ?? '', 0, 100),
        'parsed_type' => gettype($parsed),
        'parsed_is_array' => is_array($parsed),
        'first_item' => is_array($parsed) ? $parsed[0] : null,
        'item_count' => is_array($parsed) ? count($parsed) : 0,
    ], JSON_PRETTY_PRINT);
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
