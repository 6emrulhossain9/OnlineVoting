<?php
$password = 'amerachar';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "The password hash for 'amerachar' is: " . $hashed_password;
?>
