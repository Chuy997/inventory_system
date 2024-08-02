<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'admin') {
    header("Location: login.php");
    die;
}

$report_type = '';
$start_date = '';
$end_date = '';
$report_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($report_type == 'inventory') {
        $query = "SELECT m.id, m.descripcion, COALESCE(SUM(il.cantidad), 0) as cantidad, m.max, m.min, m.HWcode, GROUP_CONCAT(l.nombre SEPARATOR ', ') as localidades
                  FROM materials m
                  LEFT JOIN inventory_locations il ON m.id = il.material_id
                  LEFT JOIN locations l ON il.location_id = l.id
                  WHERE il.cantidad > 0
                  GROUP BY m.id";
    } elseif ($report_type == 'entries') {
        $query = "SELECT im.material_id, m.descripcion, im.cantidad, im.fecha, im.localidad_destino
                  FROM inventory_movements im
                  JOIN materials m ON im.material_id = m.id
                  WHERE im.tipo_movimiento = 'entrada' AND im.fecha BETWEEN '$start_date' AND '$end_date'";
    } elseif ($report_type == 'outgoings') {
        $query = "SELECT im.material_id, m.descripcion, im.cantidad, im.fecha, im.localidad_origen, im.area
                  FROM inventory_movements im
                  JOIN materials m ON im.material_id = m.id
                  WHERE im.tipo_movimiento = 'salida' AND im.fecha BETWEEN '$start_date' AND '$end_date'";
    }

    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    } else {
        echo "No hay datos disponibles para el reporte seleccionado.";
    }

    if (isset($_POST['generate_pdf'])) {
        require('generate_pdf.php');
        $pdf = new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $header = [];
        if ($report_type == 'inventory') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Máximo', 'Mínimo', 'HWcode', 'Localidades'];
        } elseif ($report_type == 'entries') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Fecha', 'Localidad de Destino'];
        } elseif ($report_type == 'outgoings') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Fecha', 'Localidad de Origen', 'Área'];
        }
        $pdf->BasicTable($header, $report_data);
        $pdf->Output();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <title>Reportes de Inventario</title>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Generar Reporte</h2>
        <form method="post">
            <label for="report_type">Tipo de Reporte</label>
            <select id="report_type" name="report_type" required>
                <option value="inventory" <?php if ($report_type == 'inventory') echo 'selected'; ?>>Reporte de Inventario</option>
                <option value="entries" <?php if ($report_type == 'entries') echo 'selected'; ?>>Reporte de Entradas</option>
                <option value="outgoings" <?php if ($report_type == 'outgoings') echo 'selected'; ?>>Reporte de Salidas</option>
            </select>
            <label for="start_date">Fecha de Inicio</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
            <label for="end_date">Fecha de Fin</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
            <button type="submit" name="generate_report">Generar Reporte</button>
            <button type="submit" name="generate_pdf">Generar PDF</button>
        </form>

        <?php if (!empty($report_data)): ?>
            <h2>Resultados del Reporte</h2>
            <table>
                <thead>
                    <tr>
                        <?php
                        if ($report_type == 'inventory') {
                            echo "<th>ID del Material</th><th>Descripción</th><th>Cantidad</th><th>Máximo</th><th>Mínimo</th><th>HWcode</th><th>Localidades</th>";
                        } elseif ($report_type == 'entries') {
                            echo "<th>ID del Material</th><th>Descripción</th><th>Cantidad</th><th>Fecha</th><th>Localidad de Destino</th>";
                        } elseif ($report_type == 'outgoings') {
                            echo "<th>ID del Material</th><th>Descripción</th><th>Cantidad</th><th>Fecha</th><th>Localidad de Origen</th><th>Área</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($report_data as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>$value</td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php include("../templates/footer.php"); ?>
</body>
</html>
