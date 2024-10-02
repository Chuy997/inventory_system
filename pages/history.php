<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'admin') {
    header("Location: login.php");
    die;
}

// Obtener el término de búsqueda si se envió
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Consulta para obtener el historial de movimientos con filtro de búsqueda
$query = "SELECT ih.id, ih.material_id, ih.movimiento, ih.cantidad, ih.fecha, ih.descripcion, m.descripcion AS material_desc
          FROM inventory_history ih
          JOIN materials m ON ih.material_id = m.id
          WHERE ih.id LIKE '%$search%'
             OR ih.material_id LIKE '%$search%'
             OR ih.movimiento LIKE '%$search%'
             OR ih.cantidad LIKE '%$search%'
             OR ih.fecha LIKE '%$search%'
             OR ih.descripcion LIKE '%$search%'
             OR m.descripcion LIKE '%$search%'
          ORDER BY ih.fecha DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/history.css">
    <title>Historial</title>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Historial de Movimientos</h2>

        <!-- Formulario de búsqueda -->
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID del Material</th>
                    <th>Descripción del Material</th>
                    <th>Movimiento</th>
                    <th>Cantidad</th>
                    <th>Fecha</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['material_id']}</td>";
                        echo "<td>{$row['material_desc']}</td>";
                        echo "<td>{$row['movimiento']}</td>";
                        echo "<td>{$row['cantidad']}</td>";
                        echo "<td>{$row['fecha']}</td>";
                        echo "<td>{$row['descripcion']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No hay movimientos registrados</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php include("../templates/footer.php"); ?>
</body>
</html>
