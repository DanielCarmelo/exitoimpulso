<?php
// api/portal/tips_noticias.php
header('Content-Type: application/json; charset=utf-8');

 $ruta = '../../database/tips_noticias.json';

if (file_exists($ruta)) {
    $json = file_get_contents($ruta);
    echo $json;
} else {
    echo json_encode(['error' => 'No se encontraron tips.']);
}
?>