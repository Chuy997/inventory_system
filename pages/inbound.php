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
    $cantidad = $_POST['cantidad'];
    $localidad_id = 'LOC001';  // Se asume que LOC001 es la localidad de paso
    $fecha = date("Y-m-d H:i:s"); // Registrar la fecha automáticamente

    if (!empty($material_id) && !empty($cantidad)) {
        // Verificar si el registro existe en inventory_locations
        $query = "SELECT * FROM inventory_locations WHERE material_id = '$material_id' AND location_id = '$localidad_id'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // Actualizar la cantidad si el registro existe
            $query = "UPDATE inventory_locations SET cantidad = cantidad + $cantidad WHERE material_id = '$material_id' AND location_id = '$localidad_id'";
        } else {
            // Insertar un nuevo registro si no existe
            $query = "INSERT INTO inventory_locations (material_id, location_id, cantidad) VALUES ('$material_id', '$localidad_id', $cantidad)";
        }
        
        if ($conn->query($query) === TRUE) {
            // Registrar movimiento en inventory_movements
            $query = "INSERT INTO inventory_movements (material_id, tipo_movimiento, cantidad, fecha, localidad_destino) VALUES ('$material_id', 'entrada', '$cantidad', '$fecha', '$localidad_id')";
            $conn->query($query);

            // Registrar en historial
            $query = "INSERT INTO inventory_history (material_id, movimiento, cantidad, fecha, descripcion) VALUES ('$material_id', 'entrada', '$cantidad', '$fecha', 'Entrada de material')";
            $conn->query($query);

            // Llamar a la función para verificar inventario y actualizar proceso_compra
            verificar_inventario($conn);

            echo "Entrada registrada exitosamente";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Todos los campos son requeridos.";
    }
}

// Obtener lista de materiales
$query = "SELECT id, descripcion FROM materials";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
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
            display: flex;           /* Usar flexbox en el body */
            flex-direction: column;  /* Colocar los hijos en una columna */
            min-height: 100vh; 
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
            font-size: 1.2em
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
        #loading-icon {
            position: fixed;
            z-index: 1000;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;  /* Ocultar el ícono de carga inicialmente */
        }

        #loading-icon img {
            width: 50px;
            height: 50px;
        }


    </style>
    <title>Registrar Entrada de Material</title>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Registrar Entrada de Material</h2>
        <form method="post">
            <label for="material_id">ID del Material</label>
            <select id="material_id" name="material_id" class="js-example-basic-single" required>
                <option value="">Selecciona un material</option>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}'>{$row['id']} - {$row['descripcion']}</option>";
                    }
                } else {
                    echo "<option value=''>No hay materiales disponibles</option>";
                }
                ?>
            </select>
            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" required>
            <button type="submit">Registrar Entrada</button>
        </form>
    </div>
    <!-- Icono de carga -->
    <div id="loading-icon">
        <img src="https://i.gifer.com/ZZ5H.gif" alt="Cargando..." />
    </div>
    <?php include("../templates/footer.php"); ?>
    <script>
        $(document).ready(function() {
    // Activar Select2 en los selects y permitir la búsqueda por texto
    $('.js-example-basic-single').select2({
        width: '100%',
        dropdownAutoWidth: true,
        minimumResultsForSearch: 1 // Permitir búsqueda al teclear
    });

    // Mostrar el ícono de carga solo al enviar el formulario
    $('form').on('submit', function() {
        $('#loading-icon').show();  // Mostrar el ícono de carga al enviar
    });
});


    </script>
</body>
</html>
