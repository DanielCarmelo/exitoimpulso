<?php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id'])) {
    enviarJSON(['error' => 'No autorizado.'], 401);
}

 $usuario_id = $_SESSION['usuario_id'];
 $pdo = db();

 $stmt = $pdo->prepare("
    SELECT r.id, r.puntaje, r.correctas, r.total_preguntas, r.fecha, r.codigo_verificacion, e.titulo as examen_titulo, c.nombre as categoria_nombre
    FROM resultados r
    JOIN examenes e ON r.examen_id = e.id
    JOIN categorias c ON e.categoria_id = c.id
    WHERE r.usuario_id = ?
    ORDER BY r.fecha DESC
");
 $stmt->execute([$usuario_id]);
 $historial = $stmt->fetchAll();

 $totalExamenes = count($historial);
 $promedio = 0;
 $mejor = 0;

if ($totalExamenes > 0) {
    $suma = 0;
    foreach ($historial as $h) {
        $suma += $h['puntaje'];
        if ($h['puntaje'] > $mejor) {
            $mejor = $h['puntaje'];
        }
    }
    $promedio = round($suma / $totalExamenes);
}

enviarJSON([
    'stats' => [
        'total' => $totalExamenes,
        'promedio' => $promedio,
        'mejor' => $mejor
    ],
    'historial' => $historial
]);
?>