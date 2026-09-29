<?php
require "config.php";

$usuario_id = $_GET['usuario_id'] ?? null;
if (!$usuario_id) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el parámetro usuario_id"]);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT en.id, en.precio_pagado, en.codigo_qr, en.estado, en.fecha_compra,
            t.nombre AS tipo_nombre, ev.nombre AS evento_nombre, ev.fecha AS evento_fecha
     FROM entradas en
     JOIN tipos_entrada t ON t.id = en.tipo_entrada_id
     JOIN eventos ev ON ev.id = t.evento_id
     WHERE en.usuario_id = :usuario_id
     ORDER BY ev.fecha DESC"
);
$stmt->execute([':usuario_id' => $usuario_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);