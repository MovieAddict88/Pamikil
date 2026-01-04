<?php
// Function to connect to the database
function db_connect() {
    // Attempt to connect to the database
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check for connection errors
    if ($conn->connect_error) {
        // Log the error for the administrator
        error_log("Database connection failed: " . $conn->connect_error);

        // Display a generic error message to the user and exit
        die("A database error occurred. Please try again later.");
    }

    return $conn;
}
