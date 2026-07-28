<?php
// api/portal/rankingJuegos.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php'; // ¡ESTA LÍNEA FALTABA!

header('Content-Type: application/json; charset=utf-8');

 $tipo_juego = isset($_GET['tipo']) ? limpiarInput($_GET['tipo']) : 'trivia';

 $pdo = db();
// Top 10 mejores puntajes de la historia para ese juego
 $stmt = $pdo->prepare("SELECT nombre, puntaje, fecha FROM juegos_ranking WHERE tipo_juego = ? ORDER BY puntaje DESC, fecha ASC LIMIT 10");
 $stmt->execute([$tipo_juego]);
echo json_encode(['data' => $stmt->fetchAll()]);
?>