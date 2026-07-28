<?php
// api/admin/estadisticas.php
require_once '../config/config.php';
require_once '../config/conexion.php';
require_once '../config/funciones.php';

iniciarSesionSegura();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    enviarJSON(['error' => 'Acceso denegado.'], 403);
}

 $pdo = db();

// 1. Total de usuarios (rol_id = 2)
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2");
 $totalUsuarios = $stmt->fetch()['total'];

// 2. Total de exámenes rendidos
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM resultados");
 $totalExamenesRendidos = $stmt->fetch()['total'];

// 3. Promedio general de la plataforma
 $stmt = $pdo->query("SELECT AVG(puntaje) as promedio FROM resultados");
 $promedioGeneral = round($stmt->fetch()['promedio'] ?? 0);

// 4. Top 3 exámenes más rendidos
 $stmt = $pdo->query("
    SELECT e.titulo, COUNT(r.id) as veces_rendido, AVG(r.puntaje) as promedio 
    FROM resultados r 
    JOIN examenes e ON r.examen_id = e.id 
    GROUP BY r.examen_id 
    ORDER BY veces_rendido DESC 
    LIMIT 3
");
 $topExamenes = $stmt->fetchAll();

enviarJSON([
    'success' => true,
    'total_usuarios' => $totalUsuarios,
    'total_rendidos' => $totalExamenesRendidos,
    'promedio_general' => $promedioGeneral,
    'top_examenes' => $topExamenes
]);
?>