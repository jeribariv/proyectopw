<?php
require 'conexion.php';
$a = $_GET['accion'] ?? '';

if ($a === 'sesion') { requiere(); responder($_SESSION['u']); }
if ($a === 'logout') { $_SESSION = []; session_destroy(); responder(['ok' => true]); }

$d = entrada();
if ($a === 'login') {
    $s = $db->prepare('SELECT * FROM usuarios WHERE email = ?');
    $s->execute([trim($d['email'] ?? '')]);
    $u = $s->fetch();
    if (!$u || !password_verify($d['clave'] ?? '', $u['clave'])) responder(['error' => 'Correo o contraseña incorrectos'], 401);
    session_regenerate_id(true);
    $_SESSION['u'] = ['id' => (int)$u['id'], 'nombre' => $u['nombres'] . ' ' . $u['apellidos'], 'email' => $u['email'], 'rol' => $u['rol']];
    responder($_SESSION['u']);
}
if ($a === 'registro') {
    $n = trim($d['nombres'] ?? ''); $ap = trim($d['apellidos'] ?? '');
    $e = trim($d['email'] ?? ''); $c = $d['clave'] ?? '';
    if (strlen($n) < 2 || strlen($ap) < 2 || !filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($c) < 6)
        responder(['error' => 'Datos inválidos'], 422);
    if (empty($d['terminos'])) responder(['error' => 'Debe aceptar los términos y condiciones'], 422);
    $s = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
    $s->execute([$e]);
    if ($s->fetch()) responder(['error' => 'Ese correo ya está registrado'], 409);
    $rol = $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() == 0 ? 'admin' : 'usuario';
    $db->prepare('INSERT INTO usuarios (nombres,apellidos,email,clave,rol) VALUES (?,?,?,?,?)')
       ->execute([$n, $ap, $e, password_hash($c, PASSWORD_DEFAULT), $rol]);
    responder(['ok' => true, 'rol' => $rol], 201);
}
responder(['error' => 'Acción no válida'], 400);
