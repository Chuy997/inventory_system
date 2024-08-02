<?php
include("../includes/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $material_id = $_POST['material_id'];

    if (!empty($material_id)) {
        // Obtener las localidades y cantidades del material seleccionado
        $query = "SELECT il.location_id, l.nombre, il.cantidad 
                  FROM inventory_locations il
                  JOIN locations l ON il.location_id = l.id
                  WHERE il.material_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $material_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['location_id']}'>{$row['nombre']} (Cantidad: {$row['cantidad']})</option>";
            }
        } else {
            echo "<option value=''>No hay localidades disponibles</option>";
        }
    }
}
?>
