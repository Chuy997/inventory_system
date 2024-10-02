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
        $query = "SELECT m.id, m.descripcion, COALESCE(SUM(il.cantidad), 0) as cantidad, GROUP_CONCAT(l.nombre SEPARATOR ', ') as localidades
                  FROM materials m
                  LEFT JOIN inventory_locations il ON m.id = il.material_id
                  LEFT JOIN locations l ON il.location_id = l.id
                  WHERE il.cantidad > 0
                  GROUP BY m.id";
    } elseif ($report_type == 'entries') {
        $query = "SELECT im.material_id, m.descripcion, im.cantidad, im.fecha
                  FROM inventory_movements im
                  JOIN materials m ON im.material_id = m.id
                  WHERE im.tipo_movimiento = 'entrada' AND im.fecha BETWEEN '$start_date' AND '$end_date'";
    } elseif ($report_type == 'outgoings') {
        $query = "SELECT im.material_id, m.descripcion, im.cantidad, im.fecha, im.area
                  FROM inventory_movements im
                  JOIN materials m ON im.material_id = m.id
                  WHERE im.tipo_movimiento = 'salida' AND im.fecha BETWEEN '$start_date' AND '$end_date'";
    }elseif ($report_type == 'history') {
        $query = "SELECT im.material_id, m.descripcion, im.cantidad, im.fecha, im.tipo_movimiento, 
                  IF(im.tipo_movimiento = 'entrada', im.localidad_destino, im.localidad_origen) as localidad, im.area
                  FROM inventory_movements im
                  JOIN materials m ON im.material_id = m.id
                  WHERE im.fecha BETWEEN '$start_date' AND '$end_date'";
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
        $pdf = new PDF('L');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $header = [];
        if ($report_type == 'inventory') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Localidades'];
        } elseif ($report_type == 'entries') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Fecha'];
        } elseif ($report_type == 'outgoings') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Fecha', 'Área'];
        } elseif ($report_type == 'history') {
            $header = ['ID del Material', 'Descripción', 'Cantidad', 'Fecha', 'Localidad de Origen','Localidad de Destino', 'Tipo de Movimiento', 'Área'];
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
    <style>
        /* Vamos a darle un look dark mode a la página */
        body {
            background-color: #2c2c2c; /* Fondo oscuro */
            color: #f1f1f1; /* Texto claro para contraste */
        }
        .container {
            max-width: 800px; /* Limita el ancho del contenedor */
            margin: auto; /* Centra el contenedor */
        }
        .error-message {
            color: red; /* Mensajes de error en rojo */
            font-size: 1.2em; /* Tamaño de fuente más grande */
            margin-bottom: 10px; /* Espacio debajo del mensaje */
        }
        .success-message {
            color: green; /* Mensajes de éxito en verde */
            font-size: 1.2em; /* Tamaño de fuente más grande */
            margin-bottom: 10px; /* Espacio debajo del mensaje */
        }
        select, input {
            font-size: 1.2em; /* Texto más grande para campos de entrada */
            margin-bottom: 10px; /* Espacio debajo de cada campo */
            background-color: #333; /* Fondo oscuro para los campos */
            color: #f1f1f1; /* Texto claro en los campos */
            border: 1px solid #555; /* Bordes oscuros */
        }
        table {
            width: 100%; /* Tabla ocupa todo el ancho */
            border-collapse: collapse; /* Quita los espacios entre celdas */
            background-color: #444; /* Fondo oscuro para la tabla */
        }
        table, th, td {
            border: 1px solid #666; /* Bordes grises */
        }
        th, td {
            padding: 10px; /* Espacio dentro de las celdas */
            text-align: left; /* Alinea el texto a la izquierda */
        }
        th {
            background-color: #555; /* Fondo oscuro para las cabeceras */
        }
        button {
            font-size: 1.2em; /* Texto más grande para botones */
            padding: 10px 20px; /* Espacio interno en los botones */
            background-color: #444; /* Fondo oscuro para los botones */
            color: #f1f1f1; /* Texto claro en los botones */
            border: none; /* Sin bordes */
        }
        button:hover {
            background-color: #555; /* Fondo más claro cuando pasas el mouse */
        }
        .js-example-basic-single {
            font-size: 1.5em; /* Texto aún más grande para este select */
        }
        .select2-container--default .select2-selection--single {
            background-color: #333; /* Fondo oscuro para el select */
            color: #f1f1f1; /* Texto claro */
            border: 1px solid #555; /* Bordes oscuros */
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: 1.2em;
            color: #f1f1f1; /* Texto blanco dentro del select */
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            border-left: 1px solid #555; /* Bordes oscuros para la flecha */
        }
        .select2-dropdown {
            background-color: #333; /* Fondo oscuro para el dropdown */
            color: #f1f1f1; /* Texto claro */
        }
        .select2-results__option {
            font-size: 1.2em;
            color: #f1f1f1; /* Texto blanco en las opciones del dropdown */
        }
        footer {
            text-align: center;
            padding: 10px 0;
            position: auto;
            width: 100%;
            bottom: 0;
            background-color: #333;
            color: white;
        }
    </style>
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
