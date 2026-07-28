<?php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

 $data = json_decode(file_get_contents('php://input'), true);
 $jugadores = $data['jugadores'] ?? [];
 $tipo_juego = limpiarInput($data['tipo_juego'] ?? 'trivia');

if (empty($jugadores)) {
    enviarJSON(['error' => 'No hay datos de jugadores.']);
}

 $pdo = db();
 $stmt = $pdo->prepare("INSERT INTO juegos_ranking (nombre, puntaje, tipo_juego) VALUES (?, ?, ?)");

foreach ($jugadores as $j) {
    $nombre = limpiarInput($j['nombre']);
    $puntaje = filter_var($j['aciertos'], FILTER_VALIDATE_INT);
    $stmt->execute([$nombre, $puntaje, $tipo_juego]);
}

enviarJSON(['success' => true, 'message' => 'Puntajes guardados en el ranking.']);
?>