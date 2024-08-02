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
    <link rel="stylesheet" href="../css/style.css">
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
                        
                        // Obtener las localidades donde está disponible el material
                        $localidades_query = "SELECT il.location_id, l.nombre, il.cantidad 
                                              FROM inventory_locations il
                                              JOIN locations l ON il.location_id = l.id
                                              WHERE il.material_id = '{$row['material_id']}'";
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
    <?php include("../templates/footer.php"); ?>
</body>
</html>
