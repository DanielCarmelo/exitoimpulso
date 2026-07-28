<?php
// api/admin/usuarios.php
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
        // Listar todos los usuarios (sin mostrar las contraseñas)
        $stmt = $pdo->query("SELECT id, nombre, correo, celular, rol_id, estado, creado_en FROM usuarios ORDER BY creado_en DESC");
        enviarJSON(['data' => $stmt->fetchAll()]);
        break;

    case 'PUT':
        // Actualizar rol o estado de un usuario
        $data = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $campo = $data['campo'] ?? ''; // 'rol_id' o 'estado'
        $valor = filter_var($data['valor'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || !$campo || $valor === null) {
            enviarJSON(['error' => 'Datos inválidos.']);
        }

        // Validar que el campo sea permitido por seguridad
        if (!in_array($campo, ['rol_id', 'estado'])) {
            enviarJSON(['error' => 'Campo no permitido.']);
        }

        // Evitar que un admin se bloquee a sí mismo o se quite el rol de admin
        if ($id == $_SESSION['usuario_id']) {
            enviarJSON(['error' => 'No puedes modificar tu propio rol o estado.']);
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET $campo = ? WHERE id = ?");
        $stmt->execute([$valor, $id]);
        enviarJSON(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
        break;

    case 'DELETE':
        // Eliminar usuario
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) enviarJSON(['error' => 'ID no proporcionado.']);

        if ($id == $_SESSION['usuario_id']) {
            enviarJSON(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        enviarJSON(['success' => true, 'message' => 'Usuario eliminado.']);
        break;

    default:
        enviarJSON(['error' => 'Método no permitido'], 405);
        break;
}
?>