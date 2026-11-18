import {service} from '../services.js';
import {escapeHtml} from '../shared.js';

export async function initPublicArticle() {
  const message = document.querySelector('#public-article-message');
  const content = document.querySelector('#public-article-content');
  const id = new URLSearchParams(location.search).get('id');
  if (!/^[1-9]\d*$/.test(id ?? '')) {
    message.textContent = 'El identificador del artículo no es válido.';
    return;
  }

  try {
    const {project} = await service.getPublicProject(id);
    document.querySelector('#public-article-title').textContent = project.titulo;
    document.querySelector('#public-article-data').innerHTML = [
      ['Resumen', project.resumen],
      ['Departamento', project.departamento],
      ['Área(s) de conocimiento', project.areas],
      ['Autor(es)', project.autores],
      ['Docente asesor', project.asesor || '—'],
      ['Palabras clave', project.palabras_clave || '—'],
      ['Fecha de publicación', project.fecha || '—'],
    ].map(([label, value]) =>
      `<div><strong>${escapeHtml(label)}</strong><span>${escapeHtml(value)}</span></div>`
    ).join('');

    const authenticated = Boolean(window.INVESTIGATEC_AUTH_USER);
    const download = document.querySelector('#public-article-download');
    const login = document.querySelector('#public-article-login');
    const preview = document.querySelector('#public-article-preview');
    const accessMessage = document.querySelector('#public-article-access-message');
    download.hidden = true;
    login.hidden = true;
    preview.hidden = true;
    if (project.tiene_documento && authenticated) {
      download.href = service.getPublicProjectDownloadUrl(project.id_proyecto);
      download.hidden = false;
      document.querySelector('#public-article-pdf').src = service.getPublicProjectPreviewUrl(project.id_proyecto);
      preview.hidden = false;
    } else if (!authenticated) {
      login.hidden = false;
      accessMessage.textContent = 'Inicia sesión para tener acceso completo al artículo y consultar el documento.';
      accessMessage.hidden = false;
    } else {
      accessMessage.textContent = 'Este artículo no tiene un documento PDF disponible.';
      accessMessage.hidden = false;
    }
    content.hidden = false;
    message.hidden = true;
  } catch (error) {
    message.textContent = error.message;
  }
}
