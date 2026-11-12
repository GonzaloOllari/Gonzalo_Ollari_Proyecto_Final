<?php
require_once __DIR__ . '/conexion.php';
requiere_admin();
$msg = ''; $error = '';
$estTurno = ['pendiente', 'confirmado', 'cancelado', 'realizado'];
$estOrden = ['ingresado' => 'Ingresado', 'en_diagnostico' => 'En diagnóstico', 'esperando_repuesto' => 'Esperando repuesto',
             'en_reparacion' => 'En reparación', 'listo' => 'Listo para retirar'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $error = 'La sesión expiró. Recargá la página.';
    else switch ($_POST['accion'] ?? '') {
        case 'turno':
            if (in_array($_POST['estado'] ?? '', $estTurno, true)) {
                $conexion->prepare('UPDATE turnos SET estado=:e WHERE id=:i')->execute(['e' => $_POST['estado'], 'i' => (int)$_POST['id']]);
                $msg = 'Turno actualizado.';
            } break;
        case 'turno_fecha':
            $f = $_POST['fecha'] ?? ''; $h = $_POST['hora'] ?? '';
            if (DateTime::createFromFormat('Y-m-d', $f) && preg_match('/^\d{2}:\d{2}$/', $h)) {
                $conexion->prepare("UPDATE turnos SET fecha=:f, hora=:h, estado='pendiente' WHERE id=:i")->execute(['f' => $f, 'h' => $h . ':00', 'i' => (int)$_POST['id']]);
                $msg = 'Turno reprogramado. Quedó pendiente de confirmación.';
            } else $error = 'Fecha u hora inválida.';
            break;
        case 'orden_crear':
            $u = $conexion->prepare('SELECT id FROM usuarios WHERE dni=:d AND archivado_en IS NULL');
            $u->execute(['d' => trim($_POST['dni'] ?? '')]);
            $uid = $u->fetchColumn();
            $pat = strtoupper(preg_replace('/\s+/', '', $_POST['patente'] ?? ''));
            $veh = trim($_POST['vehiculo'] ?? '');
            if (!$uid) $error = 'No hay ningún cliente registrado con ese DNI.';
            elseif ($pat === '' || $veh === '') $error = 'Completá patente y vehículo.';
            else {
                $conexion->prepare('INSERT INTO ordenes (usuario_id, patente, vehiculo) VALUES (:u,:p,:v)')->execute(['u' => $uid, 'p' => $pat, 'v' => $veh]);
                $msg = 'Vehículo ingresado.';
            } break;
        case 'orden':
            if (isset($estOrden[$_POST['estado'] ?? ''])) {
                $conexion->prepare('UPDATE ordenes SET estado=:e WHERE id=:i')->execute(['e' => $_POST['estado'], 'i' => (int)$_POST['id']]);
                $msg = 'Estado del vehículo actualizado.';
            } break;
        case 'orden_borrar':
            $conexion->prepare('DELETE FROM ordenes WHERE id=:i')->execute(['i' => (int)$_POST['id']]);
            $msg = 'Vehículo eliminado de la lista.'; break;
        case 'contacto_leido':
            $conexion->prepare('UPDATE contactos SET leido = 1 - leido WHERE id=:i')->execute(['i' => (int)$_POST['id']]);
            break;
        case 'contacto_borrar':
            $conexion->prepare('DELETE FROM contactos WHERE id=:i')->execute(['i' => (int)$_POST['id']]);
            $msg = 'Mensaje eliminado.'; break;
        case 'usuario':
            $id = (int)$_POST['id'];
            if ($id === (int)$_SESSION['uid']) { $error = 'No podés desactivar tu propia cuenta.'; break; }
            $conexion->prepare('UPDATE usuarios SET archivado_en = IF(archivado_en IS NULL, NOW(), NULL) WHERE id=:i')->execute(['i' => $id]);
            $msg = 'Cuenta actualizada.'; break;
    }
}

// Búsqueda parametrizada de turnos
$q = trim($_GET['q'] ?? ''); $fd = trim($_GET['fecha'] ?? '');
$sql = 'SELECT t.*, s.nombre servicio, u.nombre, u.apellido, u.dni, u.email FROM turnos t
        JOIN servicios s ON s.id=t.servicio_id JOIN usuarios u ON u.id=t.usuario_id WHERE 1=1';
