<?php
session_start();
include_once("includes/db.php");
include_once("includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'admin') {
    header("Location: pages/login.php");
    die;
}

// Consulta para obtener el inventario actual total por material, excluyendo localidades con cantidad 0
$query = "SELECT m.id, m.descripcion, COALESCE(SUM(il.cantidad), 0) as cantidad, m.max, m.min, m.HWcode, 
                 GROUP_CONCAT(CASE WHEN il.cantidad > 0 THEN l.nombre END SEPARATOR ', ') as localidades
          FROM materials m
          LEFT JOIN inventory_locations il ON m.id = il.material_id
          LEFT JOIN locations l ON il.location_id = l.id
          WHERE il.cantidad > 0
          GROUP BY m.id";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="css/style.css"> 
    <title>Home</title>
    
</head>
<body>
    <?php include("templates/header.php"); ?>
    <div class="container">
        <h1>Bienvenido, <?php echo $user_data['username']; ?></h1>
        <h2>Inventario Actual</h2>
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Buscar...">
        <table id="inventoryTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">ID</th>
                    <th onclick="sortTable(1)">HWcode</th>
                    <th onclick="sortTable(2)">Descripción</th>
                    <th onclick="sortTable(3)">Cantidad</th>
                    <th onclick="sortTable(4)">Localidades</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $quantityClass = '';
                        if ($row['cantidad'] < $row['min']) {
                            $quantityClass = 'critical-stock';
                        } elseif ($row['cantidad'] <= ($row['max'] * 0.33)) {
                            $quantityClass = 'low-stock';
                        }
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['HWcode']}</td>";
                        echo "<td>{$row['descripcion']}</td>";
                        echo "<td class='{$quantityClass}'>{$row['cantidad']}</td>";
                        echo "<td>{$row['localidades']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No hay datos disponibles</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php include("templates/footer.php"); ?>

    <script>
        function filterTable() {
            var input, filter, table, tr, td, i, j, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("inventoryTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                tr[i].style.display = "none";
                td = tr[i].getElementsByTagName("td");
                for (j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break;
                        }
                    }
                }
            }
        }
        
    </script>
</body>
</html>
