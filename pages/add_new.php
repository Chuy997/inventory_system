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
    $id = $_POST['id'];
    $zlcode = $_POST['zlcode'];
    $fecha_alta = $_POST['fecha_alta'];
    $length = $_POST['length'];
    $width = $_POST['width'];
    $height = $_POST['height'];
    $tipo = $_POST['tipo'];
    $max = $_POST['max'];
    $min = $_POST['min'];
    $hwcode = $_POST['hwcode'];
    $descripcion = $_POST['descripcion'];

    if (!empty($id) && !empty($zlcode) && !empty($fecha_alta) && !empty($length) && !empty($width) && !empty($height) && !empty($tipo) && !empty($max) && !empty($min) && !empty($hwcode) && !empty($descripcion)) {
        // Verificar si el ID ya existe
        $check_query = "SELECT id FROM materials WHERE id = '$id'";
        $check_result = $conn->query($check_query);

        if ($check_result->num_rows == 0) {
            $query = "INSERT INTO materials (id, ZLcode, fecha_alta, length, width, height, tipo, max, min, HWcode, descripcion) 
                      VALUES ('$id', '$zlcode', '$fecha_alta', '$length', '$width', '$height', '$tipo', '$max', '$min', '$hwcode', '$descripcion')";
            if ($conn->query($query) === TRUE) {
                // Inicializar cantidad en inventory_locations
                $query = "INSERT INTO inventory_locations (material_id, location_id, cantidad) VALUES ('$id', 'LOC001', 0)";
                $conn->query($query);

                // Registrar alta en inventory_history
                $fecha_actual = date("Y-m-d H:i:s");
                $query = "INSERT INTO inventory_history (material_id, movimiento, cantidad, fecha, descripcion) 
                          VALUES ('$id', 'alta', 0, '$fecha_actual', 'Alta de nuevo material')";
                $conn->query($query);

                echo "Nuevo material agregado exitosamente";
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "El ID del material ya existe. Por favor, elija un ID diferente.";
        }
    } else {
        echo "Todos los campos son requeridos.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <title>Agregar Nuevo Material</title>
    <style>
        .container {
            padding: 20px;
            max-width: 800px;
            margin: auto;
            min-height: calc(100vh - 100px); /* Ajuste para asegurar que el contenido no sea más corto que la ventana */
            margin-bottom: 50px; /* Espacio para el footer */
        }
        form {
            display: flex;
            flex-wrap: wrap;
        }
        label, input, textarea, button {
            width: 100%;
            margin-bottom: 15px;
        }
        input, textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        @media (min-width: 600px) {
            label, input, textarea {
                flex: 1 1 calc(50% - 10px);
                margin-right: 10px;
            }
            label:nth-child(even), input:nth-child(even), textarea:nth-child(even) {
                margin-right: 0;
            }
        }
        @media (min-width: 768px) {
            .container {
                max-width: 1000px;
            }
        }
        footer {
            margin-top: 20px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Agregar Nuevo Material</h2>
        <form method="post">
            <label for="id">ID</label>
            <input type="text" id="id" name="id" required>
            <label for="zlcode">ZLcode</label>
            <input type="text" id="zlcode" name="zlcode" required>
            <label for="fecha_alta">Fecha de Alta</label>
            <input type="date" id="fecha_alta" name="fecha_alta" required>
            <label for="length">Length</label>
            <input type="number" step="0.01" id="length" name="length" required>
            <label for="width">Width</label>
            <input type="number" step="0.01" id="width" name="width" required>
            <label for="height">Height</label>
            <input type="number" step="0.01" id="height" name="height" required>
            <label for="tipo">Tipo</label>
            <input type="text" id="tipo" name="tipo" required>
            <label for="max">Max</label>
            <input type="number" id="max" name="max" required>
            <label for="min">Min</label>
            <input type="number" id="min" name="min" required>
            <label for="hwcode">HWcode</label>
            <input type="text" id="hwcode" name="hwcode" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" required></textarea>
            <button type="submit">Agregar</button>
        </form>
    </div>
    <?php include("../templates/footer.php"); ?>
</body>
</html>
