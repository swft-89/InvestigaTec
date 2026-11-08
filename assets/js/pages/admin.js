import {service} from '../services.js';
import {escapeHtml} from '../shared.js';
import {initUserProfileDialog, openUserProfile} from './user-profile-dialog.js';

const projectStatuses = [
  ['BORRADOR', 'Borrador'],
  ['EN_REVISION', 'En revisión'],
  ['REQUIERE_CAMBIOS', 'Requiere cambios'],
  ['APROBADO', 'Aprobado'],
  ['RECHAZADO', 'Rechazado'],
  ['PUBLICADO', 'Publicado'],
];
const statusLabels = {
  ACTIVA: 'Activa',
  INACTIVA: 'Inactiva',
  BLOQUEADA: 'Suspendida',
  BORRADOR: 'Borrador',
  EN_REVISION: 'En revisión',
  REQUIERE_CAMBIOS: 'Requiere cambios',
  APROBADO: 'Aprobado',
  RECHAZADO: 'Rechazado',
  PUBLICADO: 'Publicado',
  PENDIENTE: 'Pendiente',
  RESUELTO: 'Resuelto',
  RESPONDIDA: 'Respondida',
};
const formatDate = value => {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat('es-MX', {dateStyle: 'medium', timeStyle: 'short'}).format(date);
};

async function runAction(payload) {
  const result = await service.adminAction(payload);
  await loadDashboard();
  return result;
}

const output = document.querySelector('#admin-message');
let dashboardData;

function limitedItems(items, key, label) {
  const limit = document.querySelector(`[data-admin-limit="${key}"]`).value;
  const visible = limit === 'all' ? items : items.slice(0, Number(limit));
  const count = document.querySelector({
    users: '#admin-user-count',
    projects: '#admin-project-count',
    reports: '#admin-report-count',
    requests: '#admin-request-count',
  }[key]);
  count.textContent = `${items.length} ${label} · mostrando ${visible.length}`;
  return visible;
}

function renderUsers(users) {
  document.querySelector('#admin-users-list').innerHTML = limitedItems(users, 'users', 'cuentas').map(user => {
    const status = user.estado_cuenta === 'BLOQUEADA'
      ? 'Suspendida'
      : user.ext_aprobada ? 'Activa' : 'Pendiente de aprobación';
    const protectedAccount = user.ext_administrador;
    const action = protectedAccount ? '<span class="muted">Cuenta protegida</span>' : user.ext_aprobada && user.estado_cuenta !== 'BLOQUEADA'
      ? `<button class="button secondary admin-action" type="button" data-action="suspend-user" data-id="${user.id_usuario}">Suspender</button>`
      : `<button class="button primary admin-action" type="button" data-action="approve-user" data-id="${user.id_usuario}">${user.ext_aprobada ? 'Reactivar' : 'Aprobar'}</button>`;
    const fullName = [user.nombre, user.apellido_paterno, user.apellido_materno].filter(Boolean).join(' ');
    return `<tr>
      <td><button class="admin-user-profile" type="button" data-user-profile-id="${Number(user.id_usuario)}">${escapeHtml(fullName)}</button></td>
      <td>${escapeHtml(user.correo)}</td>
      <td>${escapeHtml(user.ext_administrador ? 'ADMINISTRADOR' : user.tipo_usuario)}</td>
      <td>${escapeHtml(user.afiliacion || '—')}</td>
      <td>${escapeHtml(status)}</td>
      <td>${action}</td>
    </tr>`;
  }).join('') || '<tr><td colspan="6">No hay cuentas registradas.</td></tr>';
}

function renderProjects(projects) {
  document.querySelector('#admin-projects-list').innerHTML = limitedItems(projects, 'projects', 'artículos').map(project => {
    const options = projectStatuses.map(([value, label]) =>
      `<option value="${value}" ${value === project.estado ? 'selected' : ''}>${label}</option>`
    ).join('');
    return `<tr>
      <td><a href="?page=articulo&amp;id=${encodeURIComponent(project.id_proyecto)}">${escapeHtml(project.titulo)}</a></td>
      <td>${escapeHtml(project.creador_correo)}</td>
      <td>${escapeHtml(statusLabels[project.estado] || project.estado)}</td>
      <td>${project.reportes_abiertos}</td>
      <td><form class="admin-inline-form" data-form="project-status" data-id="${project.id_proyecto}">
        <select name="status" aria-label="Nuevo estado de ${escapeHtml(project.titulo)}">${options}</select>
        <button class="button secondary" type="submit">Guardar</button>
      </form></td>
    </tr>`;
  }).join('') || '<tr><td colspan="5">No hay artículos.</td></tr>';
}

function renderReports(reports) {
  document.querySelector('#admin-reports-list').innerHTML = limitedItems(reports, 'reports', 'reportes').map(report => `
    <article class="admin-record">
      <div class="section-heading"><h3>${escapeHtml(report.proyecto_titulo)}</h3>
        <span class="status ${report.estado === 'RESUELTO' ? 'approved' : 'review'}">${escapeHtml(statusLabels[report.estado] || report.estado)}</span>
      </div>
      <p>${escapeHtml(report.motivo)}</p>
      <p class="muted">Reportado por ${escapeHtml(report.reportado_por)} · ${escapeHtml(report.correo_reportante)} · ${escapeHtml(formatDate(report.fecha_creacion))}</p>
      <div class="form-actions">
        <a class="button secondary" href="?page=articulo&amp;id=${encodeURIComponent(report.id_proyecto)}">Revisar artículo</a>
        ${report.estado === 'PENDIENTE' ? `<button class="button secondary admin-action" type="button" data-action="review-report" data-id="${report.id_reporte}">Marcar en revisión</button>` : ''}
        ${report.estado !== 'RESUELTO' ? `<button class="button primary admin-action" type="button" data-action="resolve-report" data-id="${report.id_reporte}">Resolver reporte</button>` : ''}
      </div>
    </article>`).join('') || '<p class="muted">No se han recibido reportes de artículos.</p>';
}

