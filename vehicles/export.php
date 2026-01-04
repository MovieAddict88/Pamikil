<?php
/**
 * Export Vehicles to CSV
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$db = getDb();

// Get filters
$status = sanitize($_GET['status'] ?? '');

// Build query
$where = ["v.status != 'inactive'"];
$params = [];

if (!empty($status)) {
    $where[] = "v.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Get vehicles
$sql = "
    SELECT 
        v.stock_number, v.make, v.model, v.year, v.variant, v.color,
        v.body_type, v.fuel_type, v.transmission, v.engine_cc,
        v.plate_number, v.chassis_number, v.engine_number,
        v.purchase_price, v.selling_price, v.mileage, v.condition,
        v.status, v.date_acquired, l.name as location_name
    FROM vehicles v
    LEFT JOIN locations l ON v.location_id = l.id
    WHERE $whereClause
    ORDER BY v.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Prepare CSV data
$csvData = [];
foreach ($vehicles as $vehicle) {
    $csvData[] = [
        $vehicle['stock_number'],
        $vehicle['make'],
        $vehicle['model'],
        $vehicle['year'],
        $vehicle['variant'],
        $vehicle['color'],
        ucfirst(str_replace('_', ' ', $vehicle['body_type'])),
        ucfirst(str_replace('_', ' ', $vehicle['fuel_type'])),
        ucfirst(str_replace('_', ' ', $vehicle['transmission'])),
        $vehicle['engine_cc'],
        $vehicle['plate_number'],
        $vehicle['chassis_number'],
        $vehicle['engine_number'],
        $vehicle['purchase_price'],
        $vehicle['selling_price'],
        $vehicle['mileage'],
        ucfirst(str_replace('_', ' ', $vehicle['condition'])),
        ucfirst(str_replace('_', ' ', $vehicle['status'])),
        formatDate($vehicle['date_acquired'], 'Y-m-d'),
        $vehicle['location_name']
    ];
}

// Export
exportToCSV($csvData, 'vehicles_' . date('Ymd_His') . '.csv', [
    'Stock Number', 'Make', 'Model', 'Year', 'Variant', 'Color',
    'Body Type', 'Fuel Type', 'Transmission', 'Engine CC',
    'Plate Number', 'Chassis Number', 'Engine Number',
    'Purchase Price', 'Selling Price', 'Mileage', 'Condition',
    'Status', 'Date Acquired', 'Location'
]);
