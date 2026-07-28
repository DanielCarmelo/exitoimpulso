<?php
// api/auth/logout.php
require_once '../config/config.php'; // ESTA LÍNEA FALTABA
require_once '../config/funciones.php';

iniciarSesionSegura();

// Destruir todas las variables de sesión
 $_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

enviarJSON(['success' => true, 'message' => 'Sesión cerrada correctamente.']);
?>