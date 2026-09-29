<?php
require "config.php";

$usuario_id = $_GET['usuario_id'] ?? null;
if (!$usuario_id) {
    http_response_code(400);
    echo json_encode(["error" => "Falta el parámetro usuario_id"]);
    exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM visitas WHERE usuario_id = :id AND tipo = 'quincho'");
$stmt->execute([':id' => $usuario_id]);
$cantidad_visitas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto_consumido), 0) FROM visitas WHERE usuario_id = :id");
$stmt->execute([':id' => $usuario_id]);
$monto_acumulado = (float) $stmt->fetchColumn();

$premios = $pdo->query("SELECT * FROM premios WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT premio_id FROM canjes WHERE usuario_id = :id");
$stmt->execute([':id' => $usuario_id]);
$ya_canjeados = $stmt->fetchAll(PDO::FETCH_COLUMN);

$resultado = [];
foreach ($premios as $premio) {
    $cumple = $premio['tipo_condicion'] === 'cantidad_visitas'
        ? $cantidad_visitas >= $premio['valor_condicion']
        : $monto_acumulado >= $premio['valor_condicion'];

    $resultado[] = [
        "id" => $premio['id'],
        "nombre" => $premio['nombre'],
        "descripcion" => $premio['descripcion'],
        "cumple_condicion" => $cumple,
        "ya_canjeado" => in_array($premio['id'], $ya_canjeados),
    ];
}

echo json_encode([
    "cantidad_visitas" => $cantidad_visitas,
    "monto_acumulado" => $monto_acumulado,
    "premios" => $resultado
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);