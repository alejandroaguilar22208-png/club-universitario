<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);

if (empty($datos['nombre']) || empty($datos['fecha']) || empty($datos['hora_apertura'])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos: nombre, fecha y hora_apertura son obligatorios"]);
    exit();
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        "INSERT INTO eventos (nombre, descripcion, fecha, hora_apertura, capacidad_maxima)
         VALUES (:nombre, :descripcion, :fecha, :hora_apertura, :capacidad_maxima)"
    );
    $stmt->execute([
        ':nombre' => $datos['nombre'],
        ':descripcion' => $datos['descripcion'] ?? null,
        ':fecha' => $datos['fecha'],
        ':hora_apertura' => $datos['hora_apertura'],
        ':capacidad_maxima' => $datos['capacidad_maxima'] ?? null,
    ]);
    $evento_id = $pdo->lastInsertId();

    $stmtTipo = $pdo->prepare(
        "INSERT INTO tipos_entrada (evento_id, nombre, precio, precio_socio, cupo)
         VALUES (:evento_id, :nombre, :precio, :precio_socio, :cupo)"
    );
    foreach ($datos['tipos_entrada'] ?? [] as $tipo) {
        $stmtTipo->execute([
            ':evento_id' => $evento_id,
            ':nombre' => $tipo['nombre'],
            ':precio' => $tipo['precio'],
            ':precio_socio' => $tipo['precio_socio'] ?? null,
            ':cupo' => $tipo['cupo'] ?? null,
        ]);
    }

    $pdo->commit();
    echo json_encode(["exito" => true, "evento_id" => $evento_id]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error al crear el evento: " . $e->getMessage()]);
}