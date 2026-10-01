DROP DATABASE IF EXISTS inventariopro;
CREATE DATABASE inventariopro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventariopro;

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL UNIQUE
);
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombres VARCHAR(60) NOT NULL,
  apellidos VARCHAR(60) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  clave VARCHAR(255) NOT NULL,
  rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,            -- SKU
  nombre VARCHAR(120) NOT NULL,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  estado ENUM('en_stock','agotado','descontinuado') NOT NULL DEFAULT 'en_stock',
  publicado TINYINT(1) NOT NULL DEFAULT 1,
  etiqueta VARCHAR(7) NOT NULL DEFAULT '#0d6efd', -- Etiqueta visual (color)
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE producto_categorias (
  producto_id INT NOT NULL,
  categoria_id INT NOT NULL,
  PRIMARY KEY (producto_id, categoria_id),
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

INSERT INTO categorias (nombre) VALUES
 ('Periféricos'),('Electrónica'),('Papelería'),('Hogar'),
 ('Soporte Técnico'),('Facturación'),('Ventas'),('Reclamos');

INSERT INTO productos (codigo,nombre,precio,stock,estado,publicado,etiqueta) VALUES
 ('PROD-001','Teclado Mecánico RGB',189.90,72,'en_stock',1,'#0d6efd'),
 ('PROD-002','Mouse inalámbrico',45.90,40,'en_stock',1,'#198754'),
 ('PROD-003','Cuaderno A4 100 hojas',8.50,8,'en_stock',1,'#ffc107'),
 ('PROD-004','Lámpara de escritorio',60.00,0,'agotado',1,'#dc3545');
INSERT INTO producto_categorias VALUES (1,1),(2,1),(2,2),(3,3),(4,4);
-- El primer usuario que se registre en la web queda como administrador.
