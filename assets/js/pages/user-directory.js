import {service} from '../services.js';
import {escapeHtml} from '../shared.js';
import {initUserProfileDialog, openUserProfile} from './user-profile-dialog.js';

export function initUserDirectory() {
  const search = document.querySelector('#user-search');
  const message = document.querySelector('#user-directory-message');
  const results = document.querySelector('#user-directory-results');
  const dialog = document.querySelector('#user-profile-dialog');
  if (!search || !message || !results || !dialog) return;
  initUserProfileDialog(dialog);
  let requestId = 0;
  let timer;

  async function loadUsers() {
    const query = search.value.trim();
    const currentRequestId = ++requestId;
    results.replaceChildren();
    if (query.length < 2) {
      message.textContent = 'Escribe al menos 2 caracteres para buscar.';
      return;
    }
    message.textContent = 'Buscando usuarios...';
    try {
      const {users} = await service.getUsers(query);
      if (currentRequestId !== requestId) return;
      results.innerHTML = users.map(user => {
        const fullName = [user.nombre, user.apellido_paterno, user.apellido_materno].filter(Boolean).join(' ');
        const initials = fullName.split(/\s+/u).map(part => part[0]).slice(0, 2).join('').toLocaleUpperCase();
        const avatar = user.imagen_perfil
          ? `<img src="${escapeHtml(user.imagen_perfil)}" alt="">`
          : `<span class="user-directory-initials" aria-hidden="true">${escapeHtml(initials)}</span>`;
        return `<button class="user-directory-card" type="button" data-user-profile-id="${Number(user.id_usuario)}">
          ${avatar}
          <span class="user-directory-card-info">
            <strong>${escapeHtml(fullName)}</strong>
            <span>${escapeHtml(user.ext_administrador ? 'Administrador' : userTypes[user.tipo_usuario] || user.tipo_usuario)}${user.afiliacion ? ` · ${escapeHtml(user.afiliacion)}` : ''}</span>
          </span>
          <span class="user-directory-card-arrow" aria-hidden="true">›</span>
        </button>`;
      }).join('');
      message.textContent = users.length
        ? `${users.length} usuario${users.length === 1 ? '' : 's'} encontrado${users.length === 1 ? '' : 's'}${users.length === 50 ? ' · mostrando los primeros 50' : ''}.`
        : 'No se encontraron usuarios para esa búsqueda.';
    } catch (error) {
      if (currentRequestId !== requestId) return;
      message.textContent = error.message;
    }
  }

  search.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(loadUsers, 250);
  });
  results.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const card = event.target.closest('[data-user-profile-id]');
    if (card) openUserProfile(card.dataset.userProfileId, dialog);
  });
}

const userTypes = {
  ESTUDIANTE: 'Estudiante',
  DOCENTE: 'Docente',
  EGRESADO: 'Egresado',
};
