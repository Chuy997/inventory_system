<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'admin') {
    header("Location: login.php");
    die;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $material_id = $_POST['material_id'];
    $from_location_id = $_POST['from_location_id'];
    $to_location_id = $_POST['to_location_id'];
    $cantidad = $_POST['cantidad'];
    $fecha = date("Y-m-d H:i:s");

    if (!empty($material_id) && !empty($from_location_id) && !empty($to_location_id) && !empty($cantidad)) {
        // Verificar si hay suficiente cantidad en la localidad de origen
        $query = "SELECT cantidad FROM inventory_locations WHERE material_id = '$material_id' AND location_id = '$from_location_id'";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['cantidad'] >= $cantidad) {
                // Iniciar transacción
                $conn->begin_transaction();
                try {
                    // Actualizar la cantidad en la localidad de origen
                    $query = "UPDATE inventory_locations SET cantidad = cantidad - $cantidad WHERE material_id = '$material_id' AND location_id = '$from_location_id'";
                    $conn->query($query);

                    // Verificar si el material ya existe en la localidad de destino
                    $query = "SELECT * FROM inventory_locations WHERE material_id = '$material_id' AND location_id = '$to_location_id'";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0) {
                        // Actualizar la cantidad en la localidad de destino
                        $query = "UPDATE inventory_locations SET cantidad = cantidad + $cantidad WHERE material_id = '$material_id' AND location_id = '$to_location_id'";
                    } else {
                        // Insertar un nuevo registro si no existe
                        $query = "INSERT INTO inventory_locations (material_id, location_id, cantidad) VALUES ('$material_id', '$to_location_id', $cantidad)";
                    }
                    $conn->query($query);

                    // Registrar movimiento en inventory_movements
                    $query = "INSERT INTO inventory_movements (material_id, tipo_movimiento, cantidad, fecha, localidad_origen, localidad_destino) 
                              VALUES ('$material_id', 'reubicacion', '$cantidad', '$fecha', '$from_location_id', '$to_location_id')";
                    $conn->query($query);

                    // Registrar en el historial
                    $query = "INSERT INTO inventory_history (material_id, movimiento, cantidad, fecha, descripcion) 
                              VALUES ('$material_id', 'reubicacion', '$cantidad', '$fecha', 'Reubicación de $cantidad del material ID $material_id de localidad $from_location_id a localidad $to_location_id')";
                    $conn->query($query);

                    // Confirmar transacción
                    $conn->commit();
                    echo "Reubicación de material exitosa";
                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $conn->rollback();
                    error_log("Error en la reubicación de material: " . $e->getMessage());
                    echo "Error al reubicar el material: " . $e->getMessage();
                }
            } else {
                echo "No hay suficiente cantidad de material en la localidad de origen.";
            }
        } else {
            echo "El material no existe en la localidad de origen.";
        }
    } else {
        echo "Todos los campos son requeridos.";
    }
}

// Obtener lista de materiales
$materials_query = "SELECT id, descripcion FROM materials";
$materials_result = $conn->query($materials_query);

// Obtener lista de localidades con inventario
$locations_query = "SELECT l.id, l.nombre, il.material_id, il.cantidad 
                    FROM locations l
                    JOIN inventory_locations il ON l.id = il.location_id
                    WHERE il.cantidad > 0";
$locations_result = $conn->query($locations_query);

$locations_by_material = [];
if ($locations_result->num_rows > 0) {
    while ($row = $locations_result->fetch_assoc()) {
        $locations_by_material[$row['material_id']][] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'cantidad' => $row['cantidad']
        ];
    }
}

// Obtener todas las localidades
$all_locations_query = "SELECT id, nombre FROM locations";
$all_locations_result = $conn->query($all_locations_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
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
            max-width: 65%; /* Limita el ancho del contenedor */
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
            position: absolute;
            width: 100%;
            bottom: 0;
            background-color: #333;
            color: white;
        }
    </style>
    <title>Reubicar Material</title>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Reubicar Material</h2>
        <form method="post">
            <label for="material_id">Material</label>
            <select id="material_id" name="material_id" class="js-example-basic-single" required>
                <option value="">Selecciona un material</option>
                <?php
                if ($materials_result->num_rows > 0) {
                    while ($row = $materials_result->fetch_assoc()) {
                        echo "<option value='{$row['id']}'>{$row['id']} - {$row['descripcion']}</option>";
                    }
                } else {
                    echo "<option value=''>No hay materiales disponibles</option>";
                }
                ?>
            </select>
            <label for="from_location_id">De Localidad</label>
            <select id="from_location_id" name="from_location_id" class="js-example-basic-single" required>
                <option value="">Selecciona una localidad</option>
                <!-- Las opciones se llenarán dinámicamente con JavaScript -->
            </select>
            <label for="to_location_id">A Localidad</label>
            <select id="to_location_id" name="to_location_id" class="js-example-basic-single" required>
                <option value="">Selecciona una localidad</option>
                <?php
                if ($all_locations_result->num_rows > 0) {
                    while ($row = $all_locations_result->fetch_assoc()) {
                        echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
                    }
                } else {
                    echo "<option value=''>No hay localidades disponibles</option>";
                }
                ?>
            </select>
            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" required>
            <button type="submit">Reubicar</button>
        </form>
    </div>
    <?php include("../templates/footer.php"); ?>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2({
                width: '100%'
            });

            // Datos de localidades agrupados por material
            var locationsByMaterial = <?php echo json_encode($locations_by_material); ?>;

            $('#material_id').change(function() {
                var materialId = $(this).val();
                var $fromLocationSelect = $('#from_location_id');
                $fromLocationSelect.empty();
                $fromLocationSelect.append('<option value="">Selecciona una localidad</option>');

                if (locationsByMaterial[materialId]) {
                    locationsByMaterial[materialId].forEach(function(location) {
                        $fromLocationSelect.append('<option value="' + location.id + '">' + location.nombre + ' (Cantidad: ' + location.cantidad + ')</option>');
                    });
                }
            });
        });
    </script>
</body>
</html>
