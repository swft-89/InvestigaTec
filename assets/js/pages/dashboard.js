import {service} from '../services.js';
import {articleCard, escapeHtml} from '../shared.js';

export async function initDashboard() {
  const list = document.querySelector('#recent-list');
  const stats = document.querySelector('#stats');
  try {
    const result = await service.getPublicProjects();
    list.innerHTML = result.articles.length
      ? result.articles.slice(0, 3).map(articleCard).join('')
      : '<div class="panel">Todavía no hay artículos publicados.</div>';
    if (stats && result.stats) {
      stats.innerHTML = `
        <div class="stat"><strong>${escapeHtml(result.stats.registrados)}</strong><span>Artículos registrados</span></div>
        <div class="stat"><strong>${escapeHtml(result.stats.publicados)}</strong><span>Artículos publicados</span></div>
        <div class="stat"><strong>${escapeHtml(result.stats.areas)}</strong><span>Áreas de conocimiento</span></div>`;
    }
  } catch (error) {
    list.innerHTML = `<div class="panel" role="alert">${escapeHtml(error.message)}</div>`;
  }
}
