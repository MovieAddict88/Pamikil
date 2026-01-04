<?php
// Generate password hash for admin user
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Verify: " . (password_verify($password, $hash) ? 'true' : 'false') . "\n";
