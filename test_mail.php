<?php
$para = 'jesus.muro@zhongli-la.com';
$asunto = 'Prueba de envío de correo desde PHP';
$mensaje = 'Este es un mensaje de prueba.';
$cabeceras = 'From: jesus.muro@zhongli-la.com' . "\r\n" .
             'Reply-To: jesus.muro@zhongli-la.com' . "\r\n" .
             'X-Mailer: PHP/' . phpversion();

if(mail($para, $asunto, $mensaje, $cabeceras)) {
    echo "Correo enviado exitosamente.";
} else {
    echo "Error al enviar el correo.";
}
?>
