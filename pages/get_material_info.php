<?php
include("../includes/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $material_id = $_POST['material_id'];

    if (!empty($material_id)) {
        // Obtener la cantidad total del material en inventario
        $query = "SELECT COALESCE(SUM(cantidad), 0) AS total FROM inventory_locations WHERE material_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $material_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];

        echo "Cantidad total del material en inventario: " . $total;
    }
}
?>
