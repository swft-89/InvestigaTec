import {service} from '../services.js';
import {escapeHtml} from '../shared.js';

const statusLabels = {
  BORRADOR: 'Borrador',
  EN_REVISION: 'En revisión',
  REQUIERE_CAMBIOS: 'Requiere cambios',
  APROBADO: 'Aprobado',
  RECHAZADO: 'Rechazado',
  PUBLICADO: 'Publicado',
};

function statusClass(status) {
  if (status === 'APROBADO' || status === 'PUBLICADO') return 'approved';
  if (status === 'RECHAZADO') return 'rejected';
  return 'review';
}

export async function initMyArticles() {
  const tbody = document.querySelector('#my-list');
  if (!tbody) return;

  try {
    const {projects} = await service.getMyProjects();
    tbody.innerHTML = projects.length
      ? projects.map(project => `<tr>
          <td><strong><a class="article-link" href="?page=articulo&amp;id=${encodeURIComponent(project.id_proyecto)}">${escapeHtml(project.titulo)}</a></strong></td>
          <td>${escapeHtml(project.areas)}</td>
          <td>${escapeHtml(project.fecha ?? '—')}</td>
          <td><span class="status ${statusClass(project.estado)}">${escapeHtml(statusLabels[project.estado] ?? project.estado)}</span></td>
          <td>${escapeHtml(project.version)}</td>
          <td>${escapeHtml(project.archivo ?? 'Sin documento')}</td>
        </tr>`).join('')
      : '<tr><td colspan="6">Todavía no tienes artículos registrados.</td></tr>';
  } catch (error) {
    tbody.innerHTML = `<tr><td colspan="6" role="alert">${escapeHtml(error.message)}</td></tr>`;
  }
}
