<?php
require "config.php";

$sql = "
    SELECT e.id, e.nombre, e.descripcion, e.fecha, e.hora_apertura,
           t.id AS tipo_entrada_id, t.nombre AS tipo_nombre,
           t.precio, t.precio_socio
    FROM eventos e
    LEFT JOIN tipos_entrada t ON t.evento_id = e.id
    WHERE e.activo = 1 AND e.fecha >= CURDATE()
    ORDER BY e.fecha ASC
";

$stmt = $pdo->query($sql);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventos = [];
foreach ($filas as $fila) {
    $id = $fila['id'];
    if (!isset($eventos[$id])) {
        $eventos[$id] = [
            "id" => $fila['id'],
            "nombre" => $fila['nombre'],
            "descripcion" => $fila['descripcion'],
            "fecha" => $fila['fecha'],
            "hora_apertura" => $fila['hora_apertura'],
            "tipos_entrada" => []
        ];
    }
    if ($fila['tipo_entrada_id']) {
        $eventos[$id]["tipos_entrada"][] = [
            "id" => $fila['tipo_entrada_id'],
            "nombre" => $fila['tipo_nombre'],
            "precio" => (float) $fila['precio'],
            "precio_socio" => (float) $fila['precio_socio']
        ];
    }
}

echo json_encode(array_values($eventos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);