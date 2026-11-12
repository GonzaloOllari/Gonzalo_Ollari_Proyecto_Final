<?php
// ===== Configuración del taller =====
const TALLER_NOMBRE    = 'Taller Ollari';
const TALLER_DIRECCION = 'Santo Tomé 5147, esquina Las Colonias';
const TALLER_MAPA      = 'Santo Tomé 5147 y Las Colonias, Ciudad Autónoma de Buenos Aires, Argentina';
const TALLER_TEL       = '11 5143-1316';
const TALLER_HORARIO   = 'Lunes a viernes de 9 a 12 hs y de 15 a 18 hs. Sábados y domingos cerrado';
const TALLER_PAGO      = 'El servicio se paga en el taller. Aceptamos transferencia bancaria o efectivo.';
const TALLER_CAPACIDAD_DIA   = 2;  // turnos máximos por día

session_start();
try {
    $conexion = new PDO('mysql:host=localhost;dbname=taller_mecanico;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_campo() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok() { return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }
function es_admin() { return ($_SESSION['rol'] ?? '') === 'admin'; }
function requiere_login() { if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; } }
function requiere_admin() { requiere_login(); if (!es_admin()) { http_response_code(403); exit('Acceso denegado.'); } }

function cabecera($titulo) { ?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> | <?= e(TALLER_NOMBRE) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="estilos.css?v=<?= filemtime(__DIR__ . '/estilos.css') ?>">
</head><body>
<header class="top"><div class="wrap bar">
  <a class="marca" href="index.php"><?= e(TALLER_NOMBRE) ?></a>
  <nav>
    <a href="index.php">Inicio</a>
    <a href="turnos.php">Turnos</a>
    <a href="contacto.php">Contacto</a>
    <?php if (es_admin()): ?><a href="admin.php">Administración</a><?php endif; ?>
    <?php if (!empty($_SESSION['uid'])): ?>
      <a href="cerrar_sesion.php">Salir (<?= e($_SESSION['nombre']) ?>)</a>
    <?php else: ?>
      <a href="login.php">Ingresar</a><a class="btn chico" href="registro.php">Crear cuenta</a>
    <?php endif; ?>
  </nav>
</div></header>
<main class="wrap">
<?php }

function pie() { ?>
</main>
<footer class="pie">
  <div class="wrap">Página realizada por Gonzalo Ollari | 4A | Trabajo Práctico Final | 2026</div>
</footer>
</body></html>
<?php }
