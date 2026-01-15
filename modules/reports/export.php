<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_auth();

$type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

switch ($type) {
    case 'sales':
        $query = "SELECT s.invoice_number, s.sale_date, CONCAT(c.first_name, ' ', c.last_name) as customer, 
                  CONCAT(v.year, ' ', v.make, ' ', v.model) as vehicle, s.sale_price, s.down_payment, 
                  s.balance, s.payment_status
                  FROM sales s
                  JOIN customers c ON s.customer_id = c.id
                  JOIN vehicles v ON s.vehicle_id = v.id
                  WHERE YEAR(s.sale_date) = {$year}
                  ORDER BY s.sale_date DESC";
        
        $result = $conn->query($query);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                $row['invoice_number'],
                $row['sale_date'],
                $row['customer'],
                $row['vehicle'],
                $row['sale_price'],
                $row['down_payment'],
                $row['balance'],
                $row['payment_status']
            ];
        }
        
        export_to_csv("sales_{$year}.csv", $data, [
            'Invoice #', 'Sale Date', 'Customer', 'Vehicle', 'Sale Price', 'Down Payment', 'Balance', 'Status'
        ]);
        break;
        
    case 'inventory':
        $query = "SELECT stock_number, make, model, year, color, transmission, fuel_type, mileage, 
                  selling_price, status, COALESCE(l.name, 'N/A') as location
                  FROM vehicles v
                  LEFT JOIN locations l ON v.location_id = l.id
                  ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                $row['stock_number'],
                $row['make'],
                $row['model'],
                $row['year'],
                $row['color'],
                $row['transmission'],
                $row['fuel_type'],
                $row['mileage'],
                $row['selling_price'],
                $row['status'],
                $row['location']
            ];
        }
        
        export_to_csv("inventory_" . date('Y-m-d') . ".csv", $data, [
            'Stock #', 'Make', 'Model', 'Year', 'Color', 'Transmission', 'Fuel Type', 'Mileage', 'Price', 'Status', 'Location'
        ]);
        break;
        
    case 'customers':
        $query = "SELECT first_name, middle_name, last_name, phone, alternate_phone, email, 
                  city, province, created_at
                  FROM customers
                  ORDER BY last_name, first_name";
        
        $result = $conn->query($query);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $name = $row['first_name'] . (!empty($row['middle_name']) ? ' ' . $row['middle_name'] : '') . ' ' . $row['last_name'];
            $data[] = [
                $name,
                $row['phone'],
                $row['alternate_phone'] ?: 'N/A',
                $row['email'] ?: 'N/A',
                $row['city'] ?: 'N/A',
                $row['province'] ?: 'N/A',
                $row['created_at']
            ];
        }
        
        export_to_csv("customers_" . date('Y-m-d') . ".csv", $data, [
            'Name', 'Phone', 'Alternate Phone', 'Email', 'City', 'Province', 'Date Added'
        ]);
        break;
        
    default:
        $_SESSION['error'] = 'Invalid export type.';
        header('Location: index.php');
        exit();
}

log_transaction($conn, 'EXPORT', $type, null, "Exported {$type} report");
?>
