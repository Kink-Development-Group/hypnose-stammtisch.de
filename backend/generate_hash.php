<?php
// Generate password hash for admin user
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash for 'admin123': " . $hash . "\n";
