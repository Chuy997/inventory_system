<?php
// Incluir conexión a la base de datos si no se ha incluido ya
include_once("db.php");

if (!function_exists('check_login')) {
    function check_login($conn)
    {
        if (isset($_SESSION['user_id'])) {
            $id = $_SESSION['user_id'];
            $query = "SELECT * FROM users WHERE id = '$id' LIMIT 1";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);
                return $user_data;
            }
        }
        // Redirigir al login si no está autenticado
        header("Location: ../pages/login.php");
        die;
    }
}
?>
