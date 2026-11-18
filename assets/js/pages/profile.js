import {service} from '../services.js';
import {toast} from '../shared.js';

function message(form, text) {
  const output = form.querySelector('[role="alert"]');
  if (output) output.textContent = text;
}

export function initProfile() {
  const form = document.querySelector('#profile-form');
  if (!form) return;
  if (!window.INVESTIGATEC_AUTH_USER) {
    location.href = '?page=login';
    return;
  }

  const career = form.elements.id_carrera;
  const loadProfile = async () => {
    const [{profile}, {carreras}] = await Promise.all([service.getProfile(), service.fetchCareers()]);
    career.replaceChildren(new Option('Selecciona una carrera', ''));
    for (const item of carreras) career.add(new Option(`${item.nombre} (${item.siglas})`, item.id_carrera));
    for (const key of ['nombre', 'apellido_paterno', 'apellido_materno', 'correo', 'num_control', 'semestre', 'id_carrera', 'generacion', 'habilidad', 'intereses']) {
      const field = form.elements.namedItem(key);
      if (!field) throw new Error(`No se encontró el campo "${key}" en el formulario de perfil.`);
      field.value = profile[key] ?? '';
    }
    const preview = form.querySelector('#profile-photo-preview');
    if (profile.imagen_perfil) {
      preview.src = profile.imagen_perfil;
      preview.hidden = false;
    }
  };

  loadProfile().catch(error => message(form, error.message));
  form.addEventListener('submit', async event => {
    event.preventDefault();
    message(form, '');
    const button = form.querySelector('[type="submit"]');
    button.disabled = true;
    try {
      await service.saveProfile(new FormData(form));
      toast('Perfil actualizado correctamente.');
      location.reload();
    } catch (error) {
      message(form, error.message);
    } finally {
      button.disabled = false;
    }
  });
}
