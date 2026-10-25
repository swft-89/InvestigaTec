import {service} from '../services.js';
import {toast} from '../shared.js';

function fields(form) { return Object.fromEntries(new FormData(form).entries()); }
function message(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = value;
}

export function initAccount() {
  const toggle = document.getElementById('account-toggle');
  const popover = document.getElementById('account-popover');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    popover.hidden = open;
  });
  document.addEventListener('click', event => {
    if (popover && toggle && !popover.contains(event.target) && !toggle.contains(event.target)) {
      popover.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && popover && toggle) {
      popover.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }
  });

  const logout = document.getElementById('logout');
  logout?.addEventListener('click', async () => {
    logout.disabled = true;
    try {
      await service.logout();
      location.href = '?page=inicio';
    } catch (error) {
      message('logout-error', error.message);
      logout.disabled = false;
    }
  });

  const login = document.getElementById('login-form');
  const reg = document.getElementById('register-form');
  document.querySelectorAll('.password-visibility').forEach(passwordToggle => {
    passwordToggle.addEventListener('click', () => {
      const password = document.getElementById(passwordToggle.getAttribute('aria-controls'));
      if (!password) return;
      const visible = password.type === 'password';
      password.type = visible ? 'text' : 'password';
      passwordToggle.setAttribute('aria-pressed', String(visible));
      passwordToggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
  });
  if (reg) {
    service.fetchCareers().then(({carreras}) => {
      const select = document.getElementById('carrera-select');
      select.replaceChildren(new Option(carreras.length ? 'Selecciona una carrera' : 'No hay carreras registradas', ''));
      for (const c of carreras) select.add(new Option(`${c.nombre} (${c.siglas})`, c.id_carrera));
      if (!carreras.length) message('register-error', 'Antes registra un departamento y una carrera en PostgreSQL.');
    }).catch(error => message('register-error', error.message));
  }

  login?.addEventListener('submit', async event => {
    event.preventDefault();
    message('login-error', '');
    const button = login.querySelector('[type=submit]');
    button.disabled = true;
    try {
      await service.login({correo:login.elements.email.value.trim(), password:login.elements.password.value});
      toast('Sesión iniciada');
      location.href = login.elements.email.value.trim().toLowerCase() === 'admin@investigatec.mx'
        ? '?page=admin'
        : '?page=inicio';
    } catch (error) { message('login-error', error.message); }
    finally { button.disabled = false; }
  });

  reg?.addEventListener('submit', async event => {
    event.preventDefault();
    message('register-error', '');
    const button = reg.querySelector('[type=submit]');
    button.disabled = true;
    try {
      const result = await service.registerStudent(fields(reg));
      reg.reset();
      message('register-error', result.message);
      toast('Cuenta creada correctamente.');
    } catch (error) { message('register-error', error.message); }
    finally { button.disabled = false; }
  });
}
