<?php
$usuarioCuenta = current_user();
$datosEstudiante = null;
if ($usuarioCuenta && $usuarioCuenta['type'] === 'ESTUDIANTE') {
    try {
        require_once __DIR__ . '/../config/database.php';
        $consultaCuenta = db()->prepare(
            'SELECT e.num_control, e.semestre, c.nombre AS carrera, u.estado_cuenta
             FROM estudiantes e
             JOIN carreras c ON c.id_carrera = e.id_carrera
             JOIN usuarios u ON u.id_usuario = e.id_usuario
             WHERE e.id_usuario = :id'
        );
        $consultaCuenta->execute(['id' => $usuarioCuenta['id']]);
        $datosEstudiante = $consultaCuenta->fetch() ?: null;
    } catch (Throwable $ex) {
        error_log('InvestigaTec datos cuenta: ' . $ex->getMessage());
    }
}
?>
<?php if ($usuarioCuenta): ?>
<div class="page-head"><p class="eyebrow">Mi cuenta</p><h1>Cuenta de estudiante</h1></div>
<section class="panel account-summary" aria-labelledby="cuenta-nombre">
  <div class="account-summary-head">
    <div class="account-avatar" aria-hidden="true">👤</div>
    <div>
      <h2 id="cuenta-nombre"><?= e($usuarioCuenta['name']) ?></h2>
      <p class="muted"><?= e($usuarioCuenta['type'] === 'ESTUDIANTE' ? 'Estudiante' : $usuarioCuenta['type']) ?></p>
      <span class="account-state">Sesión activa</span>
    </div>
  </div>
  <div class="account-summary-details">
    <div><strong>Correo electrónico</strong><span><?= e($usuarioCuenta['email']) ?></span></div>
    <?php if ($datosEstudiante): ?>
      <div><strong>Carrera</strong><span><?= e($datosEstudiante['carrera']) ?></span></div>
      <div><strong>Número de control</strong><span><?= e($datosEstudiante['num_control']) ?></span></div>
      <div><strong>Semestre</strong><span><?= $datosEstudiante['semestre'] !== null ? e($datosEstudiante['semestre']) : 'No especificado' ?></span></div>
    <?php else: ?>
      <p class="muted">Los datos académicos no están disponibles en este momento.</p>
    <?php endif; ?>
  </div>
  <button class="button secondary" id="logout" type="button">Cerrar sesión</button>
  <p id="logout-error" role="alert" aria-live="polite"></p>
</section>
<?php else: ?>
<div class="page-head"><p class="eyebrow">Acceso</p><h1>Registro e inicio de sesión</h1></div>
<div class="account-grid">
<form class="panel form-panel" id="login-form"><h2>Iniciar sesión</h2><p>Ingresa con el correo que registraste.</p>
<label>Correo electrónico<input type="email" name="email" autocomplete="username" required></label>
<label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label>
<button class="button primary" type="submit">Entrar</button><p id="login-error" role="alert" aria-live="polite"></p></form>
<div class="panel form-panel"><h2>Registro de estudiantes</h2>
<form id="register-form">
<label>Nombre<input name="nombre" maxlength="100" autocomplete="given-name" required></label>
<label>Apellido paterno<input name="apellido_paterno" maxlength="100" required></label>
<label>Apellido materno (opcional)<input name="apellido_materno" maxlength="100"></label>
<label>Correo electrónico<input name="correo" type="email" autocomplete="email" required></label>
<label>Número de control<input name="num_control" maxlength="20" minlength="4" required></label>
<label>Carrera<select name="id_carrera" id="carrera-select" required><option value="">Cargando carreras...</option></select></label>
<label>Semestre (opcional)<input type="number" name="semestre" min="1" max="20"></label>
<label>Contraseña (mínimo 10 caracteres)<input name="password" type="password" minlength="10" maxlength="256" autocomplete="new-password" required></label>
<button type="submit" class="button secondary">Crear cuenta</button><p id="register-error" role="alert" aria-live="polite"></p>
</form>
<p>La recuperación de contraseña todavía está pendiente de implementación.</p></div></div>
<?php endif; ?>
