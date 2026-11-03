import {service} from '../services.js';
import {escapeHtml, limitMultiSelect, toast} from '../shared.js';

const statusLabels = {
  BORRADOR: 'Borrador',
  EN_REVISION: 'En revisión',
  REQUIERE_CAMBIOS: 'Requiere cambios',
  APROBADO: 'Aprobado',
  RECHAZADO: 'Rechazado',
  PUBLICADO: 'Publicado',
};

const editableStates = new Set(['BORRADOR', 'REQUIERE_CAMBIOS']);

function showMessage(element, text) {
  element.textContent = text;
}

function renderProject(project) {
  document.querySelector('#article-title').textContent = project.titulo;
  const status = document.querySelector('#article-status');
  status.textContent = statusLabels[project.estado] ?? project.estado;
  status.className = `status ${project.estado === 'APROBADO' || project.estado === 'PUBLICADO' ? 'approved' : project.estado === 'RECHAZADO' ? 'rejected' : 'review'}`;

  const fields = [
    ['Resumen', project.resumen],
    ['Departamento', project.departamento],
    ['Áreas temáticas', project.areas.join(', ') || '—'],
    ['Palabras clave', project.palabras_clave.join(', ') || '—'],
    ['Autores', project.autores.join(', ') || '—'],
    ['Docente asesor', project.asesor || '—'],
    ['Fecha de publicación', project.fecha_publicacion || '—'],
    ['Fecha de registro', project.fecha_creacion || '—'],
    ['Versión del documento', project.version || '—'],
    ['Archivo', project.archivo || 'Sin documento'],
  ];
  document.querySelector('#article-data').innerHTML = fields.map(([label, value]) =>
    `<div><strong>${escapeHtml(label)}</strong><span>${escapeHtml(value)}</span></div>`
  ).join('');

  const fileMessage = document.querySelector('#article-file-message');
  const pdf = document.querySelector('#article-pdf');
  const openFile = document.querySelector('#article-open-file');
  if (project.archivo) {
    const fileUrl = service.getProjectFileUrl(project.id_proyecto);
    pdf.src = fileUrl;
    pdf.hidden = false;
    openFile.href = fileUrl;
    openFile.hidden = false;
    showMessage(fileMessage, `Documento: ${project.archivo}`);
  } else {
    pdf.removeAttribute('src');
    pdf.hidden = true;
    openFile.hidden = true;
    showMessage(fileMessage, 'Este artículo todavía no tiene un documento PDF.');
  }

  const editButton = document.querySelector('#edit-article');
  editButton.hidden = !(project.can_edit && editableStates.has(project.estado));
  const reportPanel = document.querySelector('#article-report-panel');
  const reportForm = document.querySelector('#article-report-form');
  const reportMessage = document.querySelector('#article-report-message');
  reportPanel.hidden = !project.can_report && !project.has_open_report;
  reportForm.hidden = !project.can_report;
  reportMessage.textContent = project.has_open_report
    ? 'Ya enviaste un reporte pendiente sobre este artículo.'
    : '';
  const form = document.querySelector('#article-form');
  form.elements.titulo.value = project.titulo;
  form.elements.resumen.value = project.resumen;
  form.elements.id_departamento.value = project.id_departamento;
  form.elements.palabras_clave.value = project.palabras_clave.join(', ');
  form.elements.fecha_publicacion.value = project.fecha_publicacion ?? '';
  const areaSelect = form.elements.namedItem('id_areas[]');
  for (const option of areaSelect.options) {
    option.selected = project.area_ids.map(String).includes(option.value);
  }
}

export async function initArticle() {
  const message = document.querySelector('#article-message');
  const content = document.querySelector('#article-content');
  const form = document.querySelector('#article-form');
  const id = new URLSearchParams(location.search).get('id');
  if (!/^[1-9]\d*$/.test(id ?? '')) {
    showMessage(message, 'El identificador del artículo no es válido.');
    return;
  }

  try {
    const result = await service.getProject(id);
    const department = form.elements.id_departamento;
    department.replaceChildren(new Option('Selecciona un departamento', ''));
    for (const item of result.departamentos) {
      department.add(new Option(item.nombre, item.id_departamento));
    }
    const areas = form.elements.namedItem('id_areas[]');
    const areasLimitMessage = document.querySelector('#article-areas-limit-message');
    areas.replaceChildren();
    for (const item of result.areas_tematicas) {
      areas.add(new Option(item.nombre, item.id_area));
    }
    renderProject(result.project);
    limitMultiSelect(areas, 3, exceeded => {
      showMessage(areasLimitMessage, exceeded ? 'Solo puedes seleccionar hasta 3 áreas temáticas.' : '');
    });
    content.hidden = false;
    message.hidden = true;
  } catch (error) {
    showMessage(message, error.message);
  }

  document.querySelector('#article-report-form').addEventListener('submit', async event => {
    event.preventDefault();
    const reportForm = event.currentTarget;
    const button = reportForm.querySelector('button[type="submit"]');
    const reportOutput = document.querySelector('#article-report-message');
    button.disabled = true;
    reportOutput.textContent = 'Enviando reporte...';
    try {
      const result = await service.reportProject({
        project_id: Number(id),
        reason: reportForm.elements.reason.value,
      });
      reportForm.reset();
      reportForm.hidden = true;
      reportOutput.textContent = result.message;
    } catch (error) {
      reportOutput.textContent = error.message;
      button.disabled = false;
    }
  });

  document.querySelector('#edit-article').addEventListener('click', () => {
    form.hidden = false;
    document.querySelector('#article-edit-message').textContent = '';
    form.scrollIntoView({behavior: 'smooth', block: 'start'});
  });
  document.querySelector('#cancel-edit-article').addEventListener('click', () => {
    form.hidden = true;
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const output = document.querySelector('#article-edit-message');
    const button = form.querySelector('[type="submit"]');
    const pdf = form.elements.pdf.files[0];
    showMessage(output, '');
    if (pdf && (!pdf.name.toLowerCase().endsWith('.pdf') || (pdf.type && pdf.type !== 'application/pdf') || pdf.size > 20 * 1024 * 1024)) {
      showMessage(output, 'Selecciona un PDF válido que no supere 20 MB.');
      return;
    }
    button.disabled = true;
    try {
      await service.updateProject(id, new FormData(form));
      toast('Artículo actualizado correctamente.');
      form.hidden = true;
      form.reset();
      const result = await service.getProject(id);
      renderProject(result.project);
    } catch (error) {
      showMessage(output, error.message);
    } finally {
      button.disabled = false;
    }
  });
}
