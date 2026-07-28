<?php
// api/auth/cambiarPassword.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarJSON(['error' => 'Método no permitido'], 405);
}

 $data = json_decode(file_get_contents('php://input'), true);
 $token = limpiarInput($data['token'] ?? '');
 $password = $data['password'] ?? '';
 $confirm_password = $data['confirm_password'] ?? '';

if (empty($token) || empty($password)) {
    enviarJSON(['error' => 'Datos incompletos.']);
}

if ($password !== $confirm_password) {
    enviarJSON(['error' => 'Las contraseñas no coinciden.']);
}

// Validar contraseña segura
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
    enviarJSON(['error' => 'La contraseña debe tener mínimo 8 caracteres, 1 mayúscula y 1 número.']);
}

 $pdo = db();

// Verificar si el token existe y no ha expirado
 $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE token_recuperacion = ? AND token_expiracion >= NOW()");
 $stmt->execute([$token]);
 $usuario = $stmt->fetch();

if (!$usuario) {
    enviarJSON(['error' => 'El enlace es inválido o ha expirado.']);
}

// Hashear nueva contraseña
 $hash = password_hash($password, PASSWORD_DEFAULT);

// Actualizar contraseña y borrar el token para que no se reutilice
 $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?");
 $stmt->execute([$hash, $usuario['id']]);

enviarJSON(['success' => true, 'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.']);
?>