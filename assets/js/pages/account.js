import {service} from '../services.js';
import {toast} from '../main.js';

function fields(form) { return Object.fromEntries(new FormData(form).entries()); }
function message(id, value) {
  const element = document.getElementById(id);
  if (element) element.textContent = value;
}

export function initAccount() {
  const logout = document.getElementById('logout');
  logout?.addEventListener('click', async () => {
    logout.disabled = true;
    try {
      await service.logout();
      location.href = '?page=acceso';
    } catch (error) {
      message('logout-error', error.message);
      logout.disabled = false;
    }
  });

  // Cuando hay sesión activa, los formularios no se muestran.
  const login = document.getElementById('login-form');
  const reg = document.getElementById('register-form');
  if (!login || !reg) return;

  service.fetchCareers().then(({carreras}) => {
    const select = document.getElementById('carrera-select');
    select.replaceChildren(new Option(carreras.length ? 'Selecciona una carrera' : 'No hay carreras registradas', ''));
    for (const c of carreras) select.add(new Option(`${c.nombre} (${c.siglas})`, c.id_carrera));
    if (!carreras.length) message('register-error', 'Antes registra un departamento y una carrera en PostgreSQL.');
  }).catch(error => message('register-error', error.message));

  login.addEventListener('submit', async event => {
    event.preventDefault();
    message('login-error', '');
    const button = login.querySelector('[type=submit]');
    button.disabled = true;
    try {
      await service.login({correo:login.elements.email.value.trim(), password:login.elements.password.value});
      toast('Sesión iniciada');
      location.href = '?page=inicio';
    } catch (error) { message('login-error', error.message); }
    finally { button.disabled = false; }
  });

  reg.addEventListener('submit', async event => {
    event.preventDefault();
    message('register-error', '');
    const button = reg.querySelector('[type=submit]');
    button.disabled = true;
    try {
      await service.registerStudent(fields(reg));
      reg.reset();
      toast('Cuenta creada. Ahora inicia sesión.');
    } catch (error) { message('register-error', error.message); }
    finally { button.disabled = false; }
  });
}
