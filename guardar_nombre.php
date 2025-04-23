<?php
// Ruta al archivo JSON
$jsonFile = 'hex_names.json';

// Leer el contenido actual
$data = json_decode(file_get_contents($jsonFile), true);

// Obtener datos enviados por POST
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'];
$nombre = $input['nombre'];

// Validar que existan los datos necesarios
if (!isset($id) || !isset($nombre)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Actualizar o agregar el nombre en el array
$data[$id] = $nombre;

// Guardar de nuevo el archivo JSON
file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true, 'id' => $id, 'nombre' => $nombre]);
?>
