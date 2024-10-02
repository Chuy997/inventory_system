<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include($_SERVER['DOCUMENT_ROOT']."/inventory_system/includes/db.php");
include($_SERVER['DOCUMENT_ROOT']."/inventory_system/includes/functions.php");

$user_data = check_login($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/inventory_system/css/style.css">
    <title>Inventory System</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="/inventory_system/pages/index_requisitor.php">Home</a></li>
                <li><a href="/inventory_system/pages/requirements_requisitor.php">Requirements</a></li>
                <li><a href="/inventory_system/pages/inventario_critico.php">Inventario Crítico</a></li>
                <li><a href="/inventory_system/logout.php">Logout</a></li>
                
            </ul>
        </nav>
    </header>
    <div class="content">
