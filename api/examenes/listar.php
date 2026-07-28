<?php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id'])) {
    enviarJSON(['error' => 'No autorizado.'], 401);
}

 $pdo = db();
 $stmt = $pdo->query("
    SELECT e.id, e.titulo, e.descripcion, e.tiempo_limite_segundos, c.nombre as categoria 
    FROM examenes e 
    JOIN categorias c ON e.categoria_id = c.id
    ORDER BY e.titulo ASC
");
enviarJSON(['data' => $stmt->fetchAll()]);
?>