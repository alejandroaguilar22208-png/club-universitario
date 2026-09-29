<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);

if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos: nombre, email y password son obligatorios"]);
    exit();
}

$password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (nombre, email, password_hash)
         VALUES (:nombre, :email, :password_hash)"
    );
    $stmt->execute([
        ':nombre' => $datos['nombre'],
        ':email' => $datos['email'],
        ':password_hash' => $password_hash,
    ]);

    echo json_encode([
        "exito" => true,
        "mensaje" => "Usuario registrado correctamente",
        "usuario_id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(["error" => "Ese email ya está registrado"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar: " . $e->getMessage()]);
    }
}