<?php
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
        $stmt = $pdo->query("SELECT e.*, c.nombre as categoria_nombre FROM examenes e JOIN categorias c ON e.categoria_id = c.id ORDER BY e.creado_en DESC");
        enviarJSON(['data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $categoria_id = filter_var($data['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $titulo = limpiarInput($data['titulo'] ?? '');
        $descripcion = limpiarInput($data['descripcion'] ?? '');
        $tiempo = filter_var($data['tiempo_limite_segundos'] ?? 0, FILTER_VALIDATE_INT);
        $cantidad = filter_var($data['cantidad_preguntas'] ?? 0, FILTER_VALIDATE_INT);
        $nota = filter_var($data['nota_aprobacion'] ?? 60, FILTER_VALIDATE_INT); // NUEVO

        if (!$categoria_id || empty($titulo)) {
            enviarJSON(['error' => 'Categoría y título son obligatorios.']);
        }

        $stmt = $pdo->prepare("INSERT INTO examenes (categoria_id, titulo, descripcion, tiempo_limite_segundos, cantidad_preguntas, nota_aprobacion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$categoria_id, $titulo, $descripcion, $tiempo, $cantidad, $nota]);
        enviarJSON(['success' => true, 'message' => 'Examen creado correctamente.']);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $categoria_id = filter_var($data['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $titulo = limpiarInput($data['titulo'] ?? '');
        $descripcion = limpiarInput($data['descripcion'] ?? '');
        $tiempo = filter_var($data['tiempo_limite_segundos'] ?? 0, FILTER_VALIDATE_INT);
        $cantidad = filter_var($data['cantidad_preguntas'] ?? 0, FILTER_VALIDATE_INT);
        $nota = filter_var($data['nota_aprobacion'] ?? 60, FILTER_VALIDATE_INT); // NUEVO

        if (!$id || !$categoria_id || empty($titulo)) {
            enviarJSON(['error' => 'Datos inválidos.']);
        }

        $stmt = $pdo->prepare("UPDATE examenes SET categoria_id = ?, titulo = ?, descripcion = ?, tiempo_limite_segundos = ?, cantidad_preguntas = ?, nota_aprobacion = ? WHERE id = ?");
        $stmt->execute([$categoria_id, $titulo, $descripcion, $tiempo, $cantidad, $nota, $id]);
        enviarJSON(['success' => true, 'message' => 'Examen actualizado.']);
        break;

    case 'DELETE':
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) enviarJSON(['error' => 'ID no proporcionado.']);

        $stmt = $pdo->prepare("DELETE FROM examenes WHERE id = ?");
        $stmt->execute([$id]);
        enviarJSON(['success' => true, 'message' => 'Examen eliminado.']);
        break;

    default:
        enviarJSON(['error' => 'Método no permitido'], 405);
        break;
}
?>