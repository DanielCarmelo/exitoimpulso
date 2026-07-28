<?php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id'])) {
    enviarJSON(['error' => 'No autorizado.'], 401);
}

 $data = json_decode(file_get_contents('php://input'), true);
 $examen_id = filter_var($data['examen_id'] ?? null, FILTER_VALIDATE_INT);
 $respuestasUsuario = $data['respuestas'] ?? [];
 $preguntasIds = $data['preguntas_ids'] ?? []; // Lista de IDs en el orden exacto que el usuario los vio

if (!$examen_id) enviarJSON(['error' => 'Examen no especificado.']);

 $pdo = db();

// Si por alguna razón no llegaron IDs, usamos las claves de las respuestas
if (empty($preguntasIds)) {
    $preguntasIds = array_keys($respuestasUsuario);
}

if (empty($preguntasIds)) {
    enviarJSON([
        'success' => true,
        'puntaje' => 0,
        'correctas' => 0,
        'total' => 0,
        'detalle' => []
    ]);
}

 $placeholders = implode(',', array_fill(0, count($preguntasIds), '?'));

// 1. OBTENER LAS PREGUNTAS DESDE LA BASE DE DATOS
 $stmt = $pdo->prepare("SELECT id, enunciado, multimedia_url, explicacion FROM preguntas WHERE id IN ($placeholders)");
 $stmt->execute($preguntasIds);
 $preguntasDB = $stmt->fetchAll();

// 2. OBTENER LAS OPCIONES DESDE LA BASE DE DATOS
 $stmtOp = $pdo->prepare("SELECT id, pregunta_id, texto, es_correcta FROM opciones WHERE pregunta_id IN ($placeholders)");
 $stmtOp->execute($preguntasIds);
 $opcionesDB = $stmtOp->fetchAll();

// 3. REORDENAR LAS PREGUNTAS PARA QUE COINCIDAN CON EL ORDEN DEL EXAMEN
// MySQL las devuelve ordenadas por ID, así que las reordenamos manualmente
 $preguntasOrdenadas = [];
foreach ($preguntasIds as $pid) {
    foreach ($preguntasDB as $pre) {
        if ($pre['id'] == $pid) {
            $preguntasOrdenadas[] = $pre;
            break;
        }
    }
}

 $correctasCount = 0;
 $resultadoDetallado = [];

// 4. RECORRER LAS PREGUNTAS (AHORA EN EL ORDEN CORRECTO) Y CORREGIR
foreach ($preguntasOrdenadas as $pre) {
    $opcionesPregunta = [];
    $respuestasCorrectasDB = [];
    
    foreach ($opcionesDB as $op) {
        if ($op['pregunta_id'] == $pre['id']) {
            $opcionesPregunta[] = $op;
            if ($op['es_correcta'] == 1) {
                $respuestasCorrectasDB[] = (int)$op['id'];
            }
        }
    }

    // Respuestas que dio el usuario (si no respondió, queda vacío)
    $respuestasUsuarioPre = isset($respuestasUsuario[$pre['id']]) ? array_map('intval', $respuestasUsuario[$pre['id']]) : [];
    
    sort($respuestasUsuarioPre);
    sort($respuestasCorrectasDB);
    $esCorrecta = ($respuestasUsuarioPre === $respuestasCorrectasDB);

    if ($esCorrecta) $correctasCount++;

    $resultadoDetallado[] = [
        'pregunta_id' => $pre['id'],
        'enunciado' => $pre['enunciado'],
        'multimedia_url' => $pre['multimedia_url'],
        'explicacion' => $pre['explicacion'],
        'es_correcta' => $esCorrecta,
        'respuestas_usuario' => $respuestasUsuarioPre,
        'opciones' => $opcionesPregunta
    ];
}

 $totalPreguntas = count($preguntasOrdenadas);
 $puntaje = $totalPreguntas > 0 ? round(($correctasCount / $totalPreguntas) * 100) : 0;

// 5. GUARDAR EN HISTORIAL
 $codigoVerificacion = bin2hex(random_bytes(16));

// OBTENER LA NOTA MÍNIMA DE APROBACIÓN DEL EXAMEN
 $stmtExam = $pdo->prepare("SELECT nota_aprobacion FROM examenes WHERE id = ?");
 $stmtExam->execute([$examen_id]);
 $examenData = $stmtExam->fetch();
 $notaAprobacion = $examenData['nota_aprobacion'] ?? 60; // Por defecto 60 si no existe

// DETERMINAR SI APROBÓ
 $aprobado = ($puntaje >= $notaAprobacion);

// GUARDAR EN HISTORIAL
 $stmtHist = $pdo->prepare("INSERT INTO resultados (usuario_id, examen_id, puntaje, correctas, total_preguntas, fecha, codigo_verificacion) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
 $stmtHist->execute([$_SESSION['usuario_id'], $examen_id, $puntaje, $correctasCount, $totalPreguntas, $codigoVerificacion]);

enviarJSON([
    'success' => true,
    'puntaje' => $puntaje,
    'correctas' => $correctasCount,
    'total' => $totalPreguntas,
    'aprobado' => $aprobado, // NUEVO: le decimos al JS si aprobó
    'nota_minima' => $notaAprobacion, // NUEVO: le mandamos la nota mínima
    'detalle' => $resultadoDetallado
]);
?>