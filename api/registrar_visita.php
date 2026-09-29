<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);
if (empty($datos['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Falta usuario_id"]);
    exit();
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO visitas (usuario_id, tipo, monto_consumido)
         VALUES (:usuario_id, 'quincho', :monto)"
    );
    $stmt->execute([
        ':usuario_id' => $datos['usuario_id'],
        ':monto' => $datos['monto_consumido'] ?? 0,
    ]);

    echo json_encode(["exito" => true, "visita_id" => $pdo->lastInsertId()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al registrar la visita: " . $e->getMessage()]);
}