function renderSupport(requests) {
  const visible = limitedItems(requests, 'requests', 'solicitudes');
  document.querySelector('#admin-support-list').innerHTML = visible.map(request => `
    <button class="support-request-card" type="button" data-support-request="${request.id_solicitud}">
      <strong>${escapeHtml(request.asunto)}</strong>
      <span>${escapeHtml(request.nombre_contacto)}</span>
      <time datetime="${escapeHtml(request.fecha_creacion)}">${escapeHtml(formatDate(request.fecha_creacion))}</time>
      <span class="status ${request.estado === 'RESPONDIDA' ? 'approved' : 'review'}">${escapeHtml(statusLabels[request.estado] || request.estado)}</span>
    </button>`).join('') || '<p class="muted">No se han recibido solicitudes de soporte.</p>';
}

async function loadDashboard() {
  output.textContent = 'Cargando información administrativa...';
  try {
    const data = await service.getAdminDashboard();
    renderUsers(data.users);
    renderProjects(data.projects);
    renderReports(data.reports);
    renderSupport(data.requests);
    dashboardData = data;
    output.textContent = '';
  } catch (error) {
    output.textContent = error.message || 'No se pudo cargar el panel administrativo.';
  }
}

export function initAdmin() {
  const userProfileDialog = document.querySelector('#user-profile-dialog');
  initUserProfileDialog(userProfileDialog);
  for (const select of document.querySelectorAll('[data-admin-limit]')) {
    select.addEventListener('change', () => {
      if (!dashboardData) return;
      renderUsers(dashboardData.users);
      renderProjects(dashboardData.projects);
      renderReports(dashboardData.reports);
      renderSupport(dashboardData.requests);
    });
  }

  document.querySelectorAll('[data-admin-tab]').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('[data-admin-tab]').forEach(item => {
        const selected = item === tab;
        item.classList.toggle('active', selected);
        item.setAttribute('aria-selected', String(selected));
        document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
      });
    });
  });

  document.querySelector('#admin-users-list').addEventListener('click', async event => {
    const profileButton = event.target.closest('[data-user-profile-id]');
    if (profileButton) {
      await openUserProfile(profileButton.dataset.userProfileId, userProfileDialog);
      return;
    }
    const button = event.target.closest('[data-action]');
    if (!button) return;
    button.disabled = true;
    try {
      const action = button.dataset.action === 'approve-user' ? 'APPROVED' : 'SUSPENDED';
      const result = await runAction({action: 'set_user_status', user_id: Number(button.dataset.id), status: action});
      output.textContent = result.message;
    } catch (error) {
      output.textContent = error.message;
      button.disabled = false;
    }
  });

  document.querySelector('#admin-projects-list').addEventListener('submit', async event => {
    const form = event.target.closest('[data-form="project-status"]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const result = await runAction({
        action: 'set_project_status',
        project_id: Number(form.dataset.id),
        status: form.elements.status.value,
      });
      output.textContent = result.message;
    } catch (error) {
      output.textContent = error.message;
      button.disabled = false;
    }
  });

  document.querySelector('#admin-reports-list').addEventListener('click', async event => {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    button.disabled = true;
    try {
      const status = button.dataset.action === 'review-report' ? 'EN_REVISION' : 'RESUELTO';
      const result = await runAction({action: 'set_report_status', report_id: Number(button.dataset.id), status});
      output.textContent = result.message;
    } catch (error) {
      output.textContent = error.message;
      button.disabled = false;
    }
  });

  const supportDialog = document.querySelector('#admin-support-dialog');
  const supportReply = document.querySelector('#admin-support-reply');
  document.querySelector('#admin-support-list').addEventListener('click', event => {
    const button = event.target.closest('[data-support-request]');
    if (!button || !dashboardData) return;
    const request = dashboardData.requests.find(item =>
      Number(item.id_solicitud) === Number(button.dataset.supportRequest)
    );
    if (!request) return;
    document.querySelector('#admin-support-dialog-title').textContent = request.asunto;
    document.querySelector('#admin-support-dialog-meta').textContent =
      `${request.tipo} · ${request.nombre_contacto} · ${request.correo_contacto} · ${formatDate(request.fecha_creacion)} · ${statusLabels[request.estado] || request.estado} · ${request.id_usuario ? 'Cuenta registrada' : 'Invitado'}`;
    document.querySelector('#admin-support-dialog-message').textContent = request.mensaje;
    const previousReply = document.querySelector('#admin-support-dialog-previous-reply');
    previousReply.hidden = !request.respuesta;
    previousReply.textContent = request.respuesta ? `Respuesta guardada: ${request.respuesta}` : '';
    supportReply.dataset.id = request.id_solicitud;
    supportReply.elements.reply.value = request.respuesta || '';
    supportReply.querySelector('button[type="submit"]').textContent =
      request.respuesta ? 'Actualizar respuesta' : 'Enviar respuesta';
    supportDialog.showModal();
  });
  document.querySelector('#admin-support-dialog-close').addEventListener('click', () => supportDialog.close());
  supportReply.addEventListener('submit', async event => {
    const form = event.currentTarget;
    event.preventDefault();
    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const result = await runAction({
        action: 'reply_support',
        request_id: Number(form.dataset.id),
        reply: form.elements.reply.value,
      });
      supportDialog.close();
      output.textContent = result.message;
    } catch (error) {
      output.textContent = error.message;
      button.disabled = false;
    }
  });

  loadDashboard();
}
