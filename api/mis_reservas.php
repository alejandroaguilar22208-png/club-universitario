<?php
require "config.php";

$usuario_id = $_GET['usuario_id'] ?? null;
if (!$usuario_id) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el parámetro usuario_id"]);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT r.id, r.fecha, r.hora, r.cantidad_personas, r.estado, m.numero AS mesa_numero
     FROM reservas r
     JOIN mesas m ON m.id = r.mesa_id
     WHERE r.usuario_id = :usuario_id
     ORDER BY r.fecha DESC"
);
$stmt->execute([':usuario_id' => $usuario_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);