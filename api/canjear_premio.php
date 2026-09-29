<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);
if (empty($datos['usuario_id']) || empty($datos['premio_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos: usuario_id y premio_id son obligatorios"]);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM premios WHERE id = :id AND activo = 1");
$stmt->execute([':id' => $datos['premio_id']]);
$premio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$premio) {
    http_response_code(404);
    echo json_encode(["error" => "Ese premio no existe"]);
    exit();
}

if ($premio['tipo_condicion'] === 'cantidad_visitas') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitas WHERE usuario_id = :id AND tipo = 'quincho'");
} else {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto_consumido), 0) FROM visitas WHERE usuario_id = :id");
}
$stmt->execute([':id' => $datos['usuario_id']]);
$valor_actual = $stmt->fetchColumn();

if ($valor_actual < $premio['valor_condicion']) {
    http_response_code(409);
    echo json_encode(["error" => "Todavía no cumplís la condición para este premio"]);
    exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM canjes WHERE usuario_id = :usuario_id AND premio_id = :premio_id");
$stmt->execute([':usuario_id' => $datos['usuario_id'], ':premio_id' => $datos['premio_id']]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Ya canjeaste este premio antes"]);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO canjes (usuario_id, premio_id) VALUES (:usuario_id, :premio_id)");
    $stmt->execute([':usuario_id' => $datos['usuario_id'], ':premio_id' => $datos['premio_id']]);

    echo json_encode(["exito" => true, "canje_id" => $pdo->lastInsertId(), "mensaje" => "Premio canjeado, pendiente de entrega"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al canjear: " . $e->getMessage()]);
}