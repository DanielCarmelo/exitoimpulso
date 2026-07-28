<?php
// api/auth/registro.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

// Solo permitir peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarJSON(['error' => 'Método no permitido'], 405);
}

// Obtener datos JSON del Fetch API
 $data = json_decode(file_get_contents('php://input'), true);

// Validar CSRF (si implementas tokens en el frontend, aquí se validan)
// if (!validarTokenCSRF($data['csrf_token'] ?? '')) {
//     enviarJSON(['error' => 'Token CSRF inválido'], 403);
// }

 $nombre = limpiarInput($data['nombre'] ?? '');
 $correo = filter_var($data['correo'] ?? '', FILTER_VALIDATE_EMAIL);
 $celular = limpiarInput($data['celular'] ?? '');
 $password = $data['password'] ?? '';
 $confirm_password = $data['confirm_password'] ?? '';

// Validaciones
if (empty($nombre) || !$correo || empty($celular) || empty($password)) {
    enviarJSON(['error' => 'Todos los campos son obligatorios y el correo debe ser válido.']);
}

if ($password !== $confirm_password) {
    enviarJSON(['error' => 'Las contraseñas no coinciden.']);
}

// Validar contraseña segura (mínimo 8 caracteres, 1 número, 1 mayúscula)
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
    enviarJSON(['error' => 'La contraseña debe tener mínimo 8 caracteres, 1 mayúscula y 1 número.']);
}

try {
    $pdo = db();
    
    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        enviarJSON(['error' => 'El correo electrónico ya está registrado.']);
    }

    // Hashear contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario (rol_id = 2 para usuarios normales)
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, celular, password_hash, rol_id) VALUES (?, ?, ?, ?, 2)");
    $stmt->execute([$nombre, $correo, $celular, $hash]);

    enviarJSON(['success' => true, 'message' => 'Registro exitoso. Ahora puedes iniciar sesión.']);

} catch (PDOException $e) {
    error_log("Error en registro: " . $e->getMessage());
    enviarJSON(['error' => 'Error interno del servidor.'], 500);
}
?>