<?php
require_once __DIR__ . '/conexion.php';
$error = '';
$v = ['nombre' => '', 'apellido' => '', 'dni' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!csrf_ok()) $error = 'La sesión expiró. Recargá la página.';
    elseif ($v['nombre'] === '' || $v['apellido'] === '') $error = 'Completá nombre y apellido.';
    elseif (!preg_match('/^\d{7,9}$/', $v['dni'])) $error = 'El DNI debe tener entre 7 y 9 números, sin puntos.';
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $error = 'Ingresá un email válido.';
    elseif (strlen($pass) < 8) $error = 'La contraseña debe tener al menos 8 caracteres.';
    else {
        try {
            $st = $conexion->prepare('INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                                      VALUES (:n, :a, :d, :e, :p, "usuario")');
            $st->execute(['n' => $v['nombre'], 'a' => $v['apellido'], 'd' => $v['dni'], 'e' => $v['email'],
                          'p' => password_hash($pass, PASSWORD_DEFAULT)]);
            header('Location: login.php?registrado=1');
            exit;
        } catch (PDOException $ex) {
            $error = $ex->getCode() === '23000' ? 'Ya existe una cuenta con ese DNI o email.' : 'No se pudo crear la cuenta.';
        }
    }
}
cabecera('Crear cuenta'); ?>
<section class="panel" style="max-width:560px;margin:0 auto">
  <h2>Crear cuenta</h2>
  <p class="ayuda">Con tu cuenta podés pedir turnos y escribirle al mecánico.</p>
  <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
  <form class="f" method="post">
    <?= csrf_campo() ?>
    <div class="dos">
      <div><label for="nombre">Nombre</label><input id="nombre" name="nombre" required maxlength="80" value="<?= e($v['nombre']) ?>"></div>
      <div><label for="apellido">Apellido</label><input id="apellido" name="apellido" required maxlength="80" value="<?= e($v['apellido']) ?>"></div>
    </div>
    <div><label for="dni">DNI</label><input id="dni" name="dni" required inputmode="numeric" value="<?= e($v['dni']) ?>"></div>
    <div><label for="email">Email</label><input id="email" type="email" name="email" required value="<?= e($v['email']) ?>"></div>
    <div><label for="password">Contraseña</label><input id="password" type="password" name="password" required minlength="8"><p class="ayuda">Mínimo 8 caracteres.</p></div>
    <button type="submit">Crear cuenta</button>
  </form>
</section>
<?php pie();
