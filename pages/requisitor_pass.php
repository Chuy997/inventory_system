<?php
$password = 'Xinya.123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo $hash;
?>
