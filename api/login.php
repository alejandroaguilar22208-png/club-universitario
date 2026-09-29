<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido, usá POST"]);
    exit();
}

$datos = json_decode(file_get_contents('php://input'), true);

if (empty($datos['email']) || empty($datos['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos: email y password son obligatorios"]);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
$stmt->execute([':email' => $datos['email']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($datos['password'], $usuario['password_hash'])) {
    http_response_code(401);
    echo json_encode(["error" => "Email o contraseña incorrectos"]);
    exit();
}

unset($usuario['password_hash']);

$stmtSocio = $pdo->prepare("SELECT * FROM socios WHERE usuario_id = :id");
$stmtSocio->execute([':id' => $usuario['id']]);
$socio = $stmtSocio->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "exito" => true,
    "usuario" => $usuario,
    "socio" => $socio ?: null
]);