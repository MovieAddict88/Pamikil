<?php
/**
 * Export Customers to CSV
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$db = getDb();

$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

$whereClause = !empty($where) ? implode(' AND ', $where) : "status != 'inactive'";

$sql = "
    SELECT 
        id,
        first_name,
        middle_name,
        last_name,
        email,
        phone,
        address,
        city,
        province,
        zip_code,
        identification_type,
        identification_number,
        status,
        DATE_FORMAT(created_at, '%Y-%m-%d') as created_date
    FROM customers
    WHERE $whereClause
    ORDER BY last_name, first_name ASC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$csvData = [];
foreach ($customers as $customer) {
    $csvData[] = [
        $customer['id'],
        $customer['last_name'] . ', ' . $customer['first_name'],
        $customer['middle_name'] ?? 'N/A',
        $customer['email'] ?? 'N/A',
        formatPHMobileNumber($customer['phone']),
        $customer['address'],
        $customer['city'],
        $customer['province'],
        $customer['zip_code'] ?? 'N/A',
        ucfirst(str_replace('_', ' ', $customer['identification_type'])) ?? 'N/A',
        $customer['identification_number'] ?? 'N/A',
        ucfirst($customer['status']),
        $customer['created_date']
    ];
}

exportToCSV($csvData, 'customers_' . date('Ymd_His') . '.csv', [
    'Customer ID', 'Name', 'Middle Name', 'Email', 'Phone',
    'Address', 'City', 'Province', 'ZIP Code',
    'ID Type', 'ID Number', 'Status', 'Created Date'
]);
