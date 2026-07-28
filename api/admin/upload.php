<?php
require_once '../config/config.php';
require_once '../config/funciones.php';

iniciarSesionSegura();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    enviarJSON(['error' => 'Acceso denegado.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['imagen'])) {
    enviarJSON(['error' => 'No se envió ninguna imagen.'], 400);
}

 $archivo = $_FILES['imagen'];
 $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($archivo['type'], $tiposPermitidos)) {
    enviarJSON(['error' => 'Solo se permiten imágenes JPG, PNG o WEBP.'], 400);
}

 $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
 $nombreArchivo = 'preg_' . uniqid() . '.' . $extension;
 $rutaDestino = '../../uploads/' . $nombreArchivo;

if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    enviarJSON(['success' => true, 'ruta' => 'uploads/' . $nombreArchivo]);
} else {
    enviarJSON(['error' => 'Error al guardar la imagen.'], 500);
}
?>