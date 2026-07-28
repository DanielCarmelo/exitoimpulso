<?php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    enviarJSON(['error' => 'Acceso denegado. Se requiere rol de administrador.'], 403);
}

 $pdo = db();
 $metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM categorias ORDER BY creado_en DESC");
        enviarJSON(['data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $nombre = limpiarInput($data['nombre'] ?? '');
        $descripcion = limpiarInput($data['descripcion'] ?? '');

        if (empty($nombre)) enviarJSON(['error' => 'El nombre de la categoría es obligatorio.']);

        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
        $stmt->execute([$nombre, $descripcion]);
        enviarJSON(['success' => true, 'message' => 'Categoría creada correctamente.']);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $nombre = limpiarInput($data['nombre'] ?? '');
        $descripcion = limpiarInput($data['descripcion'] ?? '');

        if (!$id || empty($nombre)) enviarJSON(['error' => 'Datos inválidos.']);

        $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->execute([$nombre, $descripcion, $id]);
        enviarJSON(['success' => true, 'message' => 'Categoría actualizada.']);
        break;

    case 'DELETE':
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) enviarJSON(['error' => 'ID no proporcionado.']);

        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        enviarJSON(['success' => true, 'message' => 'Categoría eliminada.']);
        break;

    default:
        enviarJSON(['error' => 'Método no permitido'], 405);
        break;
}
?>