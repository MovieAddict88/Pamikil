<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = isset($_POST['car_id']) && $_POST['car_id'] !== '' ? (int)$_POST['car_id'] : null;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $car_id_value = $car_id ? $car_id : 'NULL';
    $query = "INSERT INTO inquiries (car_id, name, email, phone, message) 
              VALUES ($car_id_value, '$name', '$email', '$phone', '$message')";
    
    if (mysqli_query($conn, $query)) {
        $success_message = urlencode('Thank you for your inquiry! We will contact you soon.');
    } else {
        $error_message = urlencode('Error submitting inquiry. Please try again.');
    }
}

$redirect = isset($_POST['car_id']) && $_POST['car_id'] !== '' ? 'car-details.php?id=' . $_POST['car_id'] : 'home.php';

if (isset($success_message)) {
    $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'success=' . $success_message;
} elseif (isset($error_message)) {
    $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'error=' . $error_message;
}

header('Location: ' . $redirect);
exit();
