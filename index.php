<?php
session_start();
include("includes/db.php");
include("includes/functions.php");

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
    <style>
        /* General Styles */
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #1f1f1f;
            padding: 10px 0;
            margin-bottom: 20px;
        }

        header nav ul {
            list-style: none;
            padding: 0;
            display: flex;
            justify-content: left;
            margin: 0;
        }

        header nav ul li {
            margin: 0 15px;
        }

        header nav ul li a {
            text-decoration: none;
            color: #e0e0e0;
            font-weight: bold;
        }

        header nav ul li a:hover {
            color: #90caf9;
        }

        .container {
            width: 80%;
            margin: 0 auto;
        }

        h2, h3 {
            color: #90caf9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #3d3d3d;
        }

        th, td {
            padding: 10px;
            text-align: left;
            font-size: 1.3em
        }

        th {
            background-color: #1f1f1f;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tr:nth-child(even) {
            background-color: #2c2c2c;
        }

        tr:nth-child(odd) {
            background-color: #1f1f1f;
        }

        input[type="text"], input[type="number"], input[type="date"], select {
            width: calc(100% - 22px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #3d3d3d;
            background-color: #1f1f1f;
            color: #e0e0e0;
        }

        button {
            background-color: #90caf9;
            color: #121212;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #64b5f6;
        }

        .success {
            color: #4caf50;
        }

        .error {
            color: #f44336;
        }

        .alert {
            background-color: #ff9800;
            color: #121212;
            padding: 10px;
            margin-bottom: 20px;
        }

        .low-stock {
            background-color: rgb(239, 180, 31) !important;
        }

        .critical-stock {
            background-color: red !important;
        }

        /* Estilos para el footer */
        footer {
            text-align: center;
            padding: 10px 0;
            position: flex;
            width: 100%;
            bottom: 0;
            background-color: #333;
            color: white;
        }

        .badge {
            background-color: red;
            color: white;
            padding: 2px 8px;
            border-radius: 50%;
            font-size: 12px;
            vertical-align: super;
            margin-left: 5px;
        }
    </style>
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
                    <th>ID</th>
                    <th>HWcode</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Localidades</th>
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
