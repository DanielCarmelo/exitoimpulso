<?php
// api/admin/importarPreguntas.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    enviarJSON(['error' => 'Acceso denegado.'], 403);
}

 $data = json_decode(file_get_contents('php://input'), true);
 $examen_id = filter_var($data['examen_id'] ?? null, FILTER_VALIDATE_INT);
 $preguntas = $data['preguntas'] ?? [];

if (!$examen_id || empty($preguntas)) {
    enviarJSON(['error' => 'Examen no seleccionado o no hay preguntas para importar.']);
}

 $pdo = db();
try {
    $pdo->beginTransaction();
    
    $stmtPreg = $pdo->prepare("INSERT INTO preguntas (examen_id, tipo_pregunta, enunciado, explicacion) VALUES (?, 'unica', ?, ?)");
    $stmtOp = $pdo->prepare("INSERT INTO opciones (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)");

    $count = 0;
    foreach ($preguntas as $p) {
        
        // 1. NORMALIZAR CLAVES (Acepta q, p, enunciado)
        $enunciado = $p['enunciado'] ?? $p['q'] ?? $p['p'] ?? '';
        $explicacion = $p['explicacion'] ?? $p['r'] ?? '';
        
        // Limpiar datos
        $enunciado = limpiarInput($enunciado);
        $explicacion = limpiarInput($explicacion);
        
        $stmtPreg->execute([$examen_id, $enunciado, $explicacion]);
        $pregunta_id = $pdo->lastInsertId();
        
        // 2. NORMALIZAR OPCIONES (Acepta formato string o formato objeto)
        $opciones_raw = $p['opciones'] ?? $p['o'] ?? [];
        $opciones_texto = [];
        
        foreach ($opciones_raw as $op) {
            if (is_array($op)) {
                $opciones_texto[] = $op['texto'];
            } else {
                $opciones_texto[] = $op;
            }
        }
        
        // 3. DETERMINAR RESPUESTA CORRECTA
        $respuesta_correcta_texto = $p['a'] ?? null;
        $indice_correcto = -1;

        // Si el formato ya traía es_correcta (formato nativo PHP)
        if ($respuesta_correcta_texto === null) {
            foreach ($opciones_raw as $i => $op) {
                if (is_array($op) && isset($op['es_correcta']) && $op['es_correcta']) {
                    $indice_correcto = $i;
                    break;
                }
            }
        } else {
            // Buscar coincidencia EXACTA
            foreach ($opciones_texto as $i => $texto_op) {
                if (trim($texto_op) === trim($respuesta_correcta_texto)) {
                    $indice_correcto = $i;
                    break;
                }
            }
            
            // Si no hay exacta, BUSCAR LA DE MAYOR SIMILITUD DE CARACTERES
            if ($indice_correcto === -1) {
                $max_similitud = 0;
                foreach ($opciones_texto as $i => $texto_op) {
                    similar_text(strtolower($texto_op), strtolower($respuesta_correcta_texto), $porcentaje);
                    if ($porcentaje > $max_similitud) {
                        $max_similitud = $porcentaje;
                        $indice_correcto = $i;
                    }
                }
            }
        }

        // 4. INSERTAR OPCIONES CON EL ÍNDICE CORRECTO CALCULADO
        foreach ($opciones_texto as $i => $texto) {
            $texto_limpio = limpiarInput($texto);
            $correcta = ($i === $indice_correcto) ? 1 : 0;
            $stmtOp->execute([$pregunta_id, $texto_limpio, $correcta]);
        }
        
        $count++;
    }

    $pdo->commit();
    enviarJSON(['success' => true, 'message' => "$count preguntas importadas correctamente."]);
} catch (Exception $e) {
    $pdo->rollBack();
    enviarJSON(['error' => 'Error al importar: ' . $e->getMessage()], 500);
}
?>