$par = [];
$todos = isset($_GET['todos']);
if (!$todos && $q === '' && $fd === '') $sql .= ' AND t.fecha >= CURDATE()';
if ($q !== '') { $sql .= ' AND (t.patente LIKE :q OR u.nombre LIKE :q OR u.apellido LIKE :q OR u.dni LIKE :q OR u.email LIKE :q)'; $par['q'] = "%$q%"; }
if ($fd !== '') { $sql .= ' AND t.fecha = :f'; $par['f'] = $fd; }
$st = $conexion->prepare($sql . ' ORDER BY t.fecha ASC, t.hora ASC LIMIT 200');
$st->execute($par);
$turnos = $st->fetchAll();
$diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

$ordenes  = $conexion->query('SELECT o.*, u.nombre, u.apellido FROM ordenes o JOIN usuarios u ON u.id=o.usuario_id ORDER BY o.actualizado_en DESC LIMIT 50')->fetchAll();
$contactos = $conexion->query('SELECT * FROM contactos ORDER BY leido ASC, id DESC LIMIT 100')->fetchAll();
$sinLeer = count(array_filter($contactos, fn($c) => !$c['leido']));
$usuarios = $conexion->query('SELECT id, nombre, apellido, dni, email, rol, archivado_en FROM usuarios ORDER BY apellido')->fetchAll();

cabecera('Administración'); ?>
<?php if ($msg): ?><div class="msg ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>

<section class="panel">
  <h2>Turnos pedidos (<?= count($turnos) ?>)</h2>
  <form class="f" method="get" style="grid-template-columns:2fr 1fr auto;align-items:end">
    <div><label for="q">Buscar por patente, cliente, DNI o email</label><input id="q" name="q" value="<?= e($q) ?>"></div>
    <div><label for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" value="<?= e($fd) ?>"></div>
    <button type="submit">Buscar</button>
  </form>
  <p class="ayuda">
    <?= $todos ? 'Mostrando todos los turnos, también los pasados.' : 'Mostrando los turnos de hoy en adelante.' ?>
    <a href="admin.php<?= $todos ? '' : '?todos=1' ?>"><?= $todos ? 'Ver solo los próximos' : 'Ver todos, incluidos los pasados' ?></a>
  </p>
  <?php if (!$turnos): ?><p class="ayuda">No hay turnos para mostrar.</p><?php endif; ?>
  <?php foreach ($turnos as $t): ?>
  <article class="turno">
    <div class="turno-info">
      <div>
        <span class="rotulo">Fecha y hora</span>
        <strong><?= e(ucfirst($diasSemana[(int)date('w', strtotime($t['fecha']))]) . ' ' . date('d/m/Y', strtotime($t['fecha']))) ?></strong><br>
        <?= e(substr($t['hora'], 0, 5)) ?> hs
      </div>
      <div>
        <span class="rotulo">Cliente</span>
        <strong><?= e($t['apellido'] . ', ' . $t['nombre']) ?></strong><br>
        DNI <?= e($t['dni']) ?><br>
        <a href="mailto:<?= e($t['email']) ?>"><?= e($t['email']) ?></a>
      </div>
      <div>
        <span class="rotulo">Vehículo y servicio</span>
        <strong><?= e($t['vehiculo']) ?></strong><br>
        Patente: <?= e($t['patente']) ?><br>
        Servicio: <?= e($t['servicio']) ?>
      </div>
      <div>
        <span class="rotulo">Estado</span>
        <span class="etq <?= e($t['estado']) ?>"><?= e(ucfirst($t['estado'])) ?></span><br>
        <small>Pedido el <?= e(date('d/m/Y H:i', strtotime($t['creado_en']))) ?></small>
      </div>
    </div>
    <div class="turno-acciones">
      <form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="turno"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <select name="estado" aria-label="Estado"><?php foreach ($estTurno as $est): ?><option value="<?= $est ?>" <?= $est === $t['estado'] ? 'selected' : '' ?>><?= ucfirst($est) ?></option><?php endforeach; ?></select>
        <button type="submit">Guardar estado</button></form>
      <form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="turno_fecha"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <input type="date" name="fecha" required aria-label="Nueva fecha" value="<?= e($t['fecha']) ?>"><input type="time" name="hora" required aria-label="Nueva hora" value="<?= e(substr($t['hora'], 0, 5)) ?>">
        <button type="submit">Reprogramar</button></form>
      <a href="comprobante.php?id=<?= (int)$t['id'] ?>">Ver ficha del turno</a>
    </div>
  </article>
  <?php endforeach; ?>
