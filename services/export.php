<?php
/**
 * Export Services to CSV
 * Car Management System - Pamikil
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkSessionTimeout();
requireAnyRole(['admin', 'manager', 'staff']);

$db = getDb();

$status = sanitize($_GET['status'] ?? '');
$vehicleId = intval($_GET['vehicle_id'] ?? 0);
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "s.status = ?";
    $params[] = $status;
}

if ($vehicleId) {
    $where[] = "s.vehicle_id = ?";
    $params[] = $vehicleId;
}

$whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

$sql = "
    SELECT 
        s.id,
        s.service_date,
        s.service_type,
        s.status,
        s.description,
        s.performed_by,
        s.mechanic_name,
        s.labor_cost,
        s.parts_cost,
        s.total_cost,
        v.stock_number,
        v.make,
        v.model,
        v.year,
        v.plate_number
    FROM services s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE $whereClause
    ORDER BY s.service_date DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

$csvData = [];
foreach ($services as $service) {
    $csvData[] = [
        $service['id'],
        formatDate($service['service_date'], 'Y-m-d'),
        ucfirst(str_replace('_', ' ', $service['service_type'])),
        ucfirst($service['status']),
        $service['description'],
        $service['performed_by'],
        $service['mechanic_name'] ?? 'N/A',
        number_format($service['labor_cost'], 2),
        number_format($service['parts_cost'], 2),
        number_format($service['total_cost'], 2),
        $service['stock_number'],
        $service['year'] . ' ' . $service['make'] . ' ' . $service['model'],
        $service['plate_number'] ?? 'N/A'
    ];
}

exportToCSV($csvData, 'services_' . date('Ymd_His') . '.csv', [
    'Service ID', 'Service Date', 'Service Type', 'Status', 'Description',
    'Performed By', 'Mechanic', 'Labor Cost', 'Parts Cost', 'Total Cost',
    'Stock #', 'Vehicle', 'Plate #'
]);
