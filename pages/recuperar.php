<div class="page-head"><p class="eyebrow">Acceso</p><h1>Recuperar contraseña</h1><p class="subtitle">Te enviaremos un enlace de verificación a tu correo.</p></div>
<form class="panel form-panel" id="recovery-request-form">
  <label>Correo electrónico<input type="email" name="email" autocomplete="email" maxlength="255" required></label>
  <button class="button primary" type="submit">Enviar enlace de recuperación</button>
  <p id="recovery-request-message" role="status" aria-live="polite"></p>
  <p id="recovery-request-error" role="alert" aria-live="polite"></p>
</form>
<form class="panel form-panel" id="recovery-reset-form" hidden>
  <label>Nueva contraseña (mínimo 10 caracteres)
    <span class="password-field">
      <input id="recovery-password" type="password" name="password" minlength="10" maxlength="256" autocomplete="new-password" required>
      <button class="password-visibility" type="button" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="recovery-password">
        <svg class="password-icon-show" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="password-icon-hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7s-3.6 7-10 7c-1.1 0-2.1-.2-3-.5"/></svg>
      </button>
    </span>
  </label>
  <label>Confirmar contraseña
    <input type="password" name="confirmation" minlength="10" maxlength="256" autocomplete="new-password" required>
  </label>
  <button class="button primary" type="submit">Cambiar contraseña</button>
  <p id="recovery-reset-message" role="status" aria-live="polite"></p>
  <p id="recovery-reset-error" role="alert" aria-live="polite"></p>
</form>
<p><a href="?page=login">Volver a iniciar sesión</a></p>
