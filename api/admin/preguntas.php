<?php
// api/admin/preguntas.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    enviarJSON(['error' => 'Acceso denegado.'], 403);
}

 $pdo = db();
 $metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        $examen_id = filter_var($_GET['examen_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$examen_id) enviarJSON(['error' => 'Examen ID no proporcionado.']);

        $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE examen_id = ? ORDER BY id DESC");
        $stmt->execute([$examen_id]);
        $preguntas = $stmt->fetchAll();

        foreach ($preguntas as &$pregunta) {
            $stmtOp = $pdo->prepare("SELECT * FROM opciones WHERE pregunta_id = ?");
            $stmtOp->execute([$pregunta['id']]);
            $pregunta['opciones'] = $stmtOp->fetchAll();
        }

        enviarJSON(['data' => $preguntas]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $examen_id = filter_var($data['examen_id'] ?? null, FILTER_VALIDATE_INT);
        $enunciado = limpiarInput($data['enunciado'] ?? '');
        $explicacion = limpiarInput($data['explicacion'] ?? '');
        $multimedia_url = limpiarInput($data['multimedia_url'] ?? null);
        $tipo = limpiarInput($data['tipo_pregunta'] ?? 'unica');
        $opciones = $data['opciones'] ?? [];

        if (!$examen_id || empty($enunciado) || count($opciones) < 2) {
            enviarJSON(['error' => 'Faltan datos o necesitas al menos 2 opciones.']);
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO preguntas (examen_id, tipo_pregunta, enunciado, explicacion, multimedia_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$examen_id, $tipo, $enunciado, $explicacion, $multimedia_url]);
            $pregunta_id = $pdo->lastInsertId();

            $stmtOp = $pdo->prepare("INSERT INTO opciones (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)");
            foreach ($opciones as $op) {
                $texto = limpiarInput($op['texto']);
                // AQUÍ ESTÁ LA SOLUCIÓN: filter_var convierte 'true'/'false' a 1 o 0 estrictamente
                $correcta = filter_var($op['es_correcta'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                $stmtOp->execute([$pregunta_id, $texto, $correcta]);
            }

            $pdo->commit();
            enviarJSON(['success' => true, 'message' => 'Pregunta creada correctamente.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            enviarJSON(['error' => 'Error al guardar la pregunta.'], 500);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $enunciado = limpiarInput($data['enunciado'] ?? '');
        $explicacion = limpiarInput($data['explicacion'] ?? '');
        $multimedia_url = limpiarInput($data['multimedia_url'] ?? null);
        $tipo = limpiarInput($data['tipo_pregunta'] ?? 'unica');
        $opciones = $data['opciones'] ?? [];

        if (!$id || empty($enunciado) || count($opciones) < 2) {
            enviarJSON(['error' => 'Datos inválidos para actualizar.']);
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE preguntas SET enunciado = ?, explicacion = ?, multimedia_url = ?, tipo_pregunta = ? WHERE id = ?");
            $stmt->execute([$enunciado, $explicacion, $multimedia_url, $tipo, $id]);

            $stmtDel = $pdo->prepare("DELETE FROM opciones WHERE pregunta_id = ?");
            $stmtDel->execute([$id]);

            $stmtOp = $pdo->prepare("INSERT INTO opciones (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)");
            foreach ($opciones as $op) {
                $texto = limpiarInput($op['texto']);
                // AQUÍ ESTÁ LA SOLUCIÓN: filter_var convierte 'true'/'false' a 1 o 0 estrictamente
                $correcta = filter_var($op['es_correcta'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                $stmtOp->execute([$id, $texto, $correcta]);
            }

            $pdo->commit();
            enviarJSON(['success' => true, 'message' => 'Pregunta actualizada correctamente.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            enviarJSON(['error' => 'Error al actualizar.'], 500);
        }
        break;

    case 'DELETE':
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) enviarJSON(['error' => 'ID no proporcionado.']);

        $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id = ?");
        $stmt->execute([$id]);
        enviarJSON(['success' => true, 'message' => 'Pregunta eliminada.']);
        break;

    default:
        enviarJSON(['error' => 'Método no permitido'], 405);
        break;
}
?>