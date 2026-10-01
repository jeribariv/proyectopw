<?php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder($datos, $codigo = 200) { http_response_code($codigo); echo json_encode($datos); exit; }
function entrada() { return json_decode(file_get_contents('php://input'), true) ?: []; }
function requiere($admin = false) {
    if (empty($_SESSION['u'])) responder(['error' => 'Sesión no iniciada'], 401);
    if ($admin && $_SESSION['u']['rol'] !== 'admin') responder(['error' => 'Solo para administradores'], 403);
}

try {
    // Ajusta usuario/clave si tu WampServer no usa root sin contraseña
    $db = new PDO('mysql:host=localhost;dbname=inventariopro;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    responder(['error' => 'No se pudo conectar a la base de datos'], 500);
}