</section>

<section class="panel">
  <h2>Vehículos en el taller</h2>
  <form class="f" method="post" style="grid-template-columns:1fr 1fr 2fr auto;align-items:end">
    <?= csrf_campo() ?><input type="hidden" name="accion" value="orden_crear">
    <div><label for="o-dni">DNI del cliente</label><input id="o-dni" name="dni" required></div>
    <div><label for="o-pat">Patente</label><input id="o-pat" name="patente" required maxlength="10"></div>
    <div><label for="o-veh">Vehículo</label><input id="o-veh" name="vehiculo" required maxlength="120"></div>
    <button type="submit">Ingresar vehículo</button>
  </form>
  <div class="tabla" style="margin-top:1rem"><table>
    <tr><th>Patente</th><th>Vehículo</th><th>Cliente</th><th>Estado</th><th>Ya se lo llevó</th></tr>
    <?php foreach ($ordenes as $o): ?>
    <tr><td><?= e($o['patente']) ?></td><td><?= e($o['vehiculo']) ?></td><td><?= e($o['apellido'] . ', ' . $o['nombre']) ?></td>
      <td><form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="orden"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
        <select name="estado" aria-label="Estado"><?php foreach ($estOrden as $k => $txt): ?><option value="<?= $k ?>" <?= $k === $o['estado'] ? 'selected' : '' ?>><?= e($txt) ?></option><?php endforeach; ?></select>
        <button type="submit">Guardar</button></form></td>
      <td><form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="orden_borrar"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
        <button type="submit" onclick="return confirm('¿Eliminar este vehículo de la lista? Usalo cuando el cliente ya retiró el auto.')">Eliminar</button></form></td></tr>
    <?php endforeach; if (!$ordenes): ?><tr><td colspan="5">No hay vehículos ingresados.</td></tr><?php endif; ?>
  </table></div>
</section>

<section class="panel">
  <h2>Mensajes de contacto<?= $sinLeer ? ' (' . $sinLeer . ' sin leer)' : '' ?></h2>
  <?php if (!$contactos): ?><p class="ayuda">Todavía no llegaron mensajes.</p><?php else: ?>
  <div class="tabla"><table>
    <tr><th>Fecha</th><th>Contacto</th><th>Mensaje</th><th></th></tr>
    <?php foreach ($contactos as $c): ?>
    <tr<?= $c['leido'] ? '' : ' style="background:var(--panel2)"' ?>>
      <td><?= e(date('d/m H:i', strtotime($c['creado_en']))) ?></td>
      <td><strong><?= e($c['nombre']) ?></strong><br>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $c['telefono'])) ?>"><?= e($c['telefono']) ?></a><br>
        <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></td>
      <td><?= nl2br(e($c['mensaje'])) ?></td>
      <td><form method="post"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button type="submit" name="accion" value="contacto_leido"><?= $c['leido'] ? 'Marcar sin leer' : 'Marcar leído' ?></button>
        <button type="submit" name="accion" value="contacto_borrar" onclick="return confirm('¿Eliminar este mensaje?')">Eliminar</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table></div><?php endif; ?>
</section>

<section class="panel">
  <h2>Clientes</h2>
  <div class="tabla"><table>
    <tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Rol</th><th>Cuenta</th></tr>
    <?php foreach ($usuarios as $u): ?>
    <tr><td><?= e($u['apellido'] . ', ' . $u['nombre']) ?></td><td><?= e($u['dni']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['rol']) ?></td>
      <td><form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="usuario"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <span class="etq <?= $u['archivado_en'] ? 'cancelado' : 'confirmado' ?>"><?= $u['archivado_en'] ? 'Desactivada' : 'Activa' ?></span>
        <button type="submit"><?= $u['archivado_en'] ? 'Reactivar' : 'Desactivar' ?></button></form></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
<?php pie();
