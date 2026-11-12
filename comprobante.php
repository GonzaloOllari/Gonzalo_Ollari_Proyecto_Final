<?php
require_once __DIR__ . '/conexion.php';
requiere_login();
$st = $conexion->prepare('SELECT t.*, s.nombre AS servicio, u.nombre, u.apellido, u.dni FROM turnos t
                          JOIN servicios s ON s.id=t.servicio_id JOIN usuarios u ON u.id=t.usuario_id WHERE t.id=:id');
$st->execute(['id' => (int)($_GET['id'] ?? 0)]);
$t = $st->fetch();
if (!$t || ($t['usuario_id'] != $_SESSION['uid'] && !es_admin())) { http_response_code(404); exit('Comprobante no encontrado.'); }
cabecera('Comprobante de turno'); ?>
<div class="comprobante">
  <h2><?= e(TALLER_NOMBRE) ?></h2>
  <p>Comprobante de turno N° <?= str_pad($t['id'], 6, '0', STR_PAD_LEFT) ?></p>
  <dl>
    <dt>Cliente</dt><dd><?= e($t['apellido'] . ', ' . $t['nombre']) ?> (DNI <?= e($t['dni']) ?>)</dd>
    <dt>Vehículo</dt><dd><?= e($t['vehiculo']) ?> · <?= e($t['patente']) ?></dd>
    <dt>Servicio</dt><dd><?= e($t['servicio']) ?></dd>
    <dt>Fecha y hora</dt><dd><?= e(date('d/m/Y', strtotime($t['fecha']))) ?> a las <?= e(substr($t['hora'], 0, 5)) ?> hs</dd>
    <dt>Estado</dt><dd><?= e(ucfirst($t['estado'])) ?></dd>
    <dt>Dirección</dt><dd><?= e(TALLER_DIRECCION) ?></dd>
    <dt>Forma de pago</dt><dd>En el taller. Transferencia bancaria o efectivo.</dd>
  </dl>
  <p class="ayuda">Presentá este comprobante al dejar el vehículo.</p>
  <p class="no-print"><button onclick="window.print()">Imprimir o guardar en PDF</button></p>
</div>
<?php pie();
