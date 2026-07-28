<?php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id'])) {
    enviarJSON(['error' => 'No autorizado.'], 401);
}

// Obtener el ID del examen desde la URL (?examen_id=1)
 $examen_id = filter_var($_GET['examen_id'] ?? null, FILTER_VALIDATE_INT);
if (!$examen_id) {
    enviarJSON(['error' => 'Examen no especificado.']);
}

 $pdo = db();

// Obtener datos del examen
 $stmt = $pdo->prepare("SELECT id, titulo, tiempo_limite_segundos, cantidad_preguntas FROM examenes WHERE id = ?");
 $stmt->execute([$examen_id]);
 $examen = $stmt->fetch();

if (!$examen) {
    enviarJSON(['error' => 'Examen no encontrado.']);
}

// Lógica de cantidad de preguntas al azar
 $limite = "";
if (isset($examen['cantidad_preguntas']) && $examen['cantidad_preguntas'] > 0) {
    $limite = " LIMIT " . intval($examen['cantidad_preguntas']);
}

// Consulta de preguntas FILTRANDO por examen_id
 $sqlPreguntas = "SELECT id, enunciado, multimedia_url, tipo_pregunta, explicacion FROM preguntas WHERE examen_id = ? ORDER BY RAND(){$limite}";
 $stmt = $pdo->prepare($sqlPreguntas);
 $stmt->execute([$examen_id]);
 $preguntas = $stmt->fetchAll();

// Obtener opciones de cada pregunta
foreach ($preguntas as &$pregunta) {
    $stmtOp = $pdo->prepare("SELECT id, texto FROM opciones WHERE pregunta_id = ? ORDER BY RAND()");
    $stmtOp->execute([$pregunta['id']]);
    $pregunta['opciones'] = $stmtOp->fetchAll();
}

enviarJSON(['examen' => $examen, 'preguntas' => $preguntas]);
?>