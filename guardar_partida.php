<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }

    $timestamp = date('Ymd_His');
    $filename = "saves/partida_$timestamp.json";

    if (!is_dir('saves')) {
        mkdir('saves', 0777, true);
    }

    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
