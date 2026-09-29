<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);

if (empty($datos['usuario_id']) || empty($datos['tipo_entrada_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos: usuario_id y tipo_entrada_id son obligatorios"]);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM tipos_entrada WHERE id = :id");
$stmt->execute([':id' => $datos['tipo_entrada_id']]);
$tipo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tipo) {
    http_response_code(404);
    echo json_encode(["error" => "Ese tipo de entrada no existe"]);
    exit();
}

if ($tipo['cupo'] !== null) {
    $stmtCupo = $pdo->prepare("SELECT COUNT(*) FROM entradas WHERE tipo_entrada_id = :id AND estado != 'cancelada'");
    $stmtCupo->execute([':id' => $tipo['id']]);
    if ($stmtCupo->fetchColumn() >= $tipo['cupo']) {
        http_response_code(409);
        echo json_encode(["error" => "No quedan entradas disponibles de este tipo"]);
        exit();
    }
}

$stmtSocio = $pdo->prepare("SELECT activo FROM socios WHERE usuario_id = :id");
$stmtSocio->execute([':id' => $datos['usuario_id']]);
$socio = $stmtSocio->fetch(PDO::FETCH_ASSOC);
$es_socio_activo = $socio && $socio['activo'] == 1;

$precio_final = ($es_socio_activo && $tipo['precio_socio'] !== null)
    ? $tipo['precio_socio']
    : $tipo['precio'];

$codigo_qr = bin2hex(random_bytes(16));

try {
    $stmt = $pdo->prepare(
        "INSERT INTO entradas (tipo_entrada_id, usuario_id, precio_pagado, codigo_qr)
         VALUES (:tipo_entrada_id, :usuario_id, :precio_pagado, :codigo_qr)"
    );
    $stmt->execute([
        ':tipo_entrada_id' => $tipo['id'],
        ':usuario_id' => $datos['usuario_id'],
        ':precio_pagado' => $precio_final,
        ':codigo_qr' => $codigo_qr,
    ]);

    echo json_encode([
        "exito" => true,
        "entrada_id" => $pdo->lastInsertId(),
        "precio_pagado" => (float) $precio_final,
        "codigo_qr" => $codigo_qr
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al comprar la entrada: " . $e->getMessage()]);
}