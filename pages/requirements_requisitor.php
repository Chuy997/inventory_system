<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'requisitor') {
    header("Location: login.php");
    die;
}

// Inicializar lista de requerimientos en sesión si no existe
if (!isset($_SESSION['requerimientos'])) {
    $_SESSION['requerimientos'] = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $material_id = $_POST['material_id'];
        $cantidad = $_POST['cantidad'];
        $area = $_POST['area'];

        if (!empty($material_id) && !empty($cantidad) && !empty($area)) {
            // Obtener la cantidad total en inventario del material
            $query = "SELECT COALESCE(SUM(cantidad), 0) as total FROM inventory_locations WHERE material_id = '$material_id'";
            $result = $conn->query($query);
            $total = $result->fetch_assoc()['total'];

            if ($cantidad <= $total) {
                // Obtener descripción del material
                $query = "SELECT descripcion FROM materials WHERE id = '$material_id'";
                $result = $conn->query($query);
                $material_desc = $result->fetch_assoc()['descripcion'];

                // Añadir el requerimiento a la sesión
                $_SESSION['requerimientos'][] = [
                    'material_id' => $material_id,
                    'material_desc' => $material_desc,
                    'cantidad' => $cantidad,
                    'area' => $area
                ];
            } else {
                echo "<div class='error-message'>No se puede requerir más material del disponible en inventario.</div>";
            }
        }
    } elseif (isset($_POST['submit'])) {
        $fecha = date("Y-m-d H:i:s");
        foreach ($_SESSION['requerimientos'] as $req) {
            // Insertar requerimientos en la base de datos
            $query = "INSERT INTO material_requirements (material_id, cantidad, area, requisitor_id, fecha) 
                      VALUES ('{$req['material_id']}', '{$req['cantidad']}', '{$req['area']}', '{$user_data['id']}', '$fecha')";
            $conn->query($query);
        }
        $_SESSION['requerimientos'] = [];  // Vaciar la lista después de enviar los requerimientos
        echo "<div class='success-message'>Requerimientos enviados exitosamente</div>";
    } elseif (isset($_POST['delete'])) {
        // Eliminar un requerimiento de la lista
        $index = $_POST['index'];
        array_splice($_SESSION['requerimientos'], $index, 1);
    }
}

// Obtener lista de materiales
$query = "SELECT id, descripcion FROM materials";
$result = $conn->query($query);
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
            max-width: 80%; /* Limita el ancho del contenedor */
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
            font-size: 1.25em; /* Texto aún más grande para este select */
        }
        .select2-container--default .select2-selection--single {
            background-color: #333; /* Fondo oscuro para el select */
            color: #f1f1f1; /* Texto claro */
            border: 1px solid #555; /* Bordes oscuros */
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #f1f1f1; /* Texto blanco dentro del select */
            font-size: 1.25em;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            border-left: 1px solid #555; /* Bordes oscuros para la flecha */
        }
        .select2-dropdown {
            background-color: #333; /* Fondo oscuro para el dropdown */
            color: #f1f1f1; /* Texto claro */
            font-size: 1.25em;
        }
        .select2-results__option {
            color: #f1f1f1; /* Texto blanco en las opciones del dropdown */
        }
    </style>
    <title>Requerir Material</title>
</head>
<body>
    <?php include("../templates/header_requisitor.php"); ?>
    <div class="container">
        <h2>Requerir Material</h2>
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
            <label for="area">Área</label>
            <select id="area" name="area" class="js-example-basic-single" required>
                <option value="POC">POC</option>
                <option value="Embarques">Embarques</option>
                <option value="Recibo">Recibo</option>
                <option value="Almacen">Almacen</option>
                <option value="Otro">Otro</option>
            </select>
            <button type="submit" name="add">Agregar a la Lista</button>
        </form>

        <h2>Lista de Requerimientos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID del Material</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Área</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($_SESSION['requerimientos'])) {
                    foreach ($_SESSION['requerimientos'] as $index => $req) {
                        echo "<tr>";
                        echo "<td>{$req['material_id']}</td>";
                        echo "<td>{$req['material_desc']}</td>";
                        echo "<td>{$req['cantidad']}</td>";
                        echo "<td>{$req['area']}</td>";
                        echo "<td>
                                <form method='post' style='display:inline;'>
                                    <input type='hidden' name='index' value='$index'>
                                    <button type='submit' name='delete'>Eliminar</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No hay requerimientos en la lista</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <form method="post">
            <button type="submit" name="submit">Enviar Requerimientos</button>
        </form>
    </div>
    <?php include("../templates/footer.php"); ?>
    <script>
        $(document).ready(function() {
            // Activar Select2 en los selects y permitir la búsqueda por texto
            $('.js-example-basic-single').select2({
                width: '100%',
                dropdownAutoWidth: true,
                minimumResultsForSearch: 1, // Permitir búsqueda al teclear
            });
        });
    </script>
</body>
</html>
