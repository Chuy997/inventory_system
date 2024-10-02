<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include($_SERVER['DOCUMENT_ROOT']."/inventory_system/includes/db.php");
include($_SERVER['DOCUMENT_ROOT']."/inventory_system/includes/functions.php");

$user_data = check_login($conn);

// Obtener el número de requerimientos pendientes (solo si no se ha hecho en la página que incluye el header)
if (!isset($pending_count)) {
    $pending_query = "SELECT COUNT(*) AS pending_count FROM material_requirements WHERE status = 'pendiente'";
    $pending_result = $conn->query($pending_query);
    $pending_count = $pending_result->fetch_assoc()['pending_count'];
}
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
                <li><a href="/inventory_system/index.php">Home</a></li>
                <?php if ($user_data['role'] == 'admin'): ?>
                    <li><a href="/inventory_system/pages/inbound.php">Inbound</a></li>
                    <li>
                        <a href="/inventory_system/pages/requirements_admin.php">Requirements</a>
                        <?php if ($pending_count > 0): ?>
                            <span class="badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </li>
                    <li><a href="/inventory_system/pages/outbound.php">Outbound</a></li>
                    <li><a href="/inventory_system/pages/history.php">Historial</a></li>
                    <li><a href="/inventory_system/pages/add_new.php">Add New</a></li>
                    <li><a href="/inventory_system/pages/allocation.php">Allocation</a></li>
                    <li><a href="/inventory_system/pages/reports.php">Reports</a></li>
		            <li><a href="/inventory_system/pages/inventory_charts.php">Graficos de inventario</a></li>
                    <li><a href="/inventory_system/pages/inventario_critico.php">Inventario Crítico</a></li>
                <?php elseif ($user_data['role'] == 'requisitor'): ?>
                    <li>
                        <a href="/inventory_system/pages/requirements_requisitor.php">Requirements</a>
                        <?php if ($pending_count > 0): ?>
                            <span class="badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
                
                <li><a href="/inventory_system/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <div class="content">
