<?php
require_once __DIR__ . '/conexion.php';
$msg = ''; $error = '';
$v = ['nombre' => '', 'telefono' => '', 'email' => '', 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    if (!csrf_ok()) $error = 'La sesión expiró. Recargá la página.';
    elseif (!empty($_POST['web'])) { $msg = 'Gracias, recibimos tu mensaje.'; $v = array_map(fn() => '', $v); } // campo trampa para bots
    elseif ($v['nombre'] === '' || mb_strlen($v['nombre']) > 80) $error = 'Ingresá tu nombre (hasta 80 caracteres).';
    elseif (!preg_match('/^[0-9 +()\-]{8,20}$/', $v['telefono'])) $error = 'Ingresá un teléfono válido, por ejemplo 11 5143-1316.';
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($v['email']) > 254) $error = 'Ingresá un email válido, por ejemplo nombre@gmail.com.';
    elseif ($v['mensaje'] === '' || mb_strlen($v['mensaje']) > 1000) $error = 'Escribí tu consulta (hasta 1000 caracteres).';
    else {
        $st = $conexion->prepare('INSERT INTO contactos (nombre, telefono, email, mensaje) VALUES (:n,:t,:e,:m)');
        $st->execute(['n' => $v['nombre'], 't' => $v['telefono'], 'e' => $v['email'], 'm' => $v['mensaje']]);
        $msg = 'Gracias, recibimos tu mensaje. Te vamos a contactar a la brevedad.';
        $v = array_map(fn() => '', $v);
    }
}
$telLink = 'tel:' . preg_replace('/[^0-9+]/', '', TALLER_TEL);
cabecera('Contacto'); ?>
<div class="grid2">
<section class="panel">
  <h2>Contactanos</h2>
  <p class="ayuda" style="margin-bottom:1rem">Dejanos tu consulta, tu teléfono y tu email, y te respondemos.</p>
  <?php if ($msg): ?><div class="msg ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
  <form class="f" method="post">
    <?= csrf_campo() ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>No completar<input name="web" tabindex="-1" autocomplete="off"></label></div>
    <div><label for="nombre">Nombre</label><input id="nombre" name="nombre" required maxlength="80" value="<?= e($v['nombre']) ?>"></div>
    <div class="dos">
      <div><label for="telefono">Teléfono</label><input id="telefono" type="tel" name="telefono" required maxlength="20" placeholder="11 2345-6789" value="<?= e($v['telefono']) ?>"></div>
      <div><label for="email">Email</label><input id="email" type="email" name="email" required maxlength="254" placeholder="nombre@gmail.com" value="<?= e($v['email']) ?>"></div>
    </div>
    <div><label for="mensaje">¿Qué le pasa al auto o qué necesitás?</label>
      <textarea id="mensaje" name="mensaje" rows="5" required maxlength="1000" placeholder="Contanos marca, modelo y qué problema notás."><?= e($v['mensaje']) ?></textarea></div>
    <button type="submit">Enviar mensaje</button>
  </form>
</section>

<section class="panel">
  <h2>Nuestros datos</h2>
  <p><strong>Teléfono</strong><br><a href="<?= e($telLink) ?>"><?= e(TALLER_TEL) ?></a></p>
  <p><strong>Dirección</strong><br><?= e(TALLER_DIRECCION) ?></p>
  <p><strong>Horarios</strong><br><?= e(TALLER_HORARIO) ?></p>
  <p><a class="btn" href="turnos.php">Pedir turno</a></p>
</section>
</div>
<?php pie();
