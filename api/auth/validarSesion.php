<?php
// api/auth/validarSesion.php
require_once '../config/config.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

// Si hay un ID de usuario en la sesión, está logueado
if (isset($_SESSION['usuario_id'])) {
    enviarJSON([
        'logueado' => true,
        'usuario_id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['nombre'],
        'rol_id' => $_SESSION['rol_id']
    ]);
} else {
    enviarJSON(['logueado' => false], 401);
}
?>