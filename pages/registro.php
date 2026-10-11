<div class="page-head"><p class="eyebrow">Acceso</p><h1>Registro de estudiantes</h1><p class="subtitle">Crea tu cuenta para participar en InvestigaTec.</p></div>
<form class="panel form-panel registration-form" id="register-form">
  <label>Nombre<input name="nombre" maxlength="100" autocomplete="given-name" required></label>
  <label>Apellido paterno<input name="apellido_paterno" maxlength="100" autocomplete="family-name" required></label>
  <label>Apellido materno (opcional)<input name="apellido_materno" maxlength="100"></label>
  <label>Correo electrónico<input name="correo" type="email" autocomplete="email" required></label>
  <label>Número de control<input name="num_control" maxlength="20" minlength="4" required></label>
  <label>Carrera<select name="id_carrera" id="carrera-select" required><option value="">Cargando carreras...</option></select></label>
  <label>Semestre (opcional)<input type="number" name="semestre" min="1" max="20"></label>
  <label>Contraseña (mínimo 10 caracteres)<input name="password" type="password" minlength="10" maxlength="256" autocomplete="new-password" required></label>
  <button type="submit" class="button secondary">Crear cuenta</button>
  <p id="register-error" role="alert" aria-live="polite"></p>
</form>
