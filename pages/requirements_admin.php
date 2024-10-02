<?php
session_start();
include("../includes/db.php");
include("../includes/functions.php");

$user_data = check_login($conn);

if ($user_data['role'] !== 'admin') {
    header("Location: login.php");
    die;
}

// Obtener el número de requerimientos pendientes
$pending_query = "SELECT COUNT(*) AS pending_count FROM material_requirements WHERE status = 'pendiente'";
$pending_result = $conn->query($pending_query);
$pending_count = $pending_result->fetch_assoc()['pending_count'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $requirement_id = $_POST['requirement_id'];
    $material_id = $_POST['material_id'];
    $location_id = $_POST['location_id'];
    $cantidad = $_POST['cantidad'];
    $fecha = date("Y-m-d H:i:s");

    if (!empty($requirement_id) && !empty($material_id) && !empty($location_id)) {
        if ($cantidad == 0) {
            // Opción de no surtir
            $query = "UPDATE material_requirements SET status = 'not_fulfilled' WHERE id = '$requirement_id'";
            if ($conn->query($query) === TRUE) {
                echo "Material marcado como no surtido";
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            // Verificar si el registro existe en inventory_locations y tiene suficiente cantidad
            $query = "SELECT cantidad FROM inventory_locations WHERE material_id = '$material_id' AND location_id = '$location_id'";
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['cantidad'] >= $cantidad) {
                    // Actualizar la cantidad en inventory_locations
                    $query = "UPDATE inventory_locations SET cantidad = cantidad - $cantidad WHERE material_id = '$material_id' AND location_id = '$location_id'";
                    if ($conn->query($query) === TRUE) {
                        // Actualizar el estado del requerimiento
                        $query = "UPDATE material_requirements SET status = 'surtido' WHERE id = '$requirement_id'";
                        $conn->query($query);

                        // Registrar movimiento en inventory_movements
                        $query = "INSERT INTO inventory_movements (material_id, tipo_movimiento, cantidad, fecha, area, localidad_origen) VALUES ('$material_id', 'salida', '$cantidad', '$fecha', '{$_POST['area']}', '$location_id')";
                        $conn->query($query);

                        // Registrar en historial
                        $query = "INSERT INTO inventory_history (material_id, movimiento, cantidad, fecha, descripcion) VALUES ('$material_id', 'salida', '$cantidad', '$fecha', 'Surtido de material a {$_POST['area']}')";
                        $conn->query($query);

                        // Llamar a la función para verificar inventario y actualizar proceso_compra
                        verificar_inventario($conn);

                        echo "Material surtido exitosamente";
                    } else {
                        echo "Error: " . $conn->error;
                    }
                } else {
                    echo "No hay suficiente cantidad de material en la localidad.";
                }
            } else {
                echo "El material con ID '$material_id' no existe en la localidad especificada.";
            }
        }
    } else {
        echo "Todos los campos son requeridos.";
    }
}

// Obtener lista de requerimientos pendientes
$query = "SELECT mr.id, mr.material_id, m.descripcion, mr.cantidad, mr.area, mr.requisitor_id, u.username, mr.fecha 
          FROM material_requirements mr
          JOIN materials m ON mr.material_id = m.id
          JOIN users u ON mr.requisitor_id = u.id
          WHERE mr.status = 'pendiente'
          ORDER BY mr.fecha DESC";
$result = $conn->query($query);
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

        /* Estilo para el ícono de carga */
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
    <title>Surtir Requerimientos</title>
</head>
<body>
    <?php include("../templates/header.php"); ?>
    <div class="container">
        <h2>Requerimientos Pendientes</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material</th>
                    <th>Cantidad</th>
                    <th>Área</th>
                    <th>Requisitor</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['material_id']} - {$row['descripcion']}</td>";
                        echo "<td>{$row['cantidad']}</td>";
                        echo "<td>{$row['area']}</td>";
                        echo "<td>{$row['username']}</td>";
                        echo "<td>{$row['fecha']}</td>";
                        echo "<td>
                                <form method='post'>
                                    <input type='hidden' name='requirement_id' value='{$row['id']}'>
                                    <input type='hidden' name='material_id' value='{$row['material_id']}'>
                                    <input type='hidden' name='area' value='{$row['area']}'>
                                    <label for='location_id'>Localidad</label>
                                    <select id='location_id' name='location_id' required>";
                        
                        $localidades_query = "SELECT il.location_id, l.nombre, il.cantidad 
                                              FROM inventory_locations il
                                              JOIN locations l ON il.location_id = l.id
                                              WHERE il.material_id = '{$row['material_id']}'
                                              AND il.cantidad > 0";
                        $localidades_result = $conn->query($localidades_query);
                        if ($localidades_result->num_rows > 0) {
                            while ($loc_row = $localidades_result->fetch_assoc()) {
                                echo "<option value='{$loc_row['location_id']}'>{$loc_row['nombre']} - Cantidad disponible: {$loc_row['cantidad']}</option>";
                            }
                        } else {
                            echo "<option value=''>No hay localidades disponibles</option>";
                        }
                        
                        echo "      </select>
                                    <label for='cantidad'>Cantidad a Surtir</label>
                                    <input type='number' id='cantidad' name='cantidad' min='0' required>
                                    <button type='submit'>Surtir</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No hay requerimientos pendientes</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Icono de carga -->
    <div id="loading-icon">
        <img src="https://i.gifer.com/ZZ5H.gif" alt="Cargando..." />
    </div>

    <?php include("../templates/footer.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mostrar el ícono de carga al enviar cualquier formulario
            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    // Mostrar el ícono de carga
                    document.getElementById('loading-icon').style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>
