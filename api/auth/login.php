<?php
// api/auth/login.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarJSON(['error' => 'Método no permitido'], 405);
}

 $data = json_decode(file_get_contents('php://input'), true);

 $correo = filter_var($data['correo'] ?? '', FILTER_VALIDATE_EMAIL);
 $password = $data['password'] ?? '';

if (!$correo || empty($password)) {
    enviarJSON(['error' => 'Correo y contraseña son obligatorios.']);
}

try {
    $pdo = db();

    // Buscar usuario por correo
    $stmt = $pdo->prepare("SELECT id, nombre, password_hash, rol_id, estado FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    // Registrar intento en historial_login
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        if ($usuario['estado'] != 1) {
            enviarJSON(['error' => 'Tu cuenta está desactivada. Contacta al administrador.']);
        }

        // Login correcto: guardar en sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol_id'] = $usuario['rol_id'];

        // Registrar en historial (éxito = 1)
        $stmtLog = $pdo->prepare("INSERT INTO historial_login (usuario_id, ip, user_agent, exito) VALUES (?, ?, ?, 1)");
        $stmtLog->execute([$usuario['id'], $ip, $user_agent]);

        enviarJSON([
            'success' => true, 
            'message' => 'Inicio de sesión exitoso.',
            'redirect' => $usuario['rol_id'] == 1 ? 'administrador/index.html' : 'opciones del portal/index.html'
        ]);

    } else {
        // Login incorrecto
        if ($usuario) {
            $stmtLog = $pdo->prepare("INSERT INTO historial_login (usuario_id, ip, user_agent, exito) VALUES (?, ?, ?, 0)");
            $stmtLog->execute([$usuario['id'], $ip, $user_agent]);
        }
        enviarJSON(['error' => 'Correo o contraseña incorrectos.']);
    }

} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    enviarJSON(['error' => 'Error interno del servidor.'], 500);
}
?>