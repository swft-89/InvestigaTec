import {service} from '../services.js';
import {escapeHtml, toast} from '../shared.js';

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

  const loadProjects = async () => {
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
            <td>${['BORRADOR', 'RECHAZADO'].includes(project.estado)
              ? `<button class="button secondary delete-project-button" type="button" data-project-id="${Number(project.id_proyecto)}">Eliminar</button>`
              : '—'}</td>
          </tr>`).join('')
        : '<tr><td colspan="7">Todavía no tienes artículos registrados.</td></tr>';
    } catch (error) {
      tbody.innerHTML = `<tr><td colspan="7" role="alert">${escapeHtml(error.message)}</td></tr>`;
    }
  };

  tbody.addEventListener('click', async event => {
    if (!(event.target instanceof Element)) return;
    const button = event.target.closest('.delete-project-button');
    if (!button || button.disabled ||
        !confirm('¿Marcar este artículo como eliminado? Ya no aparecerá en tus artículos, pero el administrador podrá consultarlo.')) return;
    button.disabled = true;
    try {
      await service.deleteProject(Number(button.dataset.projectId));
      toast('El artículo se marcó como eliminado.');
      await loadProjects();
    } catch (error) {
      button.disabled = false;
      toast(error.message);
    }
  });
  await loadProjects();
}
