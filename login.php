<?php
require_once __DIR__ . '/conexion.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!csrf_ok()) $error = 'La sesión expiró. Recargá la página.';
    else {
        $st = $conexion->prepare('SELECT id, nombre, password_hash, rol FROM usuarios
                                  WHERE email = :e AND archivado_en IS NULL LIMIT 1');
        $st->execute(['e' => $email]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            $_SESSION['nombre'] = $u['nombre'];
            $_SESSION['rol'] = $u['rol'];
            header('Location: ' . ($u['rol'] === 'admin' ? 'admin.php' : 'index.php'));
            exit;
        }
        $error = 'Email o contraseña incorrectos, o la cuenta está desactivada.';
    }
}
cabecera('Ingresar'); ?>
<section class="panel" style="max-width:460px;margin:0 auto">
  <h2>Ingresar</h2>
  <?php if (isset($_GET['registrado'])): ?><div class="msg ok">Cuenta creada. Ya podés ingresar.</div><?php endif; ?>
  <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
  <form class="f" method="post">
    <?= csrf_campo() ?>
    <div><label for="email">Email</label><input id="email" type="email" name="email" required></div>
    <div><label for="password">Contraseña</label><input id="password" type="password" name="password" required></div>
    <button type="submit">Ingresar</button>
  </form>
  <p class="ayuda">¿No tenés cuenta? <a href="registro.php">Crear cuenta</a></p>
</section>
<?php pie();
