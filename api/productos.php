<?php
require 'conexion.php';
requiere();
$m = $_SERVER['REQUEST_METHOD'];

function listar($db, $donde = '', $par = []) {
    $s = $db->prepare("SELECT p.*, GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') AS categoria,
                              GROUP_CONCAT(c.id) AS ids
                       FROM productos p
                       LEFT JOIN producto_categorias pc ON pc.producto_id = p.id
                       LEFT JOIN categorias c ON c.id = pc.categoria_id
                       $donde GROUP BY p.id ORDER BY p.id DESC");
    $s->execute($par);
    return array_map(function ($r) {
        $r['categoria_ids'] = $r['ids'] !== null ? array_map('intval', explode(',', $r['ids'])) : [];
        unset($r['ids']);
        return $r;
    }, $s->fetchAll());
}

if ($m === 'GET') {
    if (isset($_GET['categorias'])) responder($db->query('SELECT * FROM categorias ORDER BY id')->fetchAll());
    if (isset($_GET['id'])) {
        $r = listar($db, 'WHERE p.id = ?', [(int)$_GET['id']]);
        if (!$r) responder(['error' => 'Producto no encontrado'], 404);
        responder($r[0]);
    }
    $q = '%' . ($_GET['q'] ?? '') . '%';
    responder(listar($db, 'WHERE p.nombre LIKE ? OR p.codigo LIKE ?', [$q, $q]));
}
if ($m === 'DELETE') {
    requiere(true);
    $s = $db->prepare('DELETE FROM productos WHERE id = ?');
    $s->execute([(int)($_GET['id'] ?? 0)]);
    if (!$s->rowCount()) responder(['error' => 'Producto no encontrado'], 404);
    responder(['ok' => true]);
}
if ($m !== 'POST' && $m !== 'PUT') responder(['error' => 'Método no permitido'], 405);

// ---- Validaciones en el servidor ----
$d = entrada();
$nombre = trim($d['nombre'] ?? ''); $codigo = trim($d['codigo'] ?? '');
$precio = $d['precio'] ?? ''; $stock = $d['stock'] ?? '';
$estado = $d['estado'] ?? ''; $etiqueta = $d['etiqueta'] ?? '#0d6efd';
$publicado = !empty($d['publicado']) ? 1 : 0;
$cats = array_values(array_filter(array_unique(array_map('intval', (array)($d['categorias'] ?? [$d['categoria_id'] ?? 0]))), fn($c) => $c > 0));

if ($nombre === '' || !is_numeric($precio) || $precio < 0 || !ctype_digit((string)$stock) || !$cats
    || !in_array($estado, ['en_stock', 'agotado', 'descontinuado']) || !preg_match('/^#[0-9a-fA-F]{6}$/', $etiqueta)
    || ($m === 'POST' && !preg_match('/^[A-Za-z0-9_-]{3,30}$/', $codigo)))
    responder(['error' => 'Revisa los datos del producto'], 422);

try {
    $db->beginTransaction();
    if ($m === 'POST') {
        $db->prepare('INSERT INTO productos (codigo,nombre,precio,stock,estado,publicado,etiqueta) VALUES (?,?,?,?,?,?,?)')
           ->execute([$codigo, $nombre, $precio, $stock, $estado, $publicado, $etiqueta]);
        $id = (int)$db->lastInsertId();
    } else {
        $id = (int)($d['id'] ?? 0);
        $a = $db->prepare('SELECT categoria_id FROM producto_categorias WHERE producto_id = ?');
        $a->execute([$id]);
        $actuales = array_map('intval', $a->fetchAll(PDO::FETCH_COLUMN));
        // Si elige una categoría que ya tenía, se conservan todas las asignadas
        if (count($cats) === 1 && in_array($cats[0], $actuales)) $cats = $actuales;
        // El SKU (codigo) no se modifica: está bloqueado para preservar la integridad
        $u = $db->prepare('UPDATE productos SET nombre=?, precio=?, stock=?, estado=?, publicado=?, etiqueta=? WHERE id=?');
        $u->execute([$nombre, $precio, $stock, $estado, $publicado, $etiqueta, $id]);
        $ex = $db->prepare('SELECT 1 FROM productos WHERE id = ?'); $ex->execute([$id]);
        if (!$ex->fetch()) { $db->rollBack(); responder(['error' => 'Producto no encontrado'], 404); }
        $db->prepare('DELETE FROM producto_categorias WHERE producto_id = ?')->execute([$id]);
    }
    $ins = $db->prepare('INSERT INTO producto_categorias (producto_id,categoria_id) VALUES (?,?)');
    foreach ($cats as $c) $ins->execute([$id, $c]);
    $db->commit();
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    responder(['error' => 'El código SKU ya existe o la categoría no es válida'], 409);
}
responder(['ok' => true, 'id' => $id]);
