<?php
// api/config/conexion.php

require_once 'config.php';

class Conexion {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Errores lanzados como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch asociativo por defecto
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Usar prepared statements reales
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción registrar esto en un log, no mostrarlo al usuario
            die('Error de conexión a la base de datos.'); 
        }
    }

    // Obtener la instancia única
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Conexion();
        }
        return self::$instance;
    }

    // Obtener el objeto PDO directamente
    public function getConexion() {
        return $this->pdo;
    }

    // Evitar clonación y deserialización
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }
}

// Función helper para usar rápidamente
function db() {
    return Conexion::getInstance()->getConexion();
}
?>