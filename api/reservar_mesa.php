<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);

foreach (['usuario_id', 'mesa_id', 'fecha', 'hora', 'cantidad_personas'] as $campo) {
    if (empty($datos[$campo])) {
        http_response_code(400);
        echo json_encode(["error" => "Falta el dato: $campo"]);
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = :id AND activa = 1");
$stmt->execute([':id' => $datos['mesa_id']]);
$mesa = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mesa) {
    http_response_code(404);
    echo json_encode(["error" => "Esa mesa no existe o no está activa"]);
    exit();
}

if ($datos['cantidad_personas'] > $mesa['capacidad']) {
    http_response_code(409);
    echo json_encode(["error" => "La mesa tiene capacidad para {$mesa['capacidad']} personas, no para {$datos['cantidad_personas']}"]);
    exit();
}

$stmtOcupada = $pdo->prepare(
    "SELECT COUNT(*) FROM reservas
     WHERE mesa_id = :mesa_id AND fecha = :fecha AND hora = :hora AND estado = 'confirmada'"
);
$stmtOcupada->execute([
    ':mesa_id' => $datos['mesa_id'],
    ':fecha' => $datos['fecha'],
    ':hora' => $datos['hora'],
]);
if ($stmtOcupada->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Esa mesa ya está reservada en ese horario"]);
    exit();
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO reservas (mesa_id, usuario_id, fecha, hora, cantidad_personas)
         VALUES (:mesa_id, :usuario_id, :fecha, :hora, :cantidad_personas)"
    );
    $stmt->execute([
        ':mesa_id' => $datos['mesa_id'],
        ':usuario_id' => $datos['usuario_id'],
        ':fecha' => $datos['fecha'],
        ':hora' => $datos['hora'],
        ':cantidad_personas' => $datos['cantidad_personas'],
    ]);

    echo json_encode(["exito" => true, "reserva_id" => $pdo->lastInsertId()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al reservar: " . $e->getMessage()]);
}
