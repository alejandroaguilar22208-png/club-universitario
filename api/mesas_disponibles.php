<?php
require "config.php";

$fecha = $_GET['fecha'] ?? null;
if (!$fecha) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el parámetro fecha (ej: ?fecha=2026-10-15)"]);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT * FROM mesas
     WHERE activa = 1
     AND id NOT IN (
         SELECT mesa_id FROM reservas
         WHERE fecha = :fecha AND estado = 'confirmada'
     )"
);
$stmt->execute([':fecha' => $fecha]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);