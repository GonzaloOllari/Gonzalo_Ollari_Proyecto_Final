<?php
require_once __DIR__ . '/conexion.php';
requiere_login();
$uid = $_SESSION['uid'];
$msg = ''; $error = '';

$servicios = $conexion->query('SELECT id, nombre FROM servicios ORDER BY nombre')->fetchAll();
$horas = [];
foreach ([[9, 11], [15, 17]] as [$ini, $fin]) {
    for ($h = $ini; $h <= $fin; $h++) { $horas[] = sprintf('%02d:00', $h); $horas[] = sprintf('%02d:30', $h); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $error = 'La sesión expiró. Recargá la página.';
    elseif (($_POST['accion'] ?? '') === 'cancelar') {
        $st = $conexion->prepare("UPDATE turnos SET estado='cancelado' WHERE id=:id AND usuario_id=:u AND estado IN ('pendiente','confirmado')");
        $st->execute(['id' => (int)($_POST['id'] ?? 0), 'u' => $uid]);
        $msg = 'Turno cancelado.';
    } else {
        $vehiculo = trim($_POST['vehiculo'] ?? '');
        $patente  = strtoupper(preg_replace('/\s+/', '', $_POST['patente'] ?? ''));
        $serv     = (int)($_POST['servicio'] ?? 0);
        $fecha    = $_POST['fecha'] ?? '';
        $hora     = $_POST['hora'] ?? '';
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($vehiculo === '' || !preg_match('/^[A-Z0-9]{6,8}$/', $patente)) $error = 'Completá el vehículo y una patente válida (ej: AB123CD).';
        elseif (!$d || $d->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) $error = 'Elegí una fecha de hoy en adelante.';
        elseif (in_array($d->format('w'), ['0', '6'], true)) $error = 'Los sábados y domingos el taller está cerrado. Elegí un día de lunes a viernes.';
        elseif (!in_array($hora, $horas, true)) $error = 'Elegí un horario de 9 a 12 o de 15 a 18 hs.';
        else {
            $st = $conexion->prepare("SELECT COUNT(*) FROM turnos WHERE fecha=:f AND hora=:h AND estado IN ('pendiente','confirmado')");
            $st->execute(['f' => $fecha, 'h' => $hora . ':00']);
            $ocupado = $st->fetchColumn() > 0;
            $dia = $conexion->prepare("SELECT COUNT(*) FROM turnos WHERE fecha=:f AND estado <> 'cancelado'");
            $dia->execute(['f' => $fecha]);
            if ($dia->fetchColumn() >= TALLER_CAPACIDAD_DIA) $error = 'Ese día el taller está completo. Elegí otra fecha.';
            elseif ($ocupado) $error = 'Ese horario ya está ocupado. Probá con otro.';
            else {
                $st = $conexion->prepare('INSERT INTO turnos (usuario_id, vehiculo, patente, servicio_id, fecha, hora) VALUES (:u,:v,:p,:s,:f,:h)');
                $st->execute(['u' => $uid, 'v' => $vehiculo, 'p' => $patente, 's' => $serv, 'f' => $fecha, 'h' => $hora . ':00']);
                $msg = 'Turno solicitado. Te lo confirmamos a la brevedad.';
            }
        }
    }
}

$mios = $conexion->prepare('SELECT t.*, s.nombre AS servicio FROM turnos t JOIN servicios s ON s.id=t.servicio_id
                            WHERE t.usuario_id=:u ORDER BY t.fecha DESC, t.hora DESC');
$mios->execute(['u' => $uid]);
$mios = $mios->fetchAll();

cabecera('Turnos'); ?>
<div class="grid2">
<section class="panel">
  <h2>Pedir turno</h2>
  <?php if ($msg): ?><div class="msg ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
  <form class="f" method="post">
    <?= csrf_campo() ?>
    <div><label for="vehiculo">Marca, modelo y año</label><input id="vehiculo" name="vehiculo" required maxlength="120" placeholder="Ford Focus 2018"></div>
    <div><label for="patente">Patente</label><input id="patente" name="patente" required maxlength="8" placeholder="AB123CD"></div>
    <div><label for="servicio">Servicio</label>
      <select id="servicio" name="servicio"><?php foreach ($servicios as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="dos">
      <div><label for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" required min="<?= date('Y-m-d') ?>"></div>
      <div><label for="hora">Hora</label><select id="hora" name="hora"><?php foreach ($horas as $h): ?><option><?= $h ?></option><?php endforeach; ?></select></div>
    </div>
    <p class="ayuda">Atendemos <?= e(TALLER_HORARIO) ?>.</p>
    <p class="aviso"><strong>Forma de pago:</strong> <?= e(TALLER_PAGO) ?></p>
    <button type="submit">Pedir turno</button>
  </form>
</section>

<section class="panel">
  <h2>Mis turnos</h2>
  <?php if (!$mios): ?><p>Todavía no pediste ningún turno.</p><?php else: ?>
  <div class="tabla"><table>
    <tr><th>Fecha</th><th>Servicio</th><th>Estado</th><th></th></tr>
    <?php foreach ($mios as $t): ?>
    <tr>
      <td><?= e(date('d/m/Y', strtotime($t['fecha']))) ?><br><?= e(substr($t['hora'], 0, 5)) ?> hs</td>
      <td><?= e($t['servicio']) ?><br><small><?= e($t['patente']) ?></small></td>
      <td><span class="etq <?= e($t['estado']) ?>"><?= e(ucfirst($t['estado'])) ?></span></td>
      <td>
        <a href="comprobante.php?id=<?= (int)$t['id'] ?>">Comprobante</a>
        <?php if (in_array($t['estado'], ['pendiente', 'confirmado'], true)): ?>
        <form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="cancelar"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button type="submit">Cancelar</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</section>
</div>
<script>
document.getElementById('fecha').addEventListener('input', function () {
  const d = this.value ? new Date(this.value + 'T12:00:00').getDay() : 1;
  this.setCustomValidity(d === 0 || d === 6 ? 'El taller está cerrado los fines de semana. Elegí de lunes a viernes.' : '');
  this.reportValidity();
});
</script>
<?php pie();
