<div class="page-head"><p class="eyebrow">Acceso</p><h1>Registro de estudiantes</h1><p class="subtitle">Crea tu cuenta para participar en InvestigaTec.</p></div>
<form class="panel form-panel registration-form" id="register-form">
  <label>Nombre<input name="nombre" maxlength="100" autocomplete="given-name" required></label>
  <label>Apellido paterno<input name="apellido_paterno" maxlength="100" autocomplete="family-name" required></label>
  <label>Apellido materno (opcional)<input name="apellido_materno" maxlength="100"></label>
  <label>Correo electrónico<input name="correo" type="email" autocomplete="email" required></label>
  <label>Número de control<input name="num_control" maxlength="20" minlength="4" required></label>
  <label>Carrera<select name="id_carrera" id="carrera-select" required><option value="">Cargando carreras...</option></select></label>
  <label>Semestre (opcional)<input type="number" name="semestre" min="1" max="20"></label>
  <label>Contraseña (mínimo 10 caracteres)
    <span class="password-field">
      <input id="register-password" name="password" type="password" minlength="10" maxlength="256" autocomplete="new-password" required>
      <button class="password-visibility" id="register-password-toggle" type="button" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="register-password">
        <svg class="password-icon-show" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="password-icon-hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.5 15.5 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.1 0 2.1-.2 3-.5"/></svg>
      </button>
    </span>
  </label>
  <button type="submit" class="button secondary">Crear cuenta</button>
  <p id="register-error" role="alert" aria-live="polite"></p>
</form>
