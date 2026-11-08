import {service} from '../services.js';
import {escapeHtml} from '../shared.js';

const statusLabels = {
  BORRADOR: 'Borrador',
  EN_REVISION: 'En revisión',
  REQUIERE_CAMBIOS: 'Requiere cambios',
  APROBADO: 'Aprobado',
  RECHAZADO: 'Rechazado',
};

export async function initReviewProjects() {
  const output = document.querySelector('#review-projects-message');
  const list = document.querySelector('#review-projects-list');
  try {
    const {projects} = await service.getReviewProjects();
    list.innerHTML = projects.map(project => `
      <tr>
        <td><a href="?page=articulo&amp;id=${encodeURIComponent(project.id_proyecto)}">${escapeHtml(project.titulo)}</a></td>
        <td>${escapeHtml(project.creador_correo)}</td>
        <td>${escapeHtml(statusLabels[project.estado] || project.estado)}</td>
        <td>${project.reportes_abiertos}</td>
        <td><a class="button secondary" href="?page=articulo&amp;id=${encodeURIComponent(project.id_proyecto)}">Ver artículo</a></td>
      </tr>`).join('') || '<tr><td colspan="5">No hay artículos pendientes de publicación.</td></tr>';
    output.textContent = '';
  } catch (error) {
    output.textContent = error.message;
  }
}
