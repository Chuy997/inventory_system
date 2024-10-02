<?php
include("../includes/db.php");

// Eliminar usuarios existentes
$query_delete_admin = "DELETE FROM users WHERE username = 'admin_user'";
$query_delete_requisitor = "DELETE FROM users WHERE username = 'req_user'";

$conn->query($query_delete_admin);
$conn->query($query_delete_requisitor);

// Crear nuevas contraseñas encriptadas
$admin_password = password_hash('admin_password', PASSWORD_DEFAULT);
$requisitor_password = password_hash('req_password', PASSWORD_DEFAULT);

// Insertar nuevos usuarios
$query_admin = "INSERT INTO users (username, password, role) VALUES ('admin_user', '$admin_password', 'admin')";
$query_requisitor = "INSERT INTO users (username, password, role) VALUES ('req_user', '$requisitor_password', 'requisitor')";

if ($conn->query($query_admin) === TRUE && $conn->query($query_requisitor) === TRUE) {
    echo "Users created successfully";
} else {
    echo "Error: " . $conn->error;
}
?>
