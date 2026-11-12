<?php
require_once __DIR__ . '/conexion.php';

$servicios = $conexion->query('SELECT id, nombre, precio_base FROM servicios ORDER BY nombre')->fetchAll();

// Estado del vehículo: patente + DNI (así nadie ve el auto de otro)
$orden = null; $busco = false;
$pasos = ['ingresado' => 'Ingresado', 'en_diagnostico' => 'En diagnóstico', 'esperando_repuesto' => 'Esperando repuesto',
          'en_reparacion' => 'En reparación', 'listo' => 'Listo para retirar'];
if (isset($_GET['patente'], $_GET['dni'])) {
    $busco = true;
    $st = $conexion->prepare('SELECT o.vehiculo, o.patente, o.estado, o.actualizado_en FROM ordenes o
                              JOIN usuarios u ON u.id = o.usuario_id
                              WHERE o.patente = :p AND u.dni = :d ORDER BY o.id DESC LIMIT 1');
    $st->execute(['p' => strtoupper(trim($_GET['patente'])), 'd' => trim($_GET['dni'])]);
    $orden = $st->fetch();
}
$mapa = 'https://www.google.com/maps?q=' . urlencode(TALLER_MAPA) . '&output=embed';
cabecera('Inicio'); ?>
<section class="hero">
  <div>
    <h1>Tu auto en manos que lo conocen.</h1>
    <p>Pedí turno, escribinos tu consulta y seguí el estado de la reparación sin llamar por teléfono.</p>
    <div class="acciones">
      <a class="btn" href="turnos.php">Pedir turno</a>
      <a class="btn sec" href="contacto.php">Contactanos</a>
    </div>
  </div>
  <div class="ficha">
    <h3>Cómo llegar</h3>
    <p><?= e(TALLER_DIRECCION) ?></p>
    <p><?= e(TALLER_HORARIO) ?></p>
    <p>Teléfono: <?= e(TALLER_TEL) ?></p>
  </div>
</section>

<div class="grid2">
  <section class="panel">
    <h2>¿Cómo va mi auto?</h2>
    <form class="f" method="get" action="index.php#estado" id="estado">
      <div class="dos">
        <div><label for="patente">Patente</label><input id="patente" name="patente" required maxlength="10" placeholder="AB123CD" value="<?= e($_GET['patente'] ?? '') ?>"></div>
        <div><label for="dni">DNI del titular</label><input id="dni" name="dni" required inputmode="numeric" value="<?= e($_GET['dni'] ?? '') ?>"></div>
      </div>
      <button type="submit">Ver estado</button>
    </form>
    <?php if ($busco && !$orden): ?>
      <div class="msg error" style="margin-top:1rem">No encontramos un auto con esa patente y ese DNI. Revisá los datos o escribinos.</div>
    <?php elseif ($orden): ?>
      <h3 style="margin-top:1rem"><?= e($orden['vehiculo']) ?> · <?= e($orden['patente']) ?></h3>
      <div class="pasos">
        <?php $hecho = true; foreach ($pasos as $k => $txt):
          $cls = $k === $orden['estado'] ? 'actual' : ($hecho ? 'hecho' : ''); if ($k === $orden['estado']) $hecho = false; ?>
          <span class="paso <?= $cls ?>"><?= e($txt) ?></span>
        <?php endforeach; ?>
      </div>
      <p class="ayuda">Última actualización: <?= e(date('d/m/Y H:i', strtotime($orden['actualizado_en']))) ?></p>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Presupuesto estimado</h2>
    <form class="f" onsubmit="return false">
      <div><label for="cot-serv">Servicio</label>
        <select id="cot-serv"><?php foreach ($servicios as $s): ?>
          <option value="<?= e($s['precio_base']) ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
      <div><label for="cot-tam">Tipo de vehículo</label>
        <select id="cot-tam"><option value="1">Auto chico o mediano</option><option value="1.25">Sedán grande o SUV</option><option value="1.5">Pick-up o utilitario</option></select></div>
    </form>
    <h2 id="cot-total" style="margin-top:1rem"></h2>
    <p class="ayuda">Valor de mano de obra aproximado. El precio final se confirma después del diagnóstico. <?= e(TALLER_PAGO) ?></p>
  </section>
</div>

<section class="panel">
  <h2>Dónde estamos</h2>
  <iframe class="mapa" src="<?= e($mapa) ?>" title="Mapa del taller" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</section>

<script>
(function () {
  const s = document.getElementById('cot-serv'), t = document.getElementById('cot-tam'), o = document.getElementById('cot-total');
  function calc() {
    const n = Math.round(parseFloat(s.value) * parseFloat(t.value));
    o.textContent = 'Desde $ ' + n.toLocaleString('es-AR');
  }
  s.addEventListener('change', calc); t.addEventListener('change', calc); calc();
})();
</script>
<?php pie();
