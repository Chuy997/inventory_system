<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'admin' && $user_data['role'] !== 'requisitor' ) {
    header("Location: login.php");
    die;
}

// Consultar los datos de la tabla proceso_compra
$proceso_compra_query = "SELECT * FROM proceso_compra";
$proceso_compra_result = $conn->query($proceso_compra_query);

// Consultar los datos de la tabla inventario_critico
$inventario_critico_query = "SELECT * FROM inventario_critico";
$inventario_critico_result = $conn->query($inventario_critico_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Vamos a darle un look dark mode a la página */
        body {
            background-color: #2c2c2c; /* Fondo oscuro */
            color: #f1f1f1; /* Texto claro para contraste */
        }
        .container {
            max-width: 80%; /* Limita el ancho del contenedor */
            margin: auto; /* Centra el contenedor */
            padding-top: 20px;
        }
        table {
            width: 100%; /* Tabla ocupa todo el ancho */
            border-collapse: collapse; /* Quita los espacios entre celdas */
            background-color: #444; /* Fondo oscuro para la tabla */
            margin-bottom: 20px;
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
            margin-top: 20px;
        }
        button:hover {
            background-color: #555; /* Fondo más claro cuando pasas el mouse */
        }
    </style>
    <title>Tablas de Proceso de Compra e Inventario Crítico</title>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    
    <div class="container">
        <h2>Tabla: Inventario en límite min</h2>
        <table>
            <thead>
                <tr>                    
                    <th>ZLCode</th>
                    <th>Descripción</th>
                    <th>Cantidad Actual</th>
                    <th>Cantidad Sugerida</th>
                    <th>HWCode</th>
                    <th>Máximo</th>
                    <th>Mínimo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($proceso_compra_result->num_rows > 0) {
                    while ($row = $proceso_compra_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['zlcode']}</td>";
                        echo "<td>{$row['descripcion']}</td>";
                        echo "<td>{$row['cantidad_actual']}</td>";
                        echo "<td>{$row['cantidad_sugerida']}</td>";
                        echo "<td>{$row['HWcode']}</td>";
                        echo "<td>{$row['max']}</td>";
                        echo "<td>{$row['min']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No se encontraron registros en stock mínimo.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <h2>Tabla: Inventario Crítico</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ZLCode</th>
                    <th>Descripción</th>
                    <th>Cantidad Actual</th>
                    <th>Cantidad Sugerida</th>
                    <th>Máximo</th>
                    <th>Mínimo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($inventario_critico_result->num_rows > 0) {
                    while ($row = $inventario_critico_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['zlcode']}</td>";
                        echo "<td>{$row['descripcion']}</td>";
                        echo "<td>{$row['cantidad_actual']}</td>";
                        echo "<td>{$row['cantidad_sugerida']}</td>";
                        echo "<td>{$row['max']}</td>";
                        echo "<td>{$row['min']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No se encontraron registros en Inventario Crítico.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <?php include("../templates/footer.php"); ?>
</body>
</html>
