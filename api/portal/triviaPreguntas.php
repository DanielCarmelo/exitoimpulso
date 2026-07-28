<?php
// api/portal/triviaPreguntas.php
header('Content-Type: application/json; charset=utf-8');

 $ruta = '../../database/trivia.json';

if (file_exists($ruta)) {
    echo file_get_contents($ruta);
} else {
    echo json_encode([]); // Devuelve array vacío si no encuentra el archivo
}
?>