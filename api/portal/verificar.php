<?php
// api/portal/verificar.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

header('Content-Type: application/json; charset=utf-8');

 $codigo = limpiarInput($_GET['codigo'] ?? '');

if (empty($codigo)) {
    echo json_encode(['success' => false, 'error' => 'Código vacío']);
    exit;
}

 $pdo = db();

 $stmt = $pdo->prepare("
    SELECT u.nombre, e.titulo, r.puntaje, r.fecha 
    FROM resultados r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN examenes e ON r.examen_id = e.id
    WHERE r.codigo_verificacion = ?
");
 $stmt->execute([$codigo]);
 $resultado = $stmt->fetch();

if ($resultado) {
    $fecha = new DateTime($resultado['fecha']);
    echo json_encode([
        'success' => true,
        'usuario' => $resultado['nombre'],
        'examen' => $resultado['titulo'],
        'puntaje' => $resultado['puntaje'],
        'fecha' => $fecha->format('d/m/Y H:i:s')
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Código no encontrado']);
}
?>