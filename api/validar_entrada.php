<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);
if (empty($datos['codigo_qr'])) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el código QR"]);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT e.*, u.nombre AS usuario_nombre
     FROM entradas e
     JOIN usuarios u ON u.id = e.usuario_id
     WHERE e.codigo_qr = :codigo"
);
$stmt->execute([':codigo' => $datos['codigo_qr']]);
$entrada = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entrada) {
    http_response_code(404);
    echo json_encode(["error" => "Entrada no encontrada — código inválido"]);
    exit();
}
if ($entrada['estado'] === 'usada') {
    http_response_code(409);
    echo json_encode(["error" => "Esta entrada ya fue usada el " . $entrada['fecha_uso']]);
    exit();
}
if ($entrada['estado'] === 'cancelada') {
    http_response_code(409);
    echo json_encode(["error" => "Esta entrada está cancelada"]);
    exit();
}

$stmtUpdate = $pdo->prepare("UPDATE entradas SET estado = 'usada', fecha_uso = NOW() WHERE id = :id");
$stmtUpdate->execute([':id' => $entrada['id']]);

$stmtVisita = $pdo->prepare("INSERT INTO visitas (usuario_id, tipo) VALUES (:usuario_id, 'entrada_boliche')");
$stmtVisita->execute([':usuario_id' => $entrada['usuario_id']]);

echo json_encode([
    "exito" => true,
    "mensaje" => "Ingreso permitido",
    "usuario_nombre" => $entrada['usuario_nombre']
])