<div class="page-head"><p class="eyebrow">Acceso</p><h1>Iniciar sesión</h1><p class="subtitle">Ingresa con el correo que registraste.</p></div>
<form class="panel form-panel" id="login-form">
  <label>Correo electrónico<input type="email" name="email" autocomplete="username" required></label>
  <label>Contraseña
    <span class="password-field">
      <input id="login-password" type="password" name="password" autocomplete="current-password" required>
      <button class="password-visibility" id="login-password-toggle" type="button" aria-label="Mostrar contraseña" aria-pressed="false" aria-controls="login-password">
        <svg class="password-icon-show" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="password-icon-hide" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.5 15.5 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.1 0 2.1-.2 3-.5"/></svg>
      </button>
    </span>
  </label>
  <button class="button primary" type="submit">Entrar</button>
  <p id="login-error" role="alert" aria-live="polite"></p>
</form>
