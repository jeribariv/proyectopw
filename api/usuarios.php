<?php
require 'conexion.php';
requiere(true);
$m = $_SERVER['REQUEST_METHOD'];

if ($m === 'GET') responder($db->query('SELECT id, CONCAT(nombres, " ", apellidos) AS nombre, email, rol, creado FROM usuarios ORDER BY id')->fetchAll());

if ($m === 'PUT') {
    $d = entrada(); $id = (int)($d['id'] ?? 0);
    if ($id === $_SESSION['u']['id'] || !in_array($d['rol'] ?? '', ['admin', 'usuario']))
        responder(['error' => 'No puedes cambiar tu propio rol'], 422);
    $db->prepare('UPDATE usuarios SET rol = ? WHERE id = ?')->execute([$d['rol'], $id]);
    responder(['ok' => true]);
}
if ($m === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id === $_SESSION['u']['id']) responder(['error' => 'No puedes eliminar tu propia cuenta'], 422);
    $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
    responder(['ok' => true]);
}
responder(['error' => 'Método no permitido'], 405);
