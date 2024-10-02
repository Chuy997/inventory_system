<?php
// Incluir conexión a la base de datos si no se ha incluido ya
include_once("db.php");


if (!function_exists('check_login')) {
    function check_login($conn)
    {
        if (isset($_SESSION['user_id'])) {
            $id = $_SESSION['user_id'];
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        // Redirigir al login si no está autenticado
        header("Location: ../pages/login.php");
        die;
    }
}

if (!function_exists('verificar_inventario')) {
    function verificar_inventario($conn) {
        echo "Iniciando verificación de inventario...<br>";

        // Listas para consolidar los materiales agregados a las tablas
        $nuevos_proceso_compra = [];
        $nuevos_inventario_critico = [];

        // Consulta para obtener el inventario actual total por material
        $query = "SELECT m.id, m.descripcion, m.zlcode, m.HWcode, COALESCE(SUM(il.cantidad), 0) AS cantidad, m.max, m.min
                  FROM materials m
                  LEFT JOIN inventory_locations il ON m.id = il.material_id
                  WHERE il.cantidad > 0
                  GROUP BY m.id";
    
        $result = mysqli_query($conn, $query);
    
        if (!$result) {
            echo "Error en la consulta de inventario: " . mysqli_error($conn) . "<br>";
            return;
        }
    
        if (mysqli_num_rows($result) > 0) {
            //echo "Materiales encontrados: " . mysqli_num_rows($result) . "<br>";
    
            while ($row = mysqli_fetch_assoc($result)) {
                $material_id = $row['id'];
                $descripcion = $row['descripcion'];
                $zlcode = $row['zlcode'];
                $HWcode = $row['HWcode'];
                $cantidad_actual = $row['cantidad'];
                $cantidad_maxima = $row['max'];
                $minimo = $row['min'];

                // Calcula el porcentaje de inventario actual en relación al máximo
                $porcentaje_inventario = ($cantidad_actual / $cantidad_maxima) * 100;
                // Calcular los umbrales directamente en función del valor de 'max'
                $umbral_min = $cantidad_maxima * 0.50;  // 50% del valor máximo
                $umbral_max = $cantidad_maxima * 0.67;  // 67% del valor máximo

                // Si la cantidad actual está entre 50% y 67% del máximo, mover a proceso_compra
                if ($cantidad_actual > $umbral_min && $cantidad_actual < $umbral_max) {
                    $check_query = "SELECT id FROM proceso_compra WHERE zlcode = ?";
                    $stmt_check = $conn->prepare($check_query);
                    $stmt_check->bind_param("s", $zlcode);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
    
                    if ($result_check->num_rows == 0) {
                        // Insertar en proceso_compra
                        //echo "Insertando nuevo material en proceso_compra: $zlcode (Cantidad actual: $cantidad_actual)<br>";
                        $insert_query = "INSERT INTO proceso_compra (material_id, zlcode, descripcion, HWcode, max, min, cantidad_actual, cantidad_sugerida) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $cantidad_sugerida = $cantidad_maxima - $cantidad_actual;
                        $stmt_insert = $conn->prepare($insert_query);
                        $stmt_insert->bind_param("isssiiii", $material_id, $zlcode, $descripcion, $HWcode, $cantidad_maxima, $minimo, $cantidad_actual, $cantidad_sugerida);
                        
                        if ($stmt_insert->execute()) {
                            // Agregar el material a la lista de nuevos materiales en proceso_compra
                            $nuevos_proceso_compra[] = [
                                'zlcode' => $zlcode,
                                'descripcion' => $descripcion,
                                'cantidad_actual' => $cantidad_actual,
                                'max' => $cantidad_maxima
                            ];
                        } else {
                            echo "Error al insertar el material $zlcode en proceso_compra: " . $stmt_insert->error . "<br>";
                        }
                    }
                } elseif ($porcentaje_inventario >= 67) {
                    // Eliminar de proceso_compra si el porcentaje supera el 67%
                    //echo "Eliminando material de proceso_compra: $zlcode (Cantidad actual: $cantidad_actual, Porcentaje: $porcentaje_inventario%)<br>";
                    $delete_query = "DELETE FROM proceso_compra WHERE zlcode = ?";
                    $stmt_delete = $conn->prepare($delete_query);
                    $stmt_delete->bind_param("s", $zlcode);
                    if (!$stmt_delete->execute()) {
                        echo "Error al eliminar el material $zlcode de proceso_compra: " . $stmt_delete->error . "<br>";
                    }
                }

                // Si el porcentaje está por debajo del 50%, mover a inventario_critico
                if ($porcentaje_inventario < 50) {
                    $check_query = "SELECT id FROM inventario_critico WHERE zlcode = ?";
                    $stmt_check = $conn->prepare($check_query);
                    $stmt_check->bind_param("s", $zlcode);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
    
                    if ($result_check->num_rows == 0) {
                        // Insertar en inventario_critico
                        //echo "Insertando nuevo material crítico: $zlcode (Cantidad actual: $cantidad_actual)<br>";
                        $insert_query = "INSERT INTO inventario_critico (material_id, zlcode, descripcion, max, min, cantidad_actual, cantidad_sugerida) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $cantidad_sugerida = $cantidad_maxima - $cantidad_actual;
                        $stmt_insert = $conn->prepare($insert_query);
                        $stmt_insert->bind_param("issiiii", $material_id, $zlcode, $descripcion, $cantidad_maxima, $minimo, $cantidad_actual, $cantidad_sugerida);
                        
                        if ($stmt_insert->execute()) {
                            // Agregar el material a la lista de nuevos materiales en inventario_critico
                            $nuevos_inventario_critico[] = [
                                'zlcode' => $zlcode,
                                'descripcion' => $descripcion,
                                'cantidad_actual' => $cantidad_actual,
                                'max' => $cantidad_maxima
                            ];
                        } else {
                            echo "Error al insertar el material $zlcode en inventario_critico: " . $stmt_insert->error . "<br>";
                        }
                    }
                } elseif ($porcentaje_inventario >= 50) {
                    // Eliminar de inventario_critico si el porcentaje sube al 50% o más
                    //echo "Eliminando material de inventario_critico: $zlcode (Cantidad actual: $cantidad_actual, Porcentaje: $porcentaje_inventario%)<br>";
                    $delete_query = "DELETE FROM inventario_critico WHERE zlcode = ?";
                    $stmt_delete = $conn->prepare($delete_query);
                    $stmt_delete->bind_param("s", $zlcode);
                    if (!$stmt_delete->execute()) {
                        echo "Error al eliminar el material $zlcode de inventario_critico: " . $stmt_delete->error . "<br>";
                    }
                }
            }
        } else {
            echo "No se encontraron materiales con inventario crítico o en proceso de compra.<br>";
        }

        // Enviar correos si se agregaron nuevos materiales a las tablas
        if (count($nuevos_proceso_compra) > 0) {
            enviarCorreo('jesus.muro@zhongli-la.com', 'Materiales nuevos han alcanzado el límite mínimo', generarMensajeCorreo($nuevos_proceso_compra, 'Inventario en nivel mínimo'));
        }

        if (count($nuevos_inventario_critico) > 0) {
            enviarCorreo('jesus.muro@zhongli-la.com', 'Materiales nuevos en Inventario Crítico', generarMensajeCorreo($nuevos_inventario_critico, 'Inventario Crítico'));
        }

        echo "Verificación de inventario completada.<br>";
    }
}

// Función para generar el cuerpo del mensaje del correo
if (!function_exists('generarMensajeCorreo')) {
    function generarMensajeCorreo($materiales, $tipo) {
        global $conn; // Utilizar la conexión a la base de datos
                
        $mensaje = "Se han agregado los siguientes materiales a la tabla $tipo:\n\n Favor de tomar las medidas correspondientes \n\n";
        foreach ($materiales as $material) {
            $zlcode = $material['zlcode'];
            
            // Realizar la consulta para obtener el valor de 'min' basado en 'zlcode'
            $query_min = "SELECT min FROM materials WHERE zlcode = ?";
            $stmt_min = $conn->prepare($query_min);
            $stmt_min->bind_param("s", $zlcode);
            $stmt_min->execute();
            $result_min = $stmt_min->get_result();
            $min_value = 'No especificada';

            if ($result_min->num_rows > 0) {
                $row_min = $result_min->fetch_assoc();
                $min_value = $row_min['min']; // Obtener el valor de 'min' desde la consulta
            }

            // Generar el cuerpo del mensaje
            $mensaje .= "ZLCode: " . $material['zlcode'] . "\n";
            $mensaje .= "Descripción: " . $material['descripcion'] . "\n";
            $mensaje .= "Cantidad Actual: " . $material['cantidad_actual'] . "\n";
            $mensaje .= "Cantidad Máxima: " . $material['max'] . "\n";
            $mensaje .= "Cantidad Mínima: " . $min_value . "\n";  // Agregar el valor de 'min'
            $mensaje .= "---------------------------------------\n";
        }

        $mensaje .= "\nEste es un mensaje generado automáticamente por el sistema de inventario.\n";
        return $mensaje;
    }
}

// Función para enviar el correo usando la función nativa mail() de PHP con soporte para UTF-8
if (!function_exists('enviarCorreo')) {
    function enviarCorreo($para, $asunto, $mensaje) {
        // Configurar las cabeceras para UTF-8
        $cabeceras = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/plain; charset=UTF-8' . "\r\n";  // Asegurar que el mensaje se envíe en UTF-8
        $cabeceras .= 'From: jesus.muro@zhongli-la.com' . "\r\n";
        $cabeceras .= 'CC: jesus.muro@zhongli-la.com' . "\r\n";
        $cabeceras .= 'Reply-To: jesus.muro@zhongli-la.com' . "\r\n";
        $cabeceras .= 'X-Mailer: PHP/' . phpversion();

        // Codificar el asunto en UTF-8
        $asunto = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

        // Definir los destinatarios (agregar más correos separados por coma)
        //$para = 'veronica.sandoval@zhongli-la.com, pedro.dabdoub@zhongli-la.com, rocio.cortes@zhongli-la.com, diana.duron@zhongli-la.com';

        // Enviar el correo
        if (mail($para, $asunto, $mensaje, $cabeceras)) {
            echo "Correo enviado exitosamente a $para.<br>";
        } else {
            echo "Error al enviar el correo a $para.<br>";
        }
    }
}
?>
