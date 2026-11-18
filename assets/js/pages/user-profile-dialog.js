import {service} from '../services.js';
import {escapeHtml} from '../shared.js';

const userTypes = {
  ESTUDIANTE: 'Estudiante',
  DOCENTE: 'Docente',
  EGRESADO: 'Egresado',
};
const accountStates = {
  ACTIVA: 'Activa',
  INACTIVA: 'Inactiva',
  BLOQUEADA: 'Suspendida',
};
const participationTypes = {
  AUTOR: 'Autor',
  ASESOR: 'Asesor',
};
let profileRequestId = 0;

function appendDetail(list, label, value) {
  if (!value) return;
  list.insertAdjacentHTML('beforeend', `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`);
}

export async function openUserProfile(userId, dialog) {
  const currentRequestId = ++profileRequestId;
  const message = dialog.querySelector('#user-profile-message');
  const content = dialog.querySelector('#user-profile-content');
  const avatar = dialog.querySelector('#user-profile-avatar');
  const placeholder = dialog.querySelector('#user-profile-avatar-placeholder');
  content.hidden = true;
  message.textContent = 'Cargando perfil...';
  if (!dialog.open) dialog.showModal();

  try {
    const {profile, projects} = await service.getUserDetails(userId);
    if (currentRequestId !== profileRequestId || !dialog.open) return;
    const fullName = [profile.nombre, profile.apellido_paterno, profile.apellido_materno].filter(Boolean).join(' ');
    dialog.querySelector('#user-profile-name').textContent = fullName;
    dialog.querySelector('#user-profile-subtitle').textContent =
      `${profile.es_administrador ? 'Administrador' : userTypes[profile.tipo_usuario] || profile.tipo_usuario}` +
      (profile.afiliacion ? ` · ${profile.afiliacion}` : '');

    avatar.hidden = !profile.imagen_perfil;
    avatar.removeAttribute('src');
    placeholder.hidden = Boolean(profile.imagen_perfil);
    placeholder.textContent = fullName.split(/\s+/u).map(part => part[0]).slice(0, 2).join('').toLocaleUpperCase();
    if (profile.imagen_perfil) {
      avatar.src = profile.imagen_perfil;
      avatar.alt = `Foto de perfil de ${fullName}`;
    }

    const details = dialog.querySelector('#user-profile-details');
    details.replaceChildren();
    appendDetail(details, 'Correo electrónico', profile.correo);
    appendDetail(details, 'Afiliación', profile.afiliacion);
    appendDetail(details, 'Número de control', profile.num_control);
    appendDetail(details, 'Semestre', profile.semestre ? String(profile.semestre) : '');
    appendDetail(details, 'Generación', profile.generacion);
    appendDetail(details, 'Habilidades', profile.habilidad);
    appendDetail(details, 'Intereses de investigación', profile.intereses);
    if (profile.estado_cuenta) {
      appendDetail(details, 'Estado de la cuenta', accountStates[profile.estado_cuenta] || profile.estado_cuenta);
      if (!profile.ext_aprobada) appendDetail(details, 'Aprobación', 'Pendiente');
    }

    const projectList = dialog.querySelector('#user-profile-project-list');
    projectList.innerHTML = projects.length
      ? projects.map(project => `
        <article class="user-profile-project">
          <a href="?page=${window.INVESTIGATEC_AUTH_USER?.is_admin ? 'articulo' : 'detalle-articulo'}&amp;id=${encodeURIComponent(project.id_proyecto)}">${escapeHtml(project.titulo)}</a>
          <span>${escapeHtml(participationTypes[project.participacion] || project.participacion || 'Participante')}${window.INVESTIGATEC_AUTH_USER?.is_admin ? ` · ${escapeHtml(project.estado)}` : ''}</span>
        </article>`).join('')
      : '<p class="muted">No hay artículos visibles asociados a este usuario.</p>';
    content.hidden = false;
    message.textContent = '';
  } catch (error) {
    if (currentRequestId !== profileRequestId || !dialog.open) return;
    message.textContent = error.message || 'No se pudo cargar el perfil.';
  }
}

export function initUserProfileDialog(dialog = document.querySelector('#user-profile-dialog')) {
  if (!dialog) return;
  dialog.querySelector('[data-close-user-profile]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });
}
