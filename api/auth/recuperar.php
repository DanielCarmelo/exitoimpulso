<?php
// api/auth/recuperar.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarJSON(['error' => 'Método no permitido'], 405);
}

 $data = json_decode(file_get_contents('php://input'), true);
 $correo = filter_var($data['correo'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$correo) {
    enviarJSON(['error' => 'Debe ingresar un correo electrónico válido.']);
}

 $pdo = db();

// Buscar usuario por correo
 $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE correo = ?");
 $stmt->execute([$correo]);
 $usuario = $stmt->fetch();

if ($usuario) {
    // Generar token seguro (32 bytes = 64 caracteres hex)
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Guardar token en la base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?");
    $stmt->execute([$token, $expiracion, $usuario['id']]);
    
    // Crear enlace de recuperación
    $link = "/recuperar/cambiar.html?token=" . $token;
    
    // --- MODO PRODUCCIÓN (Descomenta esto en un hosting real) ---
    /*
    $asunto = "Recuperación de Contraseña - ÉxitoImpulso";
    $mensaje = "Hola " . $usuario['nombre'] . ",\n\nHas solicitado restablecer tu contraseña.\n\nHaz clic en el siguiente enlace (válido por 1 hora):\n" . $link . "\n\nSi no fuiste tú, ignora este correo.";
    $headers = "From: no-reply@exitoimpulso.com";
    mail($correo, $asunto, $mensaje, $headers);
    */
    
    // --- MODO DESARROLLO (Para probar en XAMPP) ---
    // Devolvemos el link en la respuesta JSON para poder hacer clic en él
    enviarJSON([
        'success' => true, 
        'message' => 'Se ha enviado un enlace de recuperación a tu correo.',
        'dev_link' => $link // <-- Trampa para probar en local
    ]);
} else {
    // Por seguridad, no revelamos si el correo existe o no.
    enviarJSON([
        'success' => true, 
        'message' => 'Se ha enviado un enlace de recuperación a tu correo.'
    ]);
}
?>