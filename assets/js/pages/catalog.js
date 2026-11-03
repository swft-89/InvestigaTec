import {service} from '../services.js';
import {articleCard, escapeHtml} from '../shared.js';

export async function initCatalog() {
  const search = document.querySelector('#search');
  const area = document.querySelector('#area');
  const year = document.querySelector('#year');
  const list = document.querySelector('#articles-list');
  const count = document.querySelector('#result-count');
  let all;

  function render() {
    const query = search.value.trim().toLocaleLowerCase();
    const results = all.filter(article =>
      (!area.value || article.areas.split(', ').includes(area.value)) &&
      (!year.value || article.fecha.startsWith(year.value)) &&
      [article.titulo, article.autores, article.palabras_clave, article.resumen]
        .some(value => value.toLocaleLowerCase().includes(query))
    );
    count.textContent = `${results.length} artículo(s) encontrado(s)`;
    list.innerHTML = results.length
      ? results.map(articleCard).join('')
      : '<div class="panel">No se encontraron artículos publicados con esos filtros.</div>';
  }

  try {
    ({articles: all} = await service.getPublicProjects());
    [...new Set(all.flatMap(article => article.areas.split(', ')))].filter(Boolean).sort()
      .forEach(value => area.add(new Option(value, value)));
    [...new Set(all.map(article => article.fecha.slice(0, 4)))].filter(Boolean).sort().reverse()
      .forEach(value => year.add(new Option(value, value)));
    [search, area, year].forEach(element => element.addEventListener('input', render));
    render();
  } catch (error) {
    count.textContent = '';
    list.innerHTML = `<div class="panel" role="alert">${escapeHtml(error.message)}</div>`;
  }
}
