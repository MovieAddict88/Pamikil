<?php
/**
 * Export Sales to CSV
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager']);

$db = getDb();

$status = sanitize($_GET['status'] ?? '');
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "s.status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

$sql = "
    SELECT 
        s.id as invoice_id,
        s.sale_date,
        s.status,
        s.financing_type,
        s.sale_price,
        s.down_payment,
        v.stock_number,
        v.make,
        v.model,
        v.year,
        v.plate_number,
        c.first_name,
        c.last_name,
        c.phone as customer_phone,
        u.full_name as sales_agent
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN customers c ON s.customer_id = c.id
    JOIN users u ON s.sales_agent_id = u.id
    WHERE $whereClause
    ORDER BY s.sale_date DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$csvData = [];
foreach ($sales as $sale) {
    $csvData[] = [
        str_pad($sale['invoice_id'], 6, '0', STR_PAD_LEFT),
        formatDate($sale['sale_date'], 'Y-m-d'),
        ucfirst($sale['status']),
        ucfirst(str_replace('_', ' ', $sale['financing_type'])),
        number_format($sale['sale_price'], 2),
        number_format($sale['down_payment'], 2),
        $sale['stock_number'],
        $sale['year'] . ' ' . $sale['make'] . ' ' . $sale['model'],
        $sale['plate_number'] ?? 'N/A',
        $sale['last_name'] . ', ' . $sale['first_name'],
        $sale['customer_phone'],
        $sale['sales_agent']
    ];
}

exportToCSV($csvData, 'sales_' . date('Ymd_His') . '.csv', [
    'Invoice #', 'Sale Date', 'Status', 'Financing Type', 'Sale Price', 
    'Down Payment', 'Stock #', 'Vehicle', 'Plate #', 
    'Customer', 'Customer Phone', 'Sales Agent'
]